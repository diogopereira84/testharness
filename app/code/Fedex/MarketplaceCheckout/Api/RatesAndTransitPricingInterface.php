<?php
/**
 * Interface RatesAndTransitPricingInterface
 *
 * Defines methods for handling rates and transit pricing operations in the Fedex MarketplaceCheckout module.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Fedex\MarketplaceCheckout\Model\DTO\RatesAndTransitRequestDTO;
interface RatesAndTransitPricingInterface
{
    /**
     * Retrieves shipping rates based on the provided request and parameters.
     *
     * @param mixed $data
     * @return array
     */
    public function getRates(RatesAndTransitRequestDTO $data): array;

    /**
     * Sets parameters for the rates and transit pricing request.
     *
     * @param RatesAndTransitRequestDTO $data
     * @return void
     */
    public function setParams(RatesAndTransitRequestDTO $data): void;
}