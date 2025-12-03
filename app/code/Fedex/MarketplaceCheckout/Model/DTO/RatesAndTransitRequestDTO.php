<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class RatesAndTransitRequestDTO
{
    public function __construct(
        public mixed  $request,
        public string $shipDate,
        public array  $shopData,
        public array  $shippingAddress,
        public array  $offerAddress,
        public string $shipAccountNumber,
        public float  $weight,
        public int    $totalPackageCount
    ) {
    }

}