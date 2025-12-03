<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\EnhancedProfile\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;
use Fedex\SelfReg\Model\EnhanceRolePermission;

class InsertSiteSettingsPermission implements DataPatchInterface
{
    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EnhanceRolePermissionFactory $enhanceRolePermissionFactory
     * @param EnhanceRolePermission $enhanceRolePermission
     */
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup,
        private EnhanceRolePermissionFactory $enhanceRolePermissionFactory,
        private EnhanceRolePermission $enhanceRolePermission
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
            'label' => 'Site Settings::site_settings',
            'sort_order' => '6',
            'tooltip' => "Users with this permission will be able to access the 'Site Settings' tab to change the site settings."
        ];
        $enhanceRolePermissionFactory = $this->enhanceRolePermissionFactory->create();
        $enhanceRolePermissionFactory->setData($permisisonData)->save();

        $enhanceRolePermissionCollection = $this->enhanceRolePermission->getCollection()->addFieldToFilter(
                "label",
                ['eq' => 'Shared Credit Cards::shared_credit_cards']
            );
        if ($enhanceRolePermissionCollection->getSize()) {
            foreach ($enhanceRolePermissionCollection as $enhanceRolePermission) {
                $enhanceRolePermissionFactory->load($enhanceRolePermission->getId());
                $enhanceRolePermissionFactory->setLabel('Site Level Payments::shared_credit_cards');
            }
            $enhanceRolePermissionFactory->save();
        }
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
