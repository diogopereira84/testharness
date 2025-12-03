<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Service;

use Magento\Catalog\Model\Product\Action;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory as CategoryPermissionCollectionFactory;

class AllowedCustomerGroupsService
{
    private const ADMIN_STORE_ID = 0;
    private const ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE = 'allowed_customer_groups';

    public function __construct(
        protected CategoryPermissionCollectionFactory $categoryPermissionCollectionFactory,
        protected Action $productAction
    ) {}

    /**
     * Get allowed customer groups from categories.
     *
     * @param array $categories
     * @return array
     */
    public function getAllowedCustomerGroupsFromCategories(array $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        $permissionsCollection = $this->categoryPermissionCollectionFactory->create()
            ->addFieldToFilter('category_id', ['in' => $categories])
            ->addFieldToFilter('grant_catalog_category_view', Permission::PERMISSION_ALLOW)
            ->addFieldToSelect('customer_group_id');
        $permissionsCollection->getSelect()->group('customer_group_id');

        $customerGroupIds = $permissionsCollection->getColumnValues('customer_group_id');

        if (!empty($customerGroupIds) && in_array(null, $customerGroupIds)) {
            return ['-1']; // '-1' represents all groups
        }

        return $customerGroupIds;
    }

    /**
     * Get allowed categories from Customer Group.
     *
     * @param int $customerGroupId
     * @return array
     */
    public function getAllowedCategoriesFromCustomerGroup(int $customerGroupId): array
    {
        if (!$customerGroupId) {
            return [];
        }

        $permissionsCollection = $this->categoryPermissionCollectionFactory->create();
        $permissionsCollection->addFieldToFilter('customer_group_id', $customerGroupId);
        $permissionsCollection->addFieldToFilter('grant_catalog_category_view', Permission::PERMISSION_ALLOW);
        $permissionsCollection->addFieldToSelect('category_id');
        $categoryIds = [];
        foreach ($permissionsCollection as $permission) {
            $categoryIds[] = $permission->getCategoryId();
        }

        return array_filter(array_unique($categoryIds));
    }

    /**
     * @param $products
     * @param $allowedValue
     * @return void
     */
    public function updateAttributes($products, $allowedValue): void
    {
        $this->productAction->updateAttributes(
            $products,
            [self::ALLOWED_CUSTOMER_GROUPS_ATTRIBUTE => $allowedValue],
            self::ADMIN_STORE_ID
        );
    }
}

