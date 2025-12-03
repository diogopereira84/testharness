<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Address;

use Magento\Directory\Model\Region;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateFactory;

/**
 * @deprecated in B-2014766
 * @see \Fedex\CartGraphQl\Model\Address\CollectRates
 */
class Builder {
    const SHIPPING_RATE_CODE = 'fedexshipping_PICKUP';
    const SHIPPING_RATE_METHOD = 'PICKUP';
    const SHIPPING_RATE_CARRIER = 'fedexshipping';
    const SHIPPING_RATE_CARRIER_TITLE = 'Fedex Store Pickup';

    /**
     * @param Region $region
     * @param RateFactory $addressRateFactory
     */
    public function __construct(
        private Region $region,
        private RateFactory $addressRateFactory
    )
    {
    }

    /**
     * @param Quote $cart
     * @param array $shippingContact
     * @param array $pickupData
     * @return void
     */
    public function setAddressData(Quote $cart, array $shippingContact, array $pickupData): void
    {
        $billingAddress = $cart->getBillingAddress();
        $shippingAddress = $cart->getShippingAddress();

        foreach ([$billingAddress, $shippingAddress] as $quoteShip) {
            if ($quoteShip->getId()) {
                $quoteShip->setContactInfo($quoteShip, $shippingContact);
                $regionId = $this->region->loadByCode(
                    $pickupData['pickup_location_state'],
                    $pickupData['pickup_location_country']
                )->getId();
                $quoteShip->setStreet($pickupData['pickup_location_street'] ?? null);
                $quoteShip->setCity($pickupData['pickup_location_city'] ?? null);
                $quoteShip->setPostcode($pickupData['pickup_location_zipcode'] ?? null);
                $quoteShip->setCountryId($pickupData['pickup_location_country'] ?? null);
                $quoteShip->setRegionId($regionId ?? null);
                $quoteShip->setFirstname($shippingContact['firstname'] ?? null);
                $quoteShip->setLastname($shippingContact['lastname'] ?? null);
                $quoteShip->setEmail($shippingContact['email'] ?? null);
                $quoteShip->setTelephone($shippingContact['telephone'] ?? null);
                $quoteShip->setExt($shippingContact['ext'] ?? null);

                if ($quoteShip->getAddressType() == 'shipping') {
                    $quoteShip->setShippingDescription($pickupData['pickup_location_id'] ?? null);
                }
                $quoteShip->save();
            }
        }
    }

    /**
     * @param $cart
     * @param $shippingAddress
     * @return void
     */
    public function setShippingData($cart, $shippingAddress): void
    {
        $shippingQuoteRate = $this->addressRateFactory->create();
        $shippingQuoteRate->setCarrier(self::SHIPPING_RATE_CARRIER);
        $shippingQuoteRate->setCarrierTitle(self::SHIPPING_RATE_CARRIER_TITLE);
        $shippingQuoteRate->setCode(self::SHIPPING_RATE_CODE);
        $shippingQuoteRate->setMethod(self::SHIPPING_RATE_METHOD);
        $shippingQuoteRate->setPrice(0);
        $shippingQuoteRate->setMethodTitle(self::SHIPPING_RATE_METHOD);
        $shippingAddress->setCollectShippingRates(false)
            ->setShippingMethod(self::SHIPPING_RATE_CODE);
        $cart->getShippingAddress()->addShippingRate($shippingQuoteRate);
    }
}
