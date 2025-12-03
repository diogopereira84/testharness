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
use Fedex\CatalogMvp\Helper\CatalogMvp as CatalogMvpHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;

/**
 * Class MoveFolder
 * Handle the MoveFolder of the CatalogMvp
 */
class MoveFolder implements ActionInterface
{
    /**
     * MoveFolder Constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CatalogMvpHelper $catalogMvpHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param RequestInterface $request
     * @param CatalogMvpConfigInterface $catalogMvpConfigInterface
     * @param EtagHelper $etagHelper
     */
    public function __construct(
        protected Context $context,
        protected LoggerInterface $logger,
        protected JsonFactory $resultJsonFactory,
        protected CatalogMvpHelper $catalogMvpHelper,
        protected CategoryRepositoryInterface $categoryRepository,
        protected RequestInterface $request,
        protected CatalogMvpConfigInterface $catalogMvpConfigInterface,
        protected EtagHelper $etagHelper
    ) {
    }

    /**
     * Execute Function
     */
    public function execute()
    {
        $newCategoryId = $this->request->getParam('cat_id');
        $catId = $this->request->getParam('id');
        $resultJsonData = $this->resultJsonFactory->create();

        /* B-2371268 Create ETag for catalog pages */
        $isB2371268enabled = $this->catalogMvpConfigInterface->isB2371268ToggleEnabled();
        $result = [];
        if ($this->catalogMvpHelper->isMvpSharedCatalogEnable() &&
            $this->catalogMvpHelper->isSharedCatalogPermissionEnabled()) {
            try {
                if ($newCategoryId && $catId) {
                    $category = $this->categoryRepository->get($newCategoryId);
                    $this->catalogMvpHelper->assignCategoryToCategory($newCategoryId, $catId);
                    $url = $this->catalogMvpHelper->getCategoryUrl($category);

                    if ($isB2371268enabled) {
                        $etag = $this->etagHelper->generateEtag($category);
                        $category->setEtag($etag);
                        $category->save();

                        // Handle ETag for the parent category, if catId (parent category) is provided
                        $parentCategory = $this->categoryRepository->get($catId);
                        $parentEtag = $this->etagHelper->generateEtag($parentCategory);
                        $parentCategory->setData('etag', $parentEtag);
                        $parentCategory->save();
                    }
                    $result = ['status' => true, 'message' => __('Folder has been moved.'), 'url' => $url];
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . 'Error while assign category to category'. $exception->getMessage()
                );

                $result = ['status' => false, 'message' => $exception->getMessage()];
            }
        }

        return $resultJsonData->setData($result);
    }
}
