<?php
/**
 * Interface ToggleConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_Customer
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_ADMIN_RESET_CART_UPDATE_TOGGLE =
        'environment_toggle_configuration/environment_toggle/tiger_b2615536';

    /**
     * Check if the admin reset card update toggle is enabled.
     *
     * @param string $scope
     * @return bool
     */
    public function isAdminResetCardUpdateToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}