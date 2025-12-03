<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Observer\ETag;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Category;
use Fedex\CatalogMvp\Api\ConfigInterface as CatalogMvpConfigInterface;
use Fedex\CatalogMvp\Helper\EtagHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class CategorySaveAfterEtagUpdateObserver implements ObserverInterface
{
    /**
     * CategorySaveAfterEtagUpdateObserver constructor
     *
     * @param CatalogMvpConfigInterface $catalogMvpConfigInterface
     * @param EtagHelper $etagHelper
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        protected CatalogMvpConfigInterface $catalogMvpConfigInterface,
        protected EtagHelper $etagHelper,
        protected CategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();

        /* B-2371268 Create ETag for catalog pages */
        $isB2371268enabled = $this->catalogMvpConfigInterface->isB2371268ToggleEnabled();
        if ($isB2371268enabled && $category && $category->getId()) {
            $etag = $this->etagHelper->generateEtag($category);
            $category->setEtag($etag);

            $parentId = $category->getParentId();
            if ($parentId) {
                $parentCategory = $this->categoryRepository->get($parentId);
                $parentEtag = $this->etagHelper->generateEtag($parentCategory);
                $parentCategory->setData('etag', $parentEtag);
                $parentCategory->save();
            }
        }
    }
}
