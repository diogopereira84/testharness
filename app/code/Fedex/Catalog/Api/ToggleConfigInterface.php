<?php
/**
 * Interface ToggleConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_Catalog
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_ESSENDANT_TOGGLE =
        'environment_toggle_configuration/environment_toggle/tiger_e458381_essendant';

    /**
     * Gets toggle status for Essendant
     *
     * @param string $scope
     * @return bool
     */
    public function isEssendantToggleEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}