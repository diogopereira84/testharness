<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Sourav Nayak <sourav.nayak.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class ToggleKeysRemove implements DataPatchInterface
{
    // core config data table unused key
    const CORE_CONFIG_DATA_KEY = [
        'explorers_shipping_form_load_blank_fix',
        'explorers_save_cc_fix',
        'explorers_dunc_image_Load_issue',
        'explorers_d146371_hide_shipping_form_fields',
        'explorers_shipping_method_name_fix_order_success',
        'explorers_fix_account_remove_issue',
        'explorers_set_previous_quote_id_null',
        'explorers_sde_checkout_breadcrumb_fix',
        'explorers_D_144639_fix',
        'explorers_display_company_level_credit_card_fix',
        'explorers_d_152542_fix',
        'explorers_remove_accountnumber_fix',
        'explorers_create_shipment_fix',
        'explorers_use_previous_quote_id',
        'explorers_customer_uuid_lookup',
        'explorers_D_148529_fix',
        'explorers_ratecall_epro'
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
