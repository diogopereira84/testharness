<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\OKTA\Model\UserRole\RoleHandler;

class ProcessNewRoles implements DataPatchInterface
{
    /**
     * ProcessRoles constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RoleHandler $roleHandler
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private RoleHandler $roleHandler
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->roleHandler->processNewRole();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
