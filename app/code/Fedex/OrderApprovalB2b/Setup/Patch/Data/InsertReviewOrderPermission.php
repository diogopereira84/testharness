<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\OrderApprovalB2b\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;

class InsertReviewOrderPermission implements DataPatchInterface
{
    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EnhanceRolePermissionFactory $enhanceRolePermission
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        private EnhanceRolePermissionFactory $enhanceRolePermission
    )
    {
    }

    /**
     * Add eav attributes
     */
    public function apply()
    {
        $installer = $this->moduleDataSetup;
        $installer->startSetup();
        $permisisonData = [
            'label' => 'Review Orders::review_orders',
            'sort_order' => '5',
            'tooltip' => "Users with this permission will be able to access the 'Review Orders' tab to approve, decline and review order for this site."
        ];

        $this->enhanceRolePermission->create()->setData($permisisonData)->save();

        $installer->endSetup();
    }

    /**
     * Returns the dependencies for this data patch.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Returns the aliases for this data patch.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
