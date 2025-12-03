<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Magento\Customer\Model\Group;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CollectionFactory as PermissionsCollectionFactory;
use Fedex\SelfReg\Model\ResourceModel\ParentUserGroup\CollectionFactory as ParentUserGroupCollectionFactory;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\CatalogPermissions\Model\Permission;
use Magento\SharedCatalog\Model\Permission as SharedCatalogPermission;

class CustomerGroupPermissionManager
{
    /**
     * CustomerGroupPermissionManager constructor
     *
     * @param Group $groupModel
     * @param PermissionsCollectionFactory $sharedCatalogPermissionCollectionFactory
     * @param DeliveryHelper $deliveryHelper
     * @param ParentUserGroupCollectionFactory $collectionFactory
     * @param CatalogPermissionManagement $catalogPermissionManagement
     */
    public function __construct(
        private Group $groupModel,
        private PermissionsCollectionFactory $sharedCatalogPermissionCollectionFactory,
        private DeliveryHelper $deliveryHelper,
        private ParentUserGroupCollectionFactory $collectionFactory,
        private CatalogPermissionManagement $catalogPermissionManagement
    ) {
    }

    /**
     * Get a list of all customer groups.
     *
     * @return array
     */
    public function getCustomerGroupsList(): array
    {
        $customer = $this->deliveryHelper->getCustomer();
        $company = $this->deliveryHelper->getAssignedCompany($customer);
        $parentGroupId = $company->getCustomerGroupId();

        $parentUserGroupCollection = $this->collectionFactory->create();
        $parentUserGroupCollection->getSelect()->join(
            ['customer_group'],
            'main_table.customer_group_id = customer_group.customer_group_id',
            ['customer_group_code']
        );
        $parentUserGroupCollection->addFieldToFilter('parent_group_id', $parentGroupId);

        $customerGroupsData = $parentUserGroupCollection->getData();
        $customerGroupsList = [];
        foreach ($customerGroupsData as $group) {
            $customerGroupsList[$group['customer_group_id']]['customer_group_code'] = $group['customer_group_code'];
        }

        return $customerGroupsList;
    }

    /**
     * Get customer group permissions for a specific category.
     *
     * @param int $categoryId The category ID.
     * @return array
     */
    public function getUserGroupsByCategory(int $categoryId): array
    {
        $customerGroupsList = $this->getCustomerGroupsList();

        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection->addFieldToFilter(
            SharedCatalogPermission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
            ['in' => array_keys($customerGroupsList)]
        )->addFieldToFilter(
            SharedCatalogPermission::SHARED_CATALOG_PERMISSION_CATEGORY_ID,
            $categoryId
        );

        /** @var SharedCatalogPermission[] $sharedCategoryPermissions */
        $sharedCategoryPermissions = $permissionCollection->getItems();

        foreach ($sharedCategoryPermissions as $sharedCategoryPermission) {
            $groupId = $sharedCategoryPermission->getCustomerGroupId();
            if (isset($customerGroupsList[$groupId])) {
                $customerGroupsList[$groupId]['permission'] = $sharedCategoryPermission->getPermission();
            }
        }

        if (count($customerGroupsList) !== count($sharedCategoryPermissions)) {
            $permissionType = $this->doesDenyAllPermissionExist($categoryId) ? Permission::PERMISSION_DENY :
                Permission::PERMISSION_ALLOW;

            foreach ($customerGroupsList as $groupId => $groupData) {
                if (!isset($groupData['permission'])) {
                    $customerGroupsList[$groupId]['permission'] = $permissionType;
                }
            }
        }

        return $customerGroupsList;
    }

    /**
     * Get user groups of current user
     *
     * @return array
     */
    public function getCurrentUserGroupsList()
    {
        $customer = $this->deliveryHelper->getCustomer();
        $company = $this->deliveryHelper->getAssignedCompany($customer);
        $parentGroupId = $company->getCustomerGroupId();

        $parentUserGroupCollection = $this->collectionFactory->create();
        $parentUserGroupCollection->getSelect()->join(
            ['customer_group'],
            'main_table.customer_group_id = customer_group.customer_group_id',
            ['customer_group_code']
        );
        $parentUserGroupCollection->addFieldToFilter('parent_group_id', $parentGroupId);

        $customerGroupsData = $parentUserGroupCollection->getData();
        $customerGroupsList = [$parentGroupId => 'Default'];
        foreach ($customerGroupsData as $group) {
            $customerGroupsList[$group['customer_group_id']] = $group['customer_group_code'];
        }
        return $customerGroupsList;
    }

    /**
     * Checks if a deny all permission exists for a specific category.
     *
     * @param int $categoryId
     *
     * @return bool
     */
    public function doesDenyAllPermissionExist(int $categoryId): bool
    {
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection->addFieldToFilter(
            SharedCatalogPermission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
            ['null' => true]
        )->addFieldToFilter(
            SharedCatalogPermission::SHARED_CATALOG_PERMISSION_CATEGORY_ID,
            $categoryId
        );

        return $permissionCollection->count() > 0;
    }

    /**
     * Get all user groups that have allowed permissions for a certain category
     *
     * @param string $categoryId
     * @param array $userGroups
     *
     * @return array
     */
    public function getAllowedGroups(string $categoryId, array $userGroups): array
    {
        $permissionCollection = $this->sharedCatalogPermissionCollectionFactory->create();
        $permissionCollection->addFieldToFilter(
            SharedCatalogPermission::SHARED_CATALOG_PERMISSION_CATEGORY_ID,
            $categoryId
        )->addFieldToFilter(
            SharedCatalogPermission::SHARED_CATALOG_PERMISSION_CUSTOMER_GROUP_ID,
            ['in' => array_keys($userGroups)]
        );

        $allowedGroups = [];
        $allowedGroupsData = $permissionCollection->getData();

        foreach ($allowedGroupsData as $group) {
            if ($group['permission'] == Permission::PERMISSION_ALLOW) {
                $allowedGroups[] = $group['customer_group_id'];
            }
        }

        return $allowedGroups;
    }
}
