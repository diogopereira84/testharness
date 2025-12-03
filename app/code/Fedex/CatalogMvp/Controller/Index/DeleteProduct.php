<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Model\Config as SelfRegConfig;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ProductRepository;

/**
 * Class DeleteProduct
 * Handle the deleteProduct of the CatalogMvp
 */
class DeleteProduct  implements ActionInterface
{
    public const DELETE_MESSAGE = 'Item has been deleted. The item will be removed from your shared catalog shortly';
    public const TECH_TITANS_B2559087_CONFIGURABLE_TOAST_MESSAGE = 'tech_titans_B_2559087';
    /**
     * DeleteProduct Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Product $product
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param CatalogDocumentRefranceApi $catalogDocumentRefranceApi
     * @param ToggleConfig $toggleConfig
     * @param CatalogMvp $catalogMvp
     * @param ProductRepository $productRepository
     * @param SelfRegConfig $selfRegConfig
     */
    public function __construct(
        protected Context $context,
        protected Registry $registry,
        protected Product $product,
        private JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected CatalogDocumentRefranceApi $catalogDocumentRefranceApi,
        protected ToggleConfig $toggleConfig,
        private CatalogMvp $catalogMvp,
        protected ProductRepository $productRepository,
        protected SelfRegConfig $selfRegConfig
    )
    {
    }

    public function execute()
    {
        $productId = $this->context->getRequest()->getParam('id');
        $json = $this->jsonFactory->create();
        $delete = 0;
        try {
            $this->registry->register('isSecureArea', true);

                $productNew = $this->productRepository->getById($productId);

            $productName = $productNew->getName();
            $documentIds = $this->catalogDocumentRefranceApi->getDocumentId($productNew->getExternalProd());

                $this->productRepository->delete($productNew);

            foreach ($documentIds as $documentId){
                $this->catalogDocumentRefranceApi->deleteProductRef($productId, $documentId);
            }
            $delete = 1;

            $this->catalogMvp->insertProductActivity($productId, "DELETE", $productName);
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__ . ":" . __LINE__ ." Product not deleted " . $e->getMessage());
        }
        $data = [];
        $data['delete'] = $delete;

        $data['message'] = __(self::DELETE_MESSAGE);

        $toastToggleEnabled = $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_B2559087_CONFIGURABLE_TOAST_MESSAGE);
        if ($toastToggleEnabled) {
            // Get dynamic message from configuration or use default
            $configMessage = $this->selfRegConfig->getDeleteCatalogItemMessage();
            $data['message'] = (!empty($configMessage) && trim($configMessage) !== '') 
                ? __($configMessage) 
                : __(self::DELETE_MESSAGE);
        }
            
        $json->setData($data);
        return $json;
    }
}
