<?php
/**
 * Interface ShipDateInterface
 *
 * Defines method for calculating order shipping date.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Fedex\MarketplaceCheckout\Model\DTO\ShippingDateDTO;

interface ShipDateInterface
{
    /**
     * Calculates the shipping date based on the provided parameters.
     *
     * @param ShippingDateDTO $data
     * @return int|false
     */
    public function getShipDate(ShippingDateDTO $data): int|false;

    /**
     * @param string $timeZone
     * @return string
     */
    public function getCurrentDateTime(string $timeZone): string;
}