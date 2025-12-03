<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data;

use Fedex\CartGraphQl\Api\Data\DeliveryDataHandlerInterface;
use Magento\Quote\Model\Quote;

class PickupData extends AbstractData implements DeliveryDataHandlerInterface
{
    public const DATA_KEY = 'pickup_data';
    public const IDENTIFIER_KEY = 'is_pickup';
    public const PICKUP_LOCATION_DATE = 'pickup_location_date';

    /**
     * @return string
     */
    public function getDataKey(): string
    {
        return self::DATA_KEY;
    }

    /**
     * Proceed setting delivery data if current data applies to the current delivery method
     *
     * @param Quote $cart
     * @param array $data
     * @return void
     */
    public function proceed(Quote $cart, array $data): void
    {
        $this->setIntegrationData($cart, $data['pickup_data']);
        $this->setDeliveryData($cart, $data['pickup_data']);
        $this->setDeliveryDatesData($cart, $data['pickup_data']);
    }

    /**
     * @param Quote $cart
     * @param array $pickupData
     * @return void
     */
    protected function setDeliveryData(
        Quote $cart,
        array $pickupData
    ): void {
        $billingAddress = $cart->getBillingAddress();
        $shippingAddress = $cart->getShippingAddress();
        $regionId = $this->region->loadByCode(
            $pickupData['pickup_location_state'],
            $pickupData['pickup_location_country']
        )->getId();

        foreach ([$billingAddress, $shippingAddress] as $quoteShip) {
            if ($quoteShip->getId()) {
                $quoteShip->setStreet($pickupData['pickup_location_street'] ?? null);
                $quoteShip->setCity($pickupData['pickup_location_city'] ?? null);
                $quoteShip->setPostcode($pickupData['pickup_location_zipcode'] ?? null);
                $quoteShip->setCountryId($pickupData['pickup_location_country'] ?? null);
                $quoteShip->setRegionId($regionId ?? null);

                if ($quoteShip->getAddressType() == 'shipping') {
                    $quoteShip->setShippingDescription($pickupData['pickup_location_id'] ?? null);
                }

                $quoteShip->save();
            }
        }
    }

    /**
     * Set delivery dates data to the cart
     *
     * @param Quote $cart
     * @param array $pickupData
     * @return void
     */
    private function setDeliveryDatesData(Quote $cart, array $pickupData): void
    {
        if (!$this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
            return;
        }

        // Handle pickup_location_date
        if (isset($pickupData[self::PICKUP_LOCATION_DATE])) {
            $formattedPickupDate = $this->dateTime->formatDate(
                $pickupData[self::PICKUP_LOCATION_DATE],
                false
            );
            $cart->setHoldUntilDate($formattedPickupDate);
        }
    }

    /**
     * @param Quote $cart
     * @param array $pickupData
     * @return void
     */
    protected function setIntegrationData(Quote $cart, array $pickupData): void
    {
        $integration = $this->cartIntegrationRepository->getByQuoteId($cart->getId());
        $integration->setPickupLocationId($pickupData['pickup_location_id'] ?? null);
        $integration->setPickupStoreId($pickupData['pickup_store_id'] ?? null);

        $deliveryData = $this->getDeliveryDataFormatted($pickupData, self::IDENTIFIER_KEY);
        $integration->setDeliveryData($deliveryData);
        $this->cartIntegrationRepository->save($integration);
    }
}
