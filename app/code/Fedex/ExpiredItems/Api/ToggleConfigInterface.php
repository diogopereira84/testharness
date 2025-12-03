<?php
/**
 * Interface ConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_ExpiredItems
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ExpiredItems\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_CART_EXPIRY_MESSAGE_TOGGLE =
        'environment_toggle_configuration/environment_toggle/tiger_d227527';

    /**
     * Gets toggle status for Cart Expiry Message defect : D-227527
     *
     * @param string $scope
     * @return bool
     */
    public function isIncorrectCartExpiryMassageToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}