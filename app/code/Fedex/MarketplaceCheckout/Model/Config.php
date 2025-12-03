<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\ToggleConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ToggleConfigInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function isShippingManagementRefactorEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_SHIPPING_MANAGEMENT_REFACTOR,
            $scope
        );
    }

    public function isMarketplaceEnabledForCommercialSites(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_ENABLE_MARKETPLACE_COMMERCIAL,
            $scope
        );
    }

    public function isIncorrectShippingTotalsToggleEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_INCORRECT_SHIPPING_TOTALS,
            $scope
        );
    }

    public function isIncorrectPackageCountToggleEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_INCORRECT_PACKAGE_COUNT,
            $scope
        );
    }
}