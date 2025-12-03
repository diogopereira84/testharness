<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class BulkMoveProduct
 * Handle the BulkMoveProduct of the CatalogMvp
 */
class BulkMoveProduct implements ActionInterface
{
    /**
     * MoveProduct Constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CatalogMvpHelper $catalogMvpHelper
     * @param CatalogMvpHelper $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param RequestInterface $request
     */
    public function __construct(
        protected Context $context,
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected CatalogMvpHelper $catalogMvpHelper,
        protected ProductRepositoryInterface $productRepository,
        protected CategoryRepositoryInterface $categoryRepository,
        private RequestInterface $request
    )
    {
    }

    public function execute()
    {
        $newCategoryId = $this->request->getParam('cat_id');
        $productIds = $this->request->getParam('id');
        $resultJsonData = $this->resultJsonFactory->create();
        $result = [];
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable() && $this->catalogMvpHelper->isSharedCatalogPermissionEnabled()) {

            // Move product if exist
            $productMove = 0;
            if ($productIds[0] != null) {
                foreach ($productIds as $productId) {
                    $productMove = $this->moveProduct($productId, $newCategoryId);
                }
            }

            // get the response on the basis of move
            $result = $this->response($productMove,$newCategoryId);
        }

        return $resultJsonData->setData($result);
    }

    /**
     * Move Product
     *
     * @param int $productId
     * @return int
     * @throws Exception
     */

    public function moveProduct($productId, $newCategoryId) {
        $productMove = 0;
        try {
            $product = $this->productRepository->getById($productId);
            $productSku = $product->getSku();
            if ($newCategoryId && $productSku) {
                $this->catalogMvpHelper->assignProductToCategory($productSku, $newCategoryId);
            }
            $productMove = 1;
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . 'Error while assign product to category'. $e->getMessage()
            );
        }
        return $productMove;
    }

    public function response($productMove, $newCategoryId) {

        $data = [];

        if ($productMove) {
            $category = $this->categoryRepository->get($newCategoryId);
            $url = $this->catalogMvpHelper->getCategoryUrl($category);
            $data['message'] = __('Your items have been moved. The items will be available in the new folder shortly.');
            $data['url'] = $url;
        }

        $data['status'] = true;
        return $data;
    }
}
