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

class DeleteNewRoles implements DataPatchInterface
{
    private const  MARKETING_LIMITED_WRITE= 'Marketing Limited Write';
    private const  CTC_SUPER_USER= 'CTC Super User';

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
        $this->roleHandler->deleteRoleFromName(static::MARKETING_LIMITED_WRITE);
        $this->roleHandler->deleteRoleFromName(static::CTC_SUPER_USER);
        $this->roleHandler->processNewAfterDeleteRole();
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
