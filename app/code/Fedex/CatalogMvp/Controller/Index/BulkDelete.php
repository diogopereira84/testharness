<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class BulkDelete
 * Handle the bulk delete product and category of the CatalogMvp
 */
class BulkDelete implements ActionInterface
{
    /**
     * DeleteProduct Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param Product $product
     * @param CatalogMvp $helper
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context $context,
        protected Registry $registry,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected Product $product,
        protected CatalogMvp $helper,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        private ProductRepositoryInterface $productRepository,
        private RequestInterface $request,
        protected ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Execute the bulk delete action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $isToggleEnable = $this->helper->isMvpSharedCatalogEnable();
        $isAdminUser = $this->helper->isSharedCatalogPermissionEnabled();
        if ($isToggleEnable && $isAdminUser) {
            $categoryIds = $this->request->getParam('cid');
            $productIds = $this->request->getParam('pid');
            $categoryIdsArray = !empty($categoryIds) ? $categoryIds : [];
            $productIdsArray = !empty($productIds) ? $productIds : []; 
            $json = $this->jsonFactory->create();
            // Delete category if exist
            $categoryDelete = 0;
            $this->registry->register('isSecureArea', true);
            if (!empty($categoryIdsArray)) {
                    foreach ($categoryIdsArray as $categoryId) {
                        $categoryDelete = $this->helper->deleteCategory($categoryId);
                    }
                }  
                 
            $productDelete = 0;
            if (isset($productIdsArray[0]) && ($productIdsArray[0] != null))
            {
                if (!empty($productIdsArray)) {
                        foreach ($productIdsArray as $productId) {
                            $productDelete = $this->deleteProduct($productId);
                        }
                    }
                    
            }
            // get the response on the basis of delete
            $data = $this->response($productDelete, $categoryDelete);
            $json->setData($data);
            return $json;
        }
    }

    /**
     * Delete Product
     *
     * @param int $productId
     * @return int
     * @throws Exception
     */

    public function deleteProduct($productId)
    {
        $productDelete = 0;
        try {
            $productNew = $this->productRepository->getById($productId);

            $productName = $productNew->getName();
            $documentIds = $this->catalogDocumentRefranceApi->getDocumentId($productNew->getExternalProd());
            $this->productRepository->delete($productNew);
            foreach ($documentIds as $documentId) {
                $this->catalogDocumentRefranceApi->deleteProductRef($productId, $documentId);
            }
            $productDelete = 1;

            $this->helper->insertProductActivity($productId, "DELETE", $productName);

        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Product not deleted ".$productId." ". $e->getMessage());
        }
        return $productDelete;
    }

    /**
     * Prepare response data based on delete results
     *
     * @param int $productDelete
     * @param int $categoryDelete
     * @return array
     */
    public function response($productDelete, $categoryDelete)
    {

        $data = [];

        if ($productDelete && !$categoryDelete) {
            $data['message'] = __('Items have been deleted from shared catalog.');
        }

        if (!$productDelete && $categoryDelete) {
            $data['message'] = __('Folders have been deleted from shared catalog.');
        }

        if ($productDelete && $categoryDelete) {
            $data['message'] = __('Items/Folders have been deleted from shared catalog.');
        }
        $data['delete'] = 1;
        return $data;
    }
}
