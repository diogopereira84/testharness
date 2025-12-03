<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RemoveV3LogoutToggle implements DataPatchInterface
{
    // core config data table unused key
    public const CORE_CONFIG_DATA_KEY = [
        'tiger_fcl_logout_url_toggle'
    ];

    /**
     * @param SchemaSetupInterface $setup
     */
    public function __construct(
        private SchemaSetupInterface $setup
    )
    {
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

    /**
     * Applies the data patch to remove Xmen toggle keys from the core_config_data table.
     *
     * @return void
     */
    public function apply()
    {
        $defaultConnection = $this->setup->getConnection();
        $toggleFieldPath = 'environment_toggle_configuration/environment_toggle/';

        foreach (self::CORE_CONFIG_DATA_KEY as $coreConfigKey) {
            $defaultConnection->delete(
                $this->getTableNameWithPrefix($this->setup, 'core_config_data'),
                "path = '$toggleFieldPath$coreConfigKey'"
            );
        }
    }

    /**
     * Gets the table name with prefix.
     *
     * @param object $setup
     * @param string $tableName
     * @return string
     */
    private function getTableNameWithPrefix(SchemaSetupInterface $setup, $tableName)
    {
        return $setup->getTable($tableName);
    }
}
