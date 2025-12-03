<?php


declare(strict_types=1);

namespace Fedex\Purchaseorder\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class ToggleKeysRemoval implements DataPatchInterface
{
    // core config data table unused key
    const CORE_CONFIG_DATA_KEY = [
        'epro_fix_gtn_admin',
       'fix_epro_name_validation'
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
