<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceToggle\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class CleanupMarketplaceToggles implements DataPatchInterface
{
    /**
     * Xpath webhook enhancement toggle.
     */
    private const XPATH_WEBHOOK_TOGGLE =
        'environment_toggle_configuration/environment_toggle/shark_tk_3043865';

    /**
     * Xpath enable MKT shipping methods toggle.
     */
    private const XPATH_MARKETPLACE_MOCKS_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_marketplace_mocks';

    /**
     * Xpath admin updates toggle.
     */
    private const XPATH_ADMIN_UPDATES_TOGGLE =
        'environment_toggle_configuration/environment_toggle/shark_tk_3043865_admin_updates';

    /**
     * Xpath shipping sorting toggle.
     */
    private const XPATH_SHIPPING_SORTING_TOGGLE =
        'environment_toggle_configuration/environment_toggle/shark_tk_3043865_shipping_sorting';

    /**
     * Xpath queue send mirakl toggle.
     */
    private const XPATH_QUEUE_SEND_MIRAKL_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_queued_order_sent_to_mirakl';

    /**
     * Xpath enable marketplace toggle.
     */
    private const XPATH_ENABLE_MARKETPLACE_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_marketplace';

    /**
     * Xpath enable selfreg receipt enhancement toggle.
     */
    private const XPATH_ENABLE_SELFREG_RECEIPT_ENHANCEMENT_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_marketplace_epro_selfreg_receipt_enhancement';

    /**
     * Xpath enable selfreg history enhancement toggle.
     */
    private const XPATH_ENABLE_SELFREG_HISTORY_ENHANCEMENT_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_marketplace_epro_selfreg_history_enhancement';

    /**
     * Xpath enable check availability retail toggle.
     */
    private const XPATH_ENABLE_CHECK_AVAILABILITY_RETAIL_TOGGLE =
        'environment_toggle_configuration/environment_toggle/check_product_availability_only_in_retail';

    /**
     * Xpath discontinue 1P Premium product sales toggle.
     */
    private const XPATH_PREMIUM_1P_DISCONTINUE_SALE_TOGGLE =
        'environment_toggle_configuration/environment_toggle/premium_product_1p_discontinue_sale';

    /**
     * Xpath payment cvv tooltip config.
     */
    private const XPATH_PAYMENT_CVV_TOOLTIP = 'fedex/marketplace_configuration/payment_cvv_tooltip';

    /**
     * Xpath 1P enable premium product message config.
     */
    private const XPATH_1P_PREMIUM_ENABLE_PRODUCT_MESSAGE
        = 'fedex/marketplace_configuration/1p_enabled_premium_product_message';

    /**
     * Xpath message for disable premium products 1p order config.
     */
    private const XPATH_1P_PREMIUM_DISABLE_PRODUCT_MESSAGE
        = 'fedex/marketplace_configuration/1p_disabled_premium_product_message';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * @return void
     */
    public function apply()
    {
        $configPathsToRemove = [
            self::XPATH_WEBHOOK_TOGGLE,
            self::XPATH_ADMIN_UPDATES_TOGGLE,
            self::XPATH_SHIPPING_SORTING_TOGGLE,
            self::XPATH_QUEUE_SEND_MIRAKL_TOGGLE,
            self::XPATH_ENABLE_MARKETPLACE_TOGGLE,
            self::XPATH_ENABLE_SELFREG_RECEIPT_ENHANCEMENT_TOGGLE,
            self::XPATH_ENABLE_SELFREG_HISTORY_ENHANCEMENT_TOGGLE,
            self::XPATH_ENABLE_CHECK_AVAILABILITY_RETAIL_TOGGLE,
            self::XPATH_PREMIUM_1P_DISCONTINUE_SALE_TOGGLE,
            self::XPATH_PAYMENT_CVV_TOOLTIP,
            self::XPATH_1P_PREMIUM_ENABLE_PRODUCT_MESSAGE,
            self::XPATH_1P_PREMIUM_DISABLE_PRODUCT_MESSAGE,
            self::XPATH_MARKETPLACE_MOCKS_TOGGLE
        ];

        foreach ($configPathsToRemove as $configPath) {
            $this->moduleDataSetup->getConnection()->delete(
                $this->moduleDataSetup->getTable('core_config_data'),
                ['path = ?' => $configPath]
            );
        }
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
