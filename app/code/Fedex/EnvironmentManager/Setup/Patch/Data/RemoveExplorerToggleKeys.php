<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Sourav Nayak <Sourav.Nayak.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RemoveExplorerToggleKeys implements DataPatchInterface
{
    // core config data table unused key
    const CORE_CONFIG_DATA_KEY = [
        'explorers_megamenu_categories_fix',
        'explorers_d_162048_fix',
        'displaying_send_order_confirmation_email_log',
        'explorers_d_164473_fix',
        'explorers_commercial_reporting',
        'explorers_d_165914_fix',
        'explorers_enable_disable_editable_fxo_payment_account',
        'explorers_enable_disable_discount',
        'armada_enable_early_shipping_account'
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
