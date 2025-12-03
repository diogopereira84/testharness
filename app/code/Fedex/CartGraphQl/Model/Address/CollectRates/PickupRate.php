<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Address\CollectRates;

use Fedex\B2b\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Fedex\CartGraphQl\Api\Data\CollectRateDataInterface;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData;
use Magento\Quote\Model\Quote\Address\RateFactory;

class PickupRate extends AbstractRate implements CollectRateDataInterface
{
    const SHIPPING_RATE_CODE = 'fedexshipping_PICKUP';
    const SHIPPING_RATE_METHOD = 'PICKUP';
    const SHIPPING_RATE_CARRIER = 'fedexshipping';
    const SHIPPING_RATE_CARRIER_TITLE = 'Fedex Store Pickup';

    /**
     * @return string
     */
    public function getDataKey(): string
    {
        return PickupData::IDENTIFIER_KEY;
    }

    /**
     * Proceed collecting rate if delivery data applies to the current delivery method
     *
     * @param Address|QuoteAddress $shippingAddress
     * @param array $deliveryData
     * @return void
     */
    public function proceed(Address|QuoteAddress $shippingAddress, array $deliveryData): void
    {
        $this->setPickupRate($shippingAddress);
    }

    /**
     * @param Address|QuoteAddress $shippingAddress
     * @return void
     */
    private function setPickupRate(Address|QuoteAddress $shippingAddress): void
    {
        $cart = $shippingAddress->getQuote();
        $shipping = $cart->getShippingAddress();
        $shippingQuoteRate = $this->addressRateFactory->create();
        $shippingQuoteRate->setCarrier(self::SHIPPING_RATE_CARRIER);
        $shippingQuoteRate->setCarrierTitle($shipping->getShippingDescription() ?? self::SHIPPING_RATE_CARRIER_TITLE);
        $shippingQuoteRate->setCode(self::SHIPPING_RATE_CODE);
        $shippingQuoteRate->setMethod(self::SHIPPING_RATE_METHOD);
        $shippingQuoteRate->setMethodTitle(self::SHIPPING_RATE_METHOD);
        $shippingQuoteRate->setPrice(0);
        $shippingAddress->setCollectShippingRates(false)
            ->setShippingMethod(self::SHIPPING_RATE_CODE);
        $shipping->addShippingRate($shippingQuoteRate);
    }
}
