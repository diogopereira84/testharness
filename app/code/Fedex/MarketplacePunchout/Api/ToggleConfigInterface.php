<?php
/**
 * Interface ConfigInterface
 *
 * Defines methods for getting system toggles.
 *
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Api;

use Magento\Store\Model\ScopeInterface;

interface ToggleConfigInterface
{
    public const XML_PATH_MARKETPLACE_PUNCHOUT_ADD_SELLERID =
        'environment_toggle_configuration/environment_toggle/tiger_tk4611007_punchout_seller_id';

    /**
     * Gets toggle status for TK-4611007 : Add SellerID to Marketplace Punchout Request XML
     *
     * @param string $scope
     * @return bool
     */
    public function isAddingSellerIdInPunchoutEnabled(
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool;
}