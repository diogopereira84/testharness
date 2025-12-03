<?php
/**
 * @category  Fedex
 * @package   Fedex_EnvironmentManager
 * @author    Bhairav Singh <bhairav.singh.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class RemoveCoreConfigUnUsedRows implements DataPatchInterface
{
    // core config data table unused key
    const CORE_CONFIG_DATA_KEY = [
        'enable_fcl',
        'enable_shared_catalog_cat_validation',
        'enable_catalogsync_view_details_page',
        'enable_catalog_sync_remove_data',
        'keep_success_page_after_refresh',
        'enable_production_location',
        'enable_document_description',
        'enable_catalog_sync_delete_item_permanently',
        'enable_delete_item_without_category',
        'checkout_pickup_location_time',
        'enable_fedex_shipping_account_number',
        'enable_checkout_terms_condition',
        'enable_oms_new_status_cron',
        'enable_menu_categories_without_indexer',
        'enable_catalog_sync_without_curl',
        'enable_catalog_sync_categories_products_remove',
        'enable_duplicate_order_check',
        'enable_dynamic_state_for_pickup',
        'save_retail_transection_id',
        'checkout_gtn_order_number',
        'order_history_enable',
        'enable_gdl',
        'validate_fedex_account_number',
        'enable_catalog_categories_queue_sync',
        'enable_catalog_sync_products_move',
        'enable_canava_inside_fedex',
        'enable_shared_catalog_category_mapping',
        'enable_pcikup_order_not_submit_issue_fix',
        'enable_fcl_checkout_modal',
        'coupon_code_validation',
        'enable_quote',
        'enable_quote_type',
        'enable_quote_reference',
        'account_mask_toogle',
        'enable_sde',
        'enable_forsta_contentsquare',
        'feature_toggle_status_for_pickup',
        'feature_toggle_status_for_shipment',
        'ship_fields_length_validation',
        'rpi_mix_cart_product_warning',
        'is_out_sourced',
        'remove_shipping_step_console_error',
        'is_po_reference_id',
        'is_retail_configuration',
        'enable_hco_location',
        'primary_contact_upadate_issue',
        'override_email_address',
        'primary_contact_update_issue',
        'invalid_payment_data_json',
        'fcl_customer_duplicate',
        'oms_email_trigger_logic_change',
        'remove_shipping_step_console_error_epro',
        'kpi_adobe_analytics',
        'enable_fcl_modal_redirected_on_checkout',
        'checkout_console_js_errors_fixes_pi_02_sp1',
        'oms_email_adobe_change',
        'retail_orders_history',
        'taz_token_cache_identifier',
        'po_number_failure_fix',
        'checkout_card_validation_message',
        'enable_promobanner',
        'enable_promobanner_type',
        'enable_promobanner_reference',
        'delivery_api_bypass',
        'item_total_roundoff',
        'checkout_pickship_display_issue_fix',
        'pop_up_modal_toggle',
        'oms_fields_length_fix',
        'cc_no_free_delivery_fix',
        'shipping_option_select_issue_fix',
        'rate_modified_in_review_screen',
        'order_history_enhancement_enable',
        'item_total_mismatch',
        'product_missing_session_timeout',
        'xmen_sprint1_checkout_console_js_error',
        'kpi_event_adobe_analytics',
        'retail_expends_order_details',
        'rate_call_pricing_issue',
        'fix_missing_shipping_info',
        'xmen_s2_checkout_console_js_error',
        'fix_missing_shipping_info_afterQuoteToOrder',
        'checkout_console_js_errors_fixes_pi_02_sp2',
        'retail_view_receipt',
        'enable_sde_commercial_homepage',
        'checkout_continue_payment_button_stuck_error',
        'enable_enhancedprofile',
        'enable_enhancedprofile_type',
        'enable_enhancedprofile_reference',
        'ext_number_fix',
        'rate_quote_user_references_number_error',
        'checkout_cc_no_validation_error',
        'enable_hco_price_update',
        'checkout_console_js_errors_fixes_pi_02_sp3',
        'xmen_checkout_console_js_errors_sprint22_2_3',
        'estimated_pickup_time_save',
        'xmen_validate_contact_info',
        'xmen_validate_contact_info_log',
        'fxo_promo_code_placement',
        'old_promo_coupon_code_display',
        'new_promo_coupon_code_display',
        'receipt_label_change_error_fix',
        'xmen_price_mismatch',
        'po_ship_fields_length_validation',
        'xmen_oms_address_byte_length_validation',
        'xmen_shipping_account_placement',
        'credit_cards_list',
        'can_include_signature_options',
        'fedex_accounts_list',
        'checkout_fedex_account_number_validations',
        'retail_order_history_reorder',
        'xmen_fcl_customer_length_fix',
        'xmen_populating_shipping_address',
        'fix_shipping_description_value',
        'fix_orderhistory_item_discount_value',
        'fix_email_new_line_character_issue',
        'order_history_view_page_shipping_recipient_issue',
        'enhanced_profile_session',
        'order_creation_per_required_feedback_from_cj',
        'xmen_tcxs_checkout_billing_field_validation',
        'epro_iframe_session_issue_fix',
        'xmen_email_date_completion_timezone',
        'epro_fix_addressclassification',
        'xmen_retail_hco_location',
        'reorder_instance_save',
        'fcl_customer_email_integrity_issue_fix',
        'shipping_continue_button',
        'order_success_page_button_alignment_issue_fix',
        'enable_commercial_header_footer',
        'xmen_enable_profile_page_ada',
        'enable_additional_item_details_order_confirm_page',
        'b_1245427_js_errors_array_from',
        'xmen_order_exist_same_quote',
        'tiger_taz_token_management',
        'xmen_prevent_redirection_for_epro',
        'd_99511_db_enhancement',
        'b_1182751_cannot_read_property_null_remove_class',
        'clean_product_item_instance',
        'populate_creditcard_fedexaccount',
        'xmen_fcl_customer_phone_length',
        'order_summary_shipping_data_rendering_fix_with_line_items',
        'xmen_profile_api_response_status',
        'xmen_missing_shipping_option',
        'enable_sde_pod_changes',
        'xmen_fcl_checkout_timeout',
        'tiger_d103922_access_token_pod',
        'xmen_express_checkout',
        'enable_sde_sso_refactor',
        'epro_order_history_reorder',
        'tiger_b1346408_tax_exempt_modal_copy_change',
        'tiger_b1343810_third_party_modal_copy_change',
        'mazegeek_checkout_enable_for_selfreg_customer',
        'explorers_search_radius_box',
        'xmen_shipping_row',
        'xmen_email_duplicacy',
        'enable_sde_site_name_change',
        'explorers_fix_set_order_id_issue',
        'sde_active_session_timeout',
        'tiger_B1330216_contentSquare_data_mapping_correction',
        'tiger_D102900_qty_error_messaging_persistence',
        'tiger_d_110679_blank_configurator_page',
        'tiger_E303770_fxo_payment_account_validation_error_messaging',
        'tiger_e_308528_custom_post_data_app_dynamics',
        'xmen_display_second_line_address',
        'd_83512_adobe_analytics_online_notary_page',
        'system_error_log_disable',
        'tiger_d102905_entered_qty_and_priced_qty_mismatch',
        'active_store_redirection',
        'd_83521_adobe_analytics_online_notary_page',
        'tiger_b_1409595_my_account_menu_option',
        'xmen_mobile_site_remediation',
        'tiger_d113195_jwt_token_being_sent_as_undefined',
        'tiger_d_115292_error_null_custom_attributes',
        'tiger_d_115030_js_unrecognized_expression',
        'sgc_b1392314_pass_tax_exempt_modal_data',
        'tiger_b1448855_content_square_mapping',
        'tiger_d116198_js_error_cannot_read',
        'tiger_d115679_js_error_instance_id',
        'explorers_epro_pickup_submission',
        'refactor_reset_coupon_code_function',
        'tiger_d113531_conflicting_product_engine_attribute_id',
        'explorers_40_cart_warning',
        'tiger_d116190_js_error_closemodal',
        'tiger_b1473592_integrate_pod_2_admin_with_purple_id',
        'xmen_wrong_label_name',
        'explorers_shipping_cost_fix',
        'tigerteam_b1504826_newrelic_transaction_custom_attribute_for_epro_customer_pageview',
        'explorers_higher_price_display_fix',
        'explorers_dunc_call_optimization',
        'explorers_ground_delivery',
        'xmen_profile_api_change',
        'explorers_modified_pickup_date_time_format',
        'explorers_address_search_for_pickup',
        'explorers_fix_address_warning',
        'explorers_fix_for_mobile_home_page',
        'explorers_fix_orderSubmit_discount_issue',
        'instore_send_pickup_location_date_on_fujitsu_request',
        'explorers_shipping_missing_data_from_checkout_session',
        'explorers_requested_pickuptime',
        'instore_send_notes_on_fujitsu_request',
        'explorers_credit_card_validation',
        'explorers_estimate_shipping_result_fix',
        'explorers_combine_warning_message',
        'explorers_tabs_click_fix',
        'skip_pod_category',
        'explorers_oms_length_fix',
        'armada_create_order_db_rollback',
        'armada_log_printing_test',
        'remove_space_from_name',
        'enable_catalog_sync_category_name_update',
        'tiger_b1382174_new_relic_custom_attribute',
        'explorers_is_search_issue_fix'
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
