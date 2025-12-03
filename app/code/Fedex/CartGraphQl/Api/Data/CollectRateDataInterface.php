<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api\Data;

use Fedex\B2b\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Fedex\Cart\Api\Data\CartIntegrationInterface;

interface CollectRateDataInterface
{
    /**
     * @param Address|QuoteAddress $shippingAddress
     * @param CartIntegrationInterface $integration
     * @return void
     */
    public function collect(Address|QuoteAddress $shippingAddress, CartIntegrationInterface $integration): void;

    /**
     * Proceed collecting rate if delivery data applies to the current delivery method
     *
     * @param Address|QuoteAddress $shippingAddress
     * @param array $deliveryData
     * @return void
     */
    public function proceed(Address|QuoteAddress $shippingAddress, array $deliveryData): void;
}
