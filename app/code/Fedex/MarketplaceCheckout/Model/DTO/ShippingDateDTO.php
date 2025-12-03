<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class ShippingDateDTO
{
    public function __construct(
        public string $currentDateTime,
        public int $businessDays,
        public string $shippingCutOffTime,
        public string $shippingSellerHolidays,
        public int $additionalProcessingDays,
        public string $timeZone
    ) {
    }

}