<?php
/**
 * Interface ToggleConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_CONFIGURABLE_WRONG_MAX_MIN_QUANTITY_TOGGLE =
        'environment_toggle_configuration/environment_toggle/tiger_d232497';

    /**
     * Gets toggle status for Min and Max Qty for configurable products (Essendant) are not getting set on PDP defect : D-232497
     *
     * @param string $scope
     * @return bool
     */
    public function isConfigurableMinMaxWrongQtyToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}