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

class UpdateSiteLevelPaymentTooltip implements DataPatchInterface
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

        $enhanceRolePermissionCollection = $this->enhanceRolePermission->getCollection()->addFieldToFilter(
                "label",
                ['eq' => 'Site Level Payments::shared_credit_cards']
            );
        $enhanceRolePermissionFactory = $this->enhanceRolePermissionFactory->create();
        if ($enhanceRolePermissionCollection->getSize()) {
            foreach ($enhanceRolePermissionCollection as $enhanceRolePermission) {
                $enhanceRolePermissionFactory->load($enhanceRolePermission->getId());
                $enhanceRolePermissionFactory->setTooltip('Users who are given this permission will have access to the Site Level Payments section in My Profile; this will allow them to add, remove and edit payment information for this site.');
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
        return [
            \Fedex\EnhancedProfile\Setup\Patch\Data\InsertSiteSettingsPermission::class
        ];
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
