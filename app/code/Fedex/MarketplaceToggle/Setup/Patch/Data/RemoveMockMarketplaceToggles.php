<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceToggle\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RemoveMockMarketplaceToggles implements DataPatchInterface
{
    private $moduleDataSetup;

    /**
     * Xpath marketplace mock enable toggle.
     */
    private const XPATH_MARKETPLACE_ENABLE_MOCK_TOGGLE =
        'fedex/marketplace_configuration/enable_marketplace_mocks';

    /**
     * Xpath marketplace shipping mock account number.
     */
    private const XPATH_MARKETPLACE_SHIPPING_ACCOUNT_NUMBER_TOGGLE =
        'fedex/marketplace_configuration/mock_customer_shipping_account_third_party';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $configPathsToRemove = [
            self::XPATH_MARKETPLACE_ENABLE_MOCK_TOGGLE,
            self::XPATH_MARKETPLACE_SHIPPING_ACCOUNT_NUMBER_TOGGLE
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
