<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\CustomerGroup\Model;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\SaaSCommon\Api\ConfigInterface as FedexSaaSCommonConfig;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\CatalogPermissions\Model\Permission;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Group as CoreGroup;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Action;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as CatalogPermissionCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Folder Permission Class
 */
class FolderPermission
{
    const SHAREDCATALOG_PERMISSION_TABLE = 'sharedcatalog_category_permissions';

    const MAGENTOCATALOG_PERMISSION_TABLE = 'magento_catalogpermissions';

    /**
     * FolderPermission Class Constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param CategoryRepository $categoryRepository
     * @param CollectionFactory $productCollectionFactory
     * @param Action $productAction
     * @param LoggerInterface $logger
     * @param CatalogMvp $catalogMvpHelper
     * @param CatalogPermissionCollectionFactory $catalogPermissionCollectionFactory
     * @param ToggleConfig $toggleConfig
     * @param FedexSaaSCommonConfig $fedexSaaSCommonConfig
     * @param CustomerGroupAttributeHandlerInterface $customerGroupAttributeHandler
     */
    public function __construct(
        protected ResourceConnection $resourceConnection,
        protected CategoryRepository $categoryRepository,
        protected CollectionFactory $productCollectionFactory,
        protected Action $productAction,
        private LoggerInterface $logger,
        protected CatalogMvp $catalogMvpHelper,
        protected CatalogPermissionCollectionFactory $catalogPermissionCollectionFactory,
        protected ToggleConfig $toggleConfig,
        private FedexSaaSCommonConfig                   $fedexSaaSCommonConfig,
        private CustomerGroupAttributeHandlerInterface  $customerGroupAttributeHandler,
    )
    {
    }

    /**
     * Map Categories with Customer Group
     * @param  array $categoryIds
     * @param  int $parentData
     * @param  int $newGroupId
     * @param  bool $isEdit Flag to indicate if we are editing an existing user group
     * @return void
     */
    public function mapCategoriesCustomerGroup($categoryIds, $parentData, $newGroupId, $isEdit = false)
    {
        $this->deletePermissions($newGroupId, static::SHAREDCATALOG_PERMISSION_TABLE, $categoryIds);
        $this->deletePermissions($newGroupId, static::MAGENTOCATALOG_PERMISSION_TABLE, $categoryIds);

        $sharedCatalogParentPermissions = $this->getParentGroupPermissions($parentData, static::SHAREDCATALOG_PERMISSION_TABLE);
        $this->insertParentGroupPermissions($sharedCatalogParentPermissions, $newGroupId, static::SHAREDCATALOG_PERMISSION_TABLE, $categoryIds);

        $magentoCatalogPermissions = $this->getParentGroupPermissions($parentData, static::MAGENTOCATALOG_PERMISSION_TABLE);
        $this->insertParentGroupPermissions($magentoCatalogPermissions, $newGroupId, static::MAGENTOCATALOG_PERMISSION_TABLE);

        if ($this->fedexSaaSCommonConfig->isTigerD200529Enabled() && !$isEdit) {
            $this->customerGroupAttributeHandler->addAttributeOption([$newGroupId]);
            $this->customerGroupAttributeHandler->pushEntityToQueue($newGroupId, CoreGroup::ENTITY);
        }

        if (!empty($categoryIds)) {
            $this->insertNewCategoryPermissions($categoryIds, $newGroupId, static::SHAREDCATALOG_PERMISSION_TABLE);
            $this->insertNewCategoryPermissions($categoryIds, $newGroupId, static::MAGENTOCATALOG_PERMISSION_TABLE);
            if (!$this->toggleConfig->getToggleConfigValue('sgc_user_group_and_folder_level_permissions')) {
                $this->insertNewCategoryPermissions($categoryIds, $newGroupId, static::SHAREDCATALOG_PERMISSION_TABLE, true);
                $this->insertNewCategoryPermissions($categoryIds, $newGroupId, static::MAGENTOCATALOG_PERMISSION_TABLE, true);
                $this->insertDenyAllPermissions($categoryIds, static::SHAREDCATALOG_PERMISSION_TABLE, true);
                $this->insertDenyAllPermissions($categoryIds, static::MAGENTOCATALOG_PERMISSION_TABLE, true);
            }
            $this->insertDenyAllPermissions($categoryIds, static::SHAREDCATALOG_PERMISSION_TABLE);
            $this->insertDenyAllPermissions($categoryIds, static::MAGENTOCATALOG_PERMISSION_TABLE);
        }
    }

