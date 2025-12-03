<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Framework\App\RequestInterface;

/**
 * Class ProductAssignedToCategory
 * Handle the ProductAssignedToCategory of the CatalogMvp
 */
class ProductAssignedToCategory implements ActionInterface
{
    /**
     * ProductAssignedToCategory Constructor
     *
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CatalogMvpHelper $catalogMvpHelper
     * @param RequestInterface $request
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected CatalogMvpHelper $catalogMvpHelper,
        protected RequestInterface $request
    )
    {
    }

    public function execute()
    {
        $newCategoryId = $this->request->getParam('category_id');
        $newCategoryId = preg_replace("/[^0-9]+/", "", trim($newCategoryId));
        $productSku = $this->request->getParam('product_sku');
        $resultJsonData = $this->resultJsonFactory->create();
        $result = [];
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable() && $this->catalogMvpHelper->isSharedCatalogPermissionEnabled()) {
            try {
                if ($newCategoryId && $productSku) {
                    $response = $this->catalogMvpHelper->assignProductToCategory($productSku, $newCategoryId);
                    if ($response) {
                        $result = ['status' => true, 'message' => 'Product has been moved successfully'];
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . 'Error while assign product to category'. $exception->getMessage()
                );
    
                $result = ['status' => false, 'message' => $exception->getMessage()];
            }
        }

        return $resultJsonData->setData($result);
    }
}
