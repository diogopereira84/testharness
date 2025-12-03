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

class RemoveMazegeeksToggleKeys implements DataPatchInterface
{
    // core config data table unused key
    const CORE_CONFIG_DATA_KEY = [
        'active_quote_update_cron',
        'download_catalog_items',
        'catalog_mvp_custom_docs',
        'url_extension_append_in_commercial',
        'punchout_new_identifier',
        'epro_fix_customdoc_edit',
        'eprofix_sameproduct_addtocart',
        'rate_api_failure_exception',
        'punchout_external_identifier',
        'epro_pickup_autofill',
        'epro_ponumber_length',
        'removed_round_off_for_product_price',
        'ordersubmission_non_json',
        'epro_approval_quote_skip',
        'total_num_product_count',
        'menu_optimization',
        'extra_po_number_validation',
        'mazegeeks_remove_localstorage',
        'mazegeeks_set_shipment_po_null',
        'company_store_and_storeview_restructure',
        'fix_shipping_results',
        'fix_catalog_permission',
        'sort_commercial_top_menu',
        'mazegeek_print_screen_product_img_preview',
        'catalog_mvp_ctc_admin',
        'catalog_mvp_customer_admin',
        'mazegeek_store_code_from_quote',
        'mazegeeks_save_product_with_same_name',
        'mazegeeks_d_165989_fix',
        'document_preview_api',
        'mazegeeks_profile_api_failure_messaging',
        'mazegeeks_shipping_method_default_address',
        'mazegeeks_catalog_mvp_cloud_drive',
        'asynchronous_call_extend_document_lifetime',
        'url_extension_causing_create_issue_fixed',
        'magegeeks_new_login_extension',
        'mazegeeks_selfreg_wlgn_login',
        'mazegeeks_product_save_attribute_set',
        'mazegeeks_filter_non_editable_products',
        'maze_geeks_hide_category_box',
        'mazegeeks_epro_order_submission_shipping_state',
        'mazegeek_commercial_duplicate_order_status',
        'order_status_update_log',
        'mazegeeks_status_update',
        'catalog_performance',
        'fix_cxml_quote_count',
        'fix_order_store_id',
        'fix_epro_name_length',
        'enable_epro_homepage',
        'mazegeeks_catalogmvp_hot_fix',
        'maze_geeks_D114405_Production_Location_Get_Reset'
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
        $defaultConnection->delete(
            $this->getTableNameWithPrefix($this->setup, 'core_config_data'),
            "path = 'fedex/my_account_menu_options/maze_geeks_D114405_Production_Location_Get_Reset'");
    }

    /**
     * @inheritDoc
     */
    private function getTableNameWithPrefix(SchemaSetupInterface $setup, $tableName)
    {
        return $setup->getTable($tableName);
    }
}
