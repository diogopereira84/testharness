<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Controller\Update;

use Magento\Framework\App\Action\Context;
use Magento\Store\Model\GroupFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogPermissions\Model\PermissionFactory;

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
class SelfRegCategory extends \Magento\Framework\App\Action\Action
{
    /**
     * @var array $catResult
     */
    protected $catResult = [];

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Store\Model\GroupFactory $groupFactory
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     * @param CategoryManagementInterface $categoryManagementInterface
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $collectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagementInterface
     * @param ProductFactory $productFactory
     * @param PermissionFactory $permissionFactory
     */
    public function __construct(
        Context $context,
        private GroupFactory $groupFactory,
        private LoggerInterface $logger,
        private Data $jsonHelper,
        private CategoryManagementInterface $categoryManagementInterface,
        private CategoryFactory $categoryFactory,
        private CollectionFactory $collectionFactory,
        private CategoryLinkManagementInterface $categoryLinkManagementInterface,
        private ProductFactory $productFactory,
        private PermissionFactory $permissionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute method to create Sde Category
     *
     * @return null|bool
     */
    public function execute()
    {
        try {

            $b2bGroupObj = $this->groupFactory->create()->load('b2b_store', 'code');
            $b2bRootCatId = $b2bGroupObj->getRootCategoryId();

            $categoryCollection = $this->categoryFactory->create()
                ->getCollection()->addAttributeToFilter('parent_id', $b2bRootCatId);
            $printProdCategoryId = null;
            foreach ($categoryCollection as $catCollection) {
                $catObj = $this->categoryFactory->create()->load($catCollection->getId());
                if ($catObj->getName() == 'Print Products') {
                    $printProdCategoryId = $catObj->getId();
                    break;
                }
            }

            if ($printProdCategoryId) {
                $printProdCat = $this->categoryManagementInterface->getTree($printProdCategoryId);

                $newCatId = $this->copyAndMoveCategory($printProdCategoryId, $b2bRootCatId);
                $childrenCategory = $printProdCat->getChildrenData();
                if (is_array($childrenCategory) && count($childrenCategory)) {
                    $this->traverseChildren($childrenCategory, $newCatId);
                }
            }

            $updateResult = $this->jsonHelper->jsonEncode($this->catResult);
            $this->logger->info('Ondemand controller hit : Copy SelfReg Print Product Category : ' . $updateResult);

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Method to traverse all children of category
     *
     * @param array $childCategories
     * @param int $parentCatId
     * @return null
     */
    public function traverseChildren($childCategories, $parentCatId)
    {
        foreach ($childCategories as $childCategory) {
            $copyCatId = $childCategory->getId();
            $newCatId = $this->copyAndMoveCategory($copyCatId, $parentCatId);

            $this->catResult[$copyCatId] = $newCatId;
            $childrenCategory = $childCategory->getChildrenData();
            if (is_array($childrenCategory) && count($childrenCategory)) {
                $this->traverseChildren($childrenCategory, $newCatId);
            }
        }
    }

    /**
     * Method to copy and move categories
     *
     * @param int $copyCatId
     * @param int $parentCatId
     * @return int
     */
    public function copyAndMoveCategory($copyCatId, $parentCatId)
    {

        $parentCategory = $this->categoryFactory->create()->load($parentCatId);
        $parentCategoryId = $parentCategory->getId();

        $category = $this->categoryFactory->create()->load($copyCatId); // The ID of the category you want to copy.
        $copy = clone $category;
        $copy->setId(null);

        if ($category->getName() == 'Print Products') {
            $copy->setName('SelfReg ' . $category->getName());
        }

        $copy->setUrlKey('selfreg-' . $category->getUrlKey());
        $copy->save();

        $this->categoryManagementInterface->move($copy->getId(), $parentCategoryId);

        // update category permission
        $this->updateCategoryPermission($copyCatId, $copy->getId());

        // assign products
        $this->assignProducts($copyCatId, $copy->getId());

        return $copy->getId();
    }

    /**
     * Method assign products to categories
     *
     * @param int $sourceCatId
     * @param int $newCatId
     * @return null
     */
    public function assignProducts($sourceCatId, $newCatId)
    {
        $prodCollection = $this->collectionFactory->create()->addCategoriesFilter(['in' => $sourceCatId]);
        $proData = $prodCollection->getData();
        foreach ($proData as $prodItem) {
            $prodObj = $this->productFactory->create()->load($prodItem['entity_id']);
            $catIds = $prodObj->getCategoryIds();
            array_push($catIds, $newCatId);

            $prodObj->setCategoryIds($catIds)->save();
        }
    }

    /**
     * Method to update category permissions
     *
     * @param int $oldCatId
     * @param int $newCatId
     * @return null
     */
    public function updateCategoryPermission($oldCatId, $newCatId)
    {
        $permissionCollection = $this->permissionFactory->create()
                ->getCollection()->addFieldToFilter('category_id', $oldCatId);

        if ($permissionCollection->getSize()) {
            foreach ($permissionCollection as $permission) {
                $permissionInfo = $permission->getData();
                unset($permissionInfo['permission_id']);
                $permissionInfo['category_id'] = $newCatId;

                $this->permissionFactory->create()->setData($permissionInfo)->save();
            }
        }
    }
}
