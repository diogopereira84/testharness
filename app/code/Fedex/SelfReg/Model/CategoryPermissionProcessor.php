<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory as PermissionCollectionFactory;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\Permissions\Synchronizer;
use Magento\SharedCatalog\Model\State;
use Magento\CatalogPermissions\Model\Permission;
use Magento\SharedCatalog\Model\ResourceModel\Permission as PermissionResource;
use Magento\SharedCatalog\Model\SharedCatalogInvalidation;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CustomerGroup\Model\FolderPermission;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\ResourceConnection;
use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup as ParentUserGroupResourceModel;

class CategoryPermissionProcessor
{
    private const MAGENTOCATALOG_PERMISSION_TABLE = 'magento_catalogpermissions';
    private const SHAREDCATALOG_PERMISSION_TABLE = 'sharedcatalog_category_permissions';

    /**
     * CategoryPermissionProcessor constructor.
     *
     * @param PermissionCollectionFactory $permissionCollectionFactory
     * @param CatalogPermissionManagement $catalogPermissionManagement
     * @param Synchronizer $permissionsSynchronizer
     * @param State $sharedCatalogState
     * @param FolderPermission $folderPermission
     * @param CategoryRepository $categoryRepository
     * @param ResourceConnection $resourceConnection
     * @param PermissionResource $sharedCatalogPermissionResource
     * @param SharedCatalogInvalidation $sharedCatalogInvalidation
     * @param CatalogMvp $catalogMvpHelper
     * @param ParentUserGroupResourceModel $parentUserGroupResourceModel
     */
    public function __construct(
        private PermissionCollectionFactory $permissionCollectionFactory,
        private CatalogPermissionManagement $catalogPermissionManagement,
        private Synchronizer $permissionsSynchronizer,
        private State $sharedCatalogState,
        private FolderPermission $folderPermission,
        private CategoryRepository $categoryRepository,
        private ResourceConnection $resourceConnection,
        private readonly PermissionResource $sharedCatalogPermissionResource,
        private readonly SharedCatalogInvalidation $sharedCatalogInvalidation,
        private readonly CatalogMvp $catalogMvpHelper,
        private ParentUserGroupResourceModel $parentUserGroupResourceModel
    ) {
        $this->permissionCollectionFactory = $permissionCollectionFactory;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->permissionsSynchronizer = $permissionsSynchronizer;
        $this->sharedCatalogState = $sharedCatalogState;
    }

    /**
     * Get Active Website IDs from Shared Catalog State.
     *
     * @return array
     */
    public function getActiveWebsiteIds(): array
    {
        return array_map(
            fn($website) => (int)$website->getId(),
            $this->sharedCatalogState->getActiveWebsites()
        );
    }

    /**
     * Check if edit folder access toggle is enabled
     *
     * @return bool
     */
    public function isAddEditFolderAccessEnabled(): bool
    {
        return $this->catalogMvpHelper->isEditFolderAccessEnabled();
    }

