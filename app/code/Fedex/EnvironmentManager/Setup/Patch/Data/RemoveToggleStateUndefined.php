<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Manish Chaubey <manish.chaubey.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RemoveToggleStateUndefined implements DataPatchInterface
{
    // core config data table unused key
    const CORE_CONFIG_DATA_KEY = [
        'tiger_D_175562_state_is_undefined_for_billing_address_on_order_success_page'
    ];

    /**
     * @param SchemaSetupInterface $setup
     */
    public function __construct(
        protected SchemaSetupInterface $setup
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    private function getTableNameWithPrefix(SchemaSetupInterface $setup, $tableName)
    {
        return $setup->getTable($tableName);
    }
}
