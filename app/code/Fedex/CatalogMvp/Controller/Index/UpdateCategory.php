<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class UpdateCategory
 * Handle the UpdateCategory of the CatalogMvp
 */
class UpdateCategory implements ActionInterface
{
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    private Pool $_cacheFrontendPool;
    private TypeListInterface $_cacheTypeList;

    /**
     * UpdateCategory Constructor
     *
     * @param CategoryFactory categoryFactory
     * @param CategoryResource categoryResource
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     */
    public function __construct(
        protected CategoryFactory $categoryFactory,
        protected CategoryResource $categoryResource,
        protected LoggerInterface $logger,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        protected StoreManagerInterface $storeManager,
        protected RequestInterface $request,
        protected ToggleConfig $toggleConfig,
        protected CategoryRepositoryInterface $categoryRepositoryInterface,
        protected JsonFactory $jsonFactory,
        protected CollectionFactory $collectionFactory,
        protected ProductRepositoryInterface $productRepository
    ) {
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
    }

    public function execute()
    {
        $categoryId = $this->request->getParam('id');
        $newStatus =  $this->request->getParam('status');
        $resultJson = $this->jsonFactory->create();

        $category = $this->categoryRepositoryInterface->get($categoryId);
        $storeId = $this->storeManager->getStore()->getId();
        try {
            if($category) {
                $category->setCustomAttributes([
                    'is_publish' => $newStatus
                ]);
                $this->categoryRepositoryInterface->save($category->setStoreId(0)->setIsActive(1));
                $this->categoryRepositoryInterface->save($category->setStoreId($storeId)->setIsActive(1));

                if ($this->toggleConfig->getToggleConfigValue('mazegeeks_D197476_fix')) {
                    $childCategories = $this->getChildCategories($category);
                    $categoryIds = array_merge([$categoryId], $childCategories);
                    $productCollection = $this->getProductsInCategories($categoryIds);

                    foreach ($categoryIds as $categoryIdValue) {
                        if ($categoryIdValue != $categoryId) {
                            $categoryData = $this->categoryRepositoryInterface->get($categoryIdValue);
                            $categoryData->setCustomAttributes([
                                'is_publish' => $newStatus
                            ]);
                            $this->categoryRepositoryInterface->save($categoryData->setStoreId(0)->setIsActive(1));
                            $this->categoryRepositoryInterface->save($categoryData->setStoreId($storeId)->setIsActive(1));
                        }
                    }
  
                    foreach ($productCollection as $product) {
                        $productData = $this->productRepository->getById($product->getId());
                        $productData->setPublished($newStatus);
                        $productData->save();
                    }
                }

                return $resultJson->setData(['success' => true, 'message' => 'Category updated successfully.']);
            }

        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage());
            return $resultJson->setData(['success' => true, 'message' => $exception->getMessage()]);
        }

    }

    /**
     * Get all child categories recursively
     *
     * @param object $category
     * @return array
     */
    protected function getChildCategories($category)
    {
        $childCategories = [];

        if ($category->getId()) {
            $childCategoryCollection = $category->getChildrenCategories();
            foreach ($childCategoryCollection as $childCategory) {
                $childCategories[] = $childCategory->getId();
                $childCategories = array_merge($childCategories, $this->getChildCategories($childCategory));
            }
        }

        return $childCategories;
    }
    
    /**
     * Get all products in the given categories
     *
     * @param array $categoryIds
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductsInCategories(array $categoryIds)
    {
        $productCollection = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addCategoriesFilter(['in' => $categoryIds]);

        return $productCollection;
    }
}