    /**
     * Process permissions for a given scope.
     *
     * @param int|null $scopeId
     * @param int $categoryId
     * @param bool $isFolderRestricted
     * @param array|null $selectedGroupIds
     *
     * @return bool
     */
    public function processPermissions(
        ?int $scopeId,
        int $categoryId,
        bool $isFolderRestricted,
        array $selectedGroupIds = []
    ): bool {
        // Check the toggle
        if (!$this->isAddEditFolderAccessEnabled()) {
            return false;
        }

        if (empty($selectedGroupIds)) {
            return false;
        }

        $selectedGroups = array_keys($selectedGroupIds);

        $permissionCollection = $this->permissionCollectionFactory->create();
        $permissionCollection
            ->addFieldToFilter('category_id', $categoryId)
            ->addFieldToFilter('website_id', $scopeId ? ['seq' => $scopeId] : ['null' => true])
            ->addFieldToFilter('customer_group_id', ['in' => $selectedGroups]);
        
        if (!$isFolderRestricted) {
            $this->processDenyPermissions($categoryId, $selectedGroups);
            $this->removeDenyAllPermissions($categoryId);
            foreach ($selectedGroupIds as $groupId => $permission) {
                $this->parentUserGroupResourceModel->removeCategoryFromCustomerGroup($groupId, $categoryId);
            }
            return true;
        }

        $tablePermissionGroupIds = [];
        foreach ($permissionCollection->getItems() as $categoryPermission) {
            $tablePermissionGroupIds[$categoryPermission->getCustomerGroupId()] = $categoryPermission->getPermission();
        }

        $selectedGroupIds = array_diff_assoc($selectedGroupIds, $tablePermissionGroupIds);
        $allowedPermissions = $deniedPermissions = [];

        foreach ($selectedGroupIds as $groupId => $permission) {
            if ($permission == Permission::PERMISSION_ALLOW) {
                $allowedPermissions[] = $groupId;
            } elseif ($permission == Permission::PERMISSION_DENY) {
                $deniedPermissions[] = $groupId;
            }
        }

        if (!empty($allowedPermissions)) {
            $this->processAllowPermissions($categoryId, $allowedPermissions, Permission::PERMISSION_ALLOW);

            $this->folderPermission->insertDenyAllPermissions([$categoryId], static::SHAREDCATALOG_PERMISSION_TABLE);
            $this->folderPermission->insertDenyAllPermissions([$categoryId], static::MAGENTOCATALOG_PERMISSION_TABLE);
        }

        if (!empty($deniedPermissions)) {
            $this->processDenyPermissions($categoryId, $deniedPermissions);

            if (empty($allowedPermissions) && $isFolderRestricted) {
                $this->folderPermission->insertDenyAllPermissions(
                    [$categoryId],
                    static::SHAREDCATALOG_PERMISSION_TABLE
                );
                $this->folderPermission->insertDenyAllPermissions(
                    [$categoryId],
                    static::MAGENTOCATALOG_PERMISSION_TABLE
                );
            }
        }

        if (!empty($allowedPermissions) || !empty($deniedPermissions)) {
            $updatedCategories[] = $categoryId;
            $this->sharedCatalogInvalidation->reindexCatalogPermissions($updatedCategories);
        }

        if (!empty($selectedGroupIds)) {
            foreach ($selectedGroupIds as $groupId => $permission) {
                if ($permission == Permission::PERMISSION_ALLOW) {
                    $this->parentUserGroupResourceModel->addCategoryToCustomerGroup($groupId, $categoryId);
                } else {
                    $this->parentUserGroupResourceModel->removeCategoryFromCustomerGroup($groupId, $categoryId);
                }
            }
        }

        return true;
    }

    /**
     * Shared helper for processing deny permissions
     *
     * @param int $categoryId
     * @param array $groupIds
     *
     * @return void
     */
    private function processDenyPermissions(int $categoryId, array $groupIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            static::MAGENTOCATALOG_PERMISSION_TABLE,
            ['category_id = ?' => $categoryId, 'customer_group_id IN (?)' => $groupIds]
        );
        $connection->delete(
            static::SHAREDCATALOG_PERMISSION_TABLE,
            ['category_id = ?' => $categoryId, 'customer_group_id IN (?)' => $groupIds]
        );
    }

    /**
     * Shared helper for processing allow permissions
     *
     * @param int $categoryId
     * @param array $groupIds
     * @param int $permissionType
     */
    private function processAllowPermissions(int $categoryId, array $groupIds, int $permissionType): void
    {
        foreach ($groupIds as $groupId) {
            $permissionItem =
                $this->catalogPermissionManagement->getSharedCatalogPermission($categoryId, null, $groupId);
            $permissionItem->setPermission($permissionType);
            $this->sharedCatalogPermissionResource->save($permissionItem);
        }

        $this->permissionsSynchronizer->updateCategoryPermissions($categoryId, $groupIds);
    }

    /**
     * Remove deny all permission for category
     *
     * @param int $categoryId
     *
     * @return void
     */
    private function removeDenyAllPermissions(int $categoryId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            static::MAGENTOCATALOG_PERMISSION_TABLE,
            ['category_id = ?' => $categoryId, 'customer_group_id IS NULL']
        );
        $connection->delete(
            static::SHAREDCATALOG_PERMISSION_TABLE,
            ['category_id = ?' => $categoryId, 'customer_group_id IS NULL']
        );
    }
}