    /**
     * Check Permissions in Parent Category
     *
     * @param int $customerGroupId
     * @param int $categoryId
     * @param string $tableName
     * @return boolean
     */
    public function checkParentPermission($customerGroupId, $categoryId, $tableName)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($tableName);

        $select = $connection->select()->from(
            $tableName,
            ['permission_id']
        )->where('category_id = ?', $categoryId);

        if ($customerGroupId == null) {
            $select->where('customer_group_id IS NULL');
        } else {
            $select->where('customer_group_id = ?',$customerGroupId);
        }

        $permissionId = 0;
        try {
            $permissionId = $connection->fetchOne($select);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with category permission save for the group: '. $customerGroupId . 'is '. $e->getMessage());
        }

        return $permissionId;
    }

    /**
     * Delete Permissions
     *
     * @param int $customerGroupId
     * @param string $tableName
     * @param array $categoryIds
     * @return void
     */
    public function deletePermissions($customerGroupId, $tableName, $categoryIds = [])
    {
        $connection = $this->resourceConnection->getConnection();

        try {
            $connection->delete($tableName, ['customer_group_id = ?' => $customerGroupId]);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with category permission save for the group: '. $customerGroupId . 'is '. $e->getMessage());
        }
    }

    /**
     * Get Parent Group Permissions from Shared Catalog Table
     * @param  $groupId int
     * @param  $tableName string
     * @return $permissions array
     */
    public function getParentGroupPermissions($groupId, $tableName)
    {
        $permissions = [];

        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($tableName);

        $select = $connection->select()->from(
            $tableName,
            ['*']
        )->where('customer_group_id = ?', $groupId);

        try {
            $permissions = $connection->fetchAll($select);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with category permission save for the group: '. $groupId . 'is '. $e->getMessage());
        }

        return $permissions;
    }

    /**
     * Insert Parent Group Permissions
     * @param  $permissions array
     * @param  $newGroupId int
     * @param  $tableName string
     * @param  $categoryIds array
     * @return void
     */
    public function insertParentGroupPermissions($permissions, $newGroupId, $tableName, $categoryIds = [])
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($tableName);

        foreach ($permissions as $permission) {
            if ($tableName == 'sharedcatalog_category_permissions') {
                if (in_array($permission['category_id'], $categoryIds) && $permission['permission'] == -2) {
                    continue;
                }
                $data = [
                    'category_id' => $permission['category_id'],
                    'customer_group_id' => $newGroupId,
                    'permission' => $permission['permission']
                ];
            } else {
                $data = [
                    'category_id' => $permission['category_id'],
                    'customer_group_id' => $newGroupId,
                    'grant_catalog_category_view' => $permission['grant_catalog_category_view'],
                    'grant_catalog_product_price' => $permission['grant_catalog_product_price'],
                    'grant_checkout_items' => $permission['grant_checkout_items']
                ];
            }
            try {
                $connection->insert($tableName, $data);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with category permission save for the group: '. $newGroupId . 'is '. $e->getMessage());
            }
        }
    }

    /**
     * Insert New Category Permissions
     * @param  array $categoryIds
     * @param  int $newGroupId
     * @param  string $tableName
     * @param  boolean $parentInsert
     * @return void
     */
    public function insertNewCategoryPermissions($categoryIds, $newGroupId, $tableName, $parentInsert = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($tableName);

        foreach ($categoryIds as $categoryId) {
            if ($parentInsert) {
                $category = $this->categoryRepository->get($categoryId);
                $categoryId = $category->getParentId();
            }

            if ($this->checkParentPermission($newGroupId, $categoryId, $tableName)) {
                continue;
            }

            if ($tableName == 'sharedcatalog_category_permissions') {
                $data = [
                    'category_id' => $categoryId,
                    'customer_group_id' => $newGroupId,
                    'permission' => -1
                ];
            } else {
                $data = [
                    'category_id' => $categoryId,
                    'customer_group_id' => $newGroupId,
                    'grant_catalog_category_view' => -1,
                    'grant_catalog_product_price' => -1,
                    'grant_checkout_items' => -1
                ];
            }
            try {
                $connection->insert($tableName, $data);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with category permission save for the group: '. $newGroupId . 'is '. $e->getMessage());
            }
        }
    }

    /**
     * Insert Deny All Permissions
     * @param  array $categoryIds
     * @param  string $tableName
     * @param  boolean $parentInsert
     * @return void
     */
    public function insertDenyAllPermissions($categoryIds, $tableName, $parentInsert = false)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName($tableName);

        foreach ($categoryIds as $categoryId) {
            if ($parentInsert) {
                $category = $this->categoryRepository->get($categoryId);
                $categoryId = $category->getParentId();
            }

            if ($this->checkParentPermission(null, $categoryId, $tableName)) {
                continue;
            }

            if ($tableName == 'sharedcatalog_category_permissions') {
                $data = [
                    'category_id' => $categoryId,
                    'customer_group_id' => null,
                    'permission' => -2
                ];
            } else {
                $data = [
                    'category_id' => $categoryId,
                    'customer_group_id' => null,
                    'grant_catalog_category_view' => -2,
                    'grant_catalog_product_price' => -2,
                    'grant_checkout_items' => -2
                ];
            }
            try {
                $connection->insert($tableName, $data);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with category permission save for the group: '. $tableName . 'is '. $e->getMessage());
            }
        }
    }

    /**
     * Check Category Permission
     * @param  int $categoryId
     * @param  int $groupId
     * @return boolean
     */
    public function checkCategoryPermission($categoryId, $groupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::MAGENTOCATALOG_PERMISSION_TABLE);

        $select = $connection->select()->from(
            $tableName,
            ['grant_catalog_category_view']
        )->where('category_id = ?', $categoryId)
         ->where('customer_group_id = ?',$groupId);

        $permission = $connection->fetchOne($select);
        if ($permission == '-1') {
            return true;
        } else if (!$this->checkCategoryPermissionExist($categoryId)) {
            $category = $this->categoryRepository->get($categoryId);
            $parentCategoryId = $category->getParentId();
            $select = $connection->select()->from(
                        $tableName,
                        ['grant_catalog_category_view']
                        )->where('category_id = ?', $parentCategoryId)
                        ->where('customer_group_id = ?',$groupId);
            $permission = $connection->fetchOne($select);
            if ($permission == '-1') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check If Permission exist in category
     * @param  int $groupId
     * @return int
     */
    public function checkCategoryPermissionExist($categoryId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::MAGENTOCATALOG_PERMISSION_TABLE);
        $permission = 0;
        try {
            $select = $connection->select()->from(
                $tableName,
                ['permission_id']
            )->where('category_id = ?', $categoryId);

            $permission = $connection->fetchOne($select);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with Permissions for Category ID: '. $categoryId . 'is '. $e->getMessage());
        }
        return $permission;
    }

    /**
     * Assign Customer Group Id
     * @param $categoryIds
     * @param $groupId
     * @param $isProductIds
     * @return void
     */
    public function assignCustomerGroupId($categoryIds, $groupId, $isProductIds = false)
    {
        $productsCollection = $this->getProductCollection($categoryIds, $isProductIds);
        if ($productsCollection->getSize()) {
            /** @var Product $product */
            foreach ($productsCollection->getItems() as $product) {
                $currentSharedCatalogs = $product->getData('shared_catalogs');
                if ($currentSharedCatalogs) {
                    $currentSharedCatalogs = explode(',', $currentSharedCatalogs);
                    if (!in_array($groupId, $currentSharedCatalogs)) {
                        $currentSharedCatalogs[] = $groupId;
                        if($this->catalogMvpHelper->isD216406Enabled()){
                        $this->productAction->updateAttributes(
                            [$product->getId()],
                            ['shared_catalogs' => implode(',',array_unique($currentSharedCatalogs))],
                            0
                        );}else{
                            $this->productAction->updateAttributes(
                                [$product->getId()],
                                ['shared_catalogs' => implode(',',$currentSharedCatalogs)],
                                0
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Un Assign Customer Group Id
     * @param $categoryIds
     * @param $groupId
     * @param $isProductIds
     * @return void
     */
    public function unAssignCustomerGroupId($categoryIds, $groupId, $isProductIds = false)
    {
        $productsCollection = $this->getProductCollection($categoryIds, $isProductIds);
        if ($productsCollection->getSize()) {
            /** @var Product $product */
            foreach ($productsCollection->getItems() as $product) {
                $currentSharedCatalogs = $product->getData('shared_catalogs');
                if ($currentSharedCatalogs) {
                    $currentSharedCatalogs = explode(',', $currentSharedCatalogs);
                    if (in_array($groupId, $currentSharedCatalogs)) {
                        $key = array_search($groupId, $currentSharedCatalogs);
                        unset($currentSharedCatalogs[$key]);
                        if($this->catalogMvpHelper->isD216406Enabled()){
                            $this->productAction->updateAttributes(
                                [$product->getId()],
                                ['shared_catalogs' => implode(',',array_unique($currentSharedCatalogs))],
                                0
                            );
                        }else{
                            $this->productAction->updateAttributes(
                                [$product->getId()],
                                ['shared_catalogs' => implode(',',$currentSharedCatalogs)],
                                0
                            );
                        }

                    }
                }
            }
        }
    }

    /**
     * @param $ids
     * @param $isProductIds
     * @return Collection|AbstractDb
     */
    protected function getProductCollection($ids, $isProductIds = false)
    {
        $productCollection = $this->productCollectionFactory->create()->addAttributeToSelect('shared_catalogs');
        if ($isProductIds) {
            $productCollection->addFieldToFilter('entity_id', ['in' => $ids]);
        } else {
            $productCollection->addCategoriesFilter(['in' => $ids]);
        }
        $productCollection->load();

        return $productCollection;
    }

    /**
     * Get Customer Group ids
     * @param  array $categoryIds
     * @return array
     */
    public function getCustomerGroupIds($categoryIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::MAGENTOCATALOG_PERMISSION_TABLE);
        $groupIds = [];
        if (is_array($categoryIds) && !empty($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
               $select = $connection->select()->from(
                $tableName,
                ['customer_group_id']
                )
                ->where('category_id = ?', $categoryId)
                ->where('grant_catalog_category_view = ?', -1);
                $allGroupIds = $connection->fetchAll($select);
                foreach ($allGroupIds as $groupId) {
                    $groupId = $groupId['customer_group_id'];
                    if ($groupId != '' && $groupId != null) {
                        $parentId = $this->getParentGroupId($groupId);
                        if (!$parentId) {
                            return [];
                        }
                        $groupIds[] = $groupId;
                    }
                }
            }
        }
        return array_unique($groupIds);
    }

    /**
     * Get Parent Group id
     * @param  int $groupId
     * @return int
     */
    public function getParentGroupId($groupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('parent_customer_group');
        $parentGroupId = 0;
        try {
            $select = $connection->select()->from(
                $tableName,
                ['parent_group_id']
            )->where('customer_group_id = ?', $groupId);
            $parentGroupId = $connection->fetchOne($select);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error with Getting Parent Group id: ' . $groupId . 'is ' . $e->getMessage());
        }
        return $parentGroupId;
    }

    /**
     * Get Unassigned Categories
     * @param  int $groupId
     * @param  array $savedCategories
     * @return array
     */
    public function getUnAssignedCategories($groupId, $savedCategories)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::MAGENTOCATALOG_PERMISSION_TABLE);
        $categoryIds = [];
        $select = $connection->select()->from(
            $tableName,
            ['category_id']
        )->where('customer_group_id = ?', $groupId);
        $categoryIds = $connection->fetchAll($select);
        $sortedCategories = [];
        foreach ($categoryIds as $categoryId) {
            $sortedCategories[] = $categoryId['category_id'];
        }

        $unAssignedCategories = array_diff($sortedCategories, $savedCategories);

        return $unAssignedCategories;
    }

    /**
     * Check if categories have allow permissions and if not remove deny all permission
     *
     * @param array $categories
     *
     * @return void
     */
    public function updatePermissions(array $categories): void
    {
        $connection = $this->resourceConnection->getConnection();
        $magentoCatalogPermissionTableName = $connection->getTableName(static::MAGENTOCATALOG_PERMISSION_TABLE);
        $sharedCatalogPermissionTableName = $connection->getTableName(static::SHAREDCATALOG_PERMISSION_TABLE);

        $magentoCatalogPermissionCollection = $this->catalogPermissionCollectionFactory->create();

        $magentoCatalogPermissionCollection->addFieldToSelect('category_id')
            ->addFieldToFilter('category_id', ['in' => $categories])
            ->addFieldToFilter('customer_group_id', ['notnull' => true])
            ->addFieldToFilter('grant_catalog_category_view', Permission::PERMISSION_ALLOW)
            ->getSelect()->distinct(true);

        try {
            $categoryIdAllowPermissions = $magentoCatalogPermissionCollection->getColumnValues('category_id');
            $denyAllCategoryIds = array_diff($categories, $categoryIdAllowPermissions);

            if ($denyAllCategoryIds) {
                $connection->delete(
                    $magentoCatalogPermissionTableName,
                    [
                        'category_id IN (?)' => $denyAllCategoryIds,
                        'customer_group_id IS NULL',
                        'grant_catalog_category_view = ?' => Permission::PERMISSION_DENY
                    ]
                );
                $connection->delete(
                    $sharedCatalogPermissionTableName,
                    [
                        'category_id IN (?)' => $denyAllCategoryIds,
                        'customer_group_id IS NULL',
                        'permission = ?' => Permission::PERMISSION_DENY
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error updating permissions for categories: '
                . $e->getMessage());
        }
    }
}
