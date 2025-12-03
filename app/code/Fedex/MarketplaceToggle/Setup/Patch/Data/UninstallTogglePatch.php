<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceToggle\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UninstallTogglePatch implements DataPatchInterface
{
    /**
     * Xpath marketplace checkout flag.
     */
    private const XPATH_ENABLE_MKT_CHECKOUT =
        'environment_toggle_configuration/environment_toggle/enable_marketplace_checkout';

    /**
     * Xpath transactional emails toggle flag
     */
    private const XPATH_TRANSACTIONAL_EMAILS_TOGGLE =
        'environment_toggle_configuration/environment_toggle/enable_transactional_emails';

    /**
     * Xpath strip tags emails toggle flag
     */
    private const STRIP_TAGS_TRANSACTIONAL_EMAILS_TOGGLE =
        'environment_toggle_configuration/environment_toggle/strip_tags_transactional_emails';

    /**
     * Xpath enable rounding of navitor prices for mirakl
     */
    private const XPATH_PRICE_ROUNDING_ENABLED =
        'environment_toggle_configuration/environment_toggle/enable_price_rounding';

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
            self::XPATH_ENABLE_MKT_CHECKOUT,
            self::XPATH_TRANSACTIONAL_EMAILS_TOGGLE,
            self::STRIP_TAGS_TRANSACTIONAL_EMAILS_TOGGLE,
            self::XPATH_PRICE_ROUNDING_ENABLED
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
