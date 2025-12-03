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
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ShippingData;
use Magento\Quote\Model\Quote\Address\RateFactory;

class ShippingRate extends AbstractRate implements CollectRateDataInterface
{
    const SHIPPING_RATE_CARRIER = 'fedexshipping';

    /**
     * @return string
     */
    public function getDataKey(): string
    {
        return ShippingData::IDENTIFIER_KEY;
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
        $this->setShippingRate($shippingAddress, $deliveryData);
    }

    /**
     * @param Address|QuoteAddress $shippingAddress
     * @param array $deliveryData
     * @return void
     */
    private function setShippingRate(Address|QuoteAddress $shippingAddress, array $deliveryData): void
    {
        $cart = $shippingAddress->getQuote();
        $shipping = $cart->getShippingAddress();
        $code = implode('_', [self::SHIPPING_RATE_CARRIER, $deliveryData['shipping_method']]);

        $shippingQuoteRate = $this->addressRateFactory->create();
        $shippingQuoteRate->setCarrier(self::SHIPPING_RATE_CARRIER);
        $shippingQuoteRate->setCarrierTitle($deliveryData['shipping_title'] ?? '');
        $shippingQuoteRate->setCode($code);
        $shippingQuoteRate->setMethod($deliveryData['shipping_method'] ?? '');
        $shippingQuoteRate->setPrice($deliveryData['shipping_price'] ?? 0);
        $shippingQuoteRate->setMethodTitle($deliveryData['shipping_method'] ?? '');
        $shippingAddress->setCollectShippingRates(false)
            ->setShippingMethod($code);
        $shipping->removeAllShippingRates();
        $shipping->addShippingRate($shippingQuoteRate);
    }
}
