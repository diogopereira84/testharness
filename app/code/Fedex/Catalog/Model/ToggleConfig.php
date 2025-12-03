<?php
/**
 * @category    Fedex
 * @package     Fedex_Catalog
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Model;

use Fedex\Catalog\Api\ToggleConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ToggleConfig implements ToggleConfigInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isEssendantToggleEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_ESSENDANT_TOGGLE,
            $scope
        );
    }
}