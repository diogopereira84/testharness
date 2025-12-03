<?php
/**
 * @category    Fedex
 * @package     Fedex_GraphQl
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model;

use Fedex\GraphQl\Api\ToggleConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ToggleConfigInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Gets toggle status for D-235836 Enable Logging for Unserialize Syntax Errors
     *
     * @param string $scope
     * @return bool
     */
    public function isGraphqlRequestErrorLogsEnabled(string $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->scopeConfig->getValue(
            static::XML_PATH_D235836_LOGS_ENABLED,
            $scope
        );
    }
}