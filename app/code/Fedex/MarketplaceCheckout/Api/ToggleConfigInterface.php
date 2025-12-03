<?php
/**
 * Interface ConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_SHIPPING_MANAGEMENT_REFACTOR =
        'environment_toggle_configuration/environment_toggle/tiger_e467599';
    public const XML_PATH_ENABLE_MARKETPLACE_COMMERCIAL =
        'environment_toggle_configuration/environment_toggle/tiger_tk_410245';
    public const XML_PATH_INCORRECT_SHIPPING_TOTALS =
        'environment_toggle_configuration/environment_toggle/tiger_d213977';

    public const XML_PATH_INCORRECT_PACKAGE_COUNT =
        'environment_toggle_configuration/environment_toggle/tiger_d234051';

    /**
     * Gets toggle status for ShippingMethodManagementPlugin.php refactor
     *
     * @param string $scope
     * @return bool
     */
    public function isShippingManagementRefactorEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * Gets toggle status for Marketplace enabled on Commercial sites
     *
     * @param string $scope
     * @return bool
     */
    public function isMarketplaceEnabledForCommercialSites(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * Gets toggle status for Incorrect Shipping Totals defect : E-467599
     *
     * @param string $scope
     * @return bool
     */
    public function isIncorrectShippingTotalsToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;

    /**
     * Gets toggle status for Incorrect Package Count defect : D-234051
     *
     * @param string $scope
     * @return bool
     */
    public function isIncorrectPackageCountToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}