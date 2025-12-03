<?php
/**
 * Interface ToggleConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_D235836_LOGS_ENABLED =
        'environment_toggle_configuration/environment_toggle/d235836_error_logs';

    /**
     * Gets toggle status for D-235836 Enable Logging for Unserialize Syntax Errors
     *
     * @param string $scope
     * @return bool
     */
    public function isGraphqlRequestErrorLogsEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}