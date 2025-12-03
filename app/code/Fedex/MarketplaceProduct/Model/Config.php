<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Fedex\MarketplaceProduct\Api\ToggleConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ToggleConfigInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function isConfigurableMinMaxWrongQtyToggleEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_CONFIGURABLE_WRONG_MAX_MIN_QUANTITY_TOGGLE,
            $scope
        );
    }
}