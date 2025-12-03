<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerGroup\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\SelfReg\Block\User\Search;
use Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\CollectionFactory as RolePermissionCollectionFactory;

class AssignPermissions implements ArgumentInterface
{
    /**
     * @param Search $search
     * @param RolePermissionCollectionFactory $rolePermissionCollectionFactory
     */
    public function __construct(
        protected Search $search,
        protected RolePermissionCollectionFactory $rolePermissionCollectionFactory
    ) {
    }

    /**
     * Get all permissions available for users
     *
     * @return Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\Collection
     */
    public function getAllRolePermission()
    {
        $collection = $this->rolePermissionCollectionFactory->create();
        $collection->setOrder('sort_order', 'ASC');

        return $collection;
    }
}
