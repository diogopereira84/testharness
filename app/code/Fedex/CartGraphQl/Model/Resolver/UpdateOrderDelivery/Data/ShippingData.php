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

class ShippingData extends AbstractData implements DeliveryDataHandlerInterface
{
    public const DATA_KEY = 'shipping_data';
    public const IDENTIFIER_KEY = 'is_delivery';
    public const SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME = 'shipping_estimated_delivery_local_time';

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
        $this->setIntegrationData($cart, $data['shipping_data']);
        $this->setDeliveryData($cart, $data['shipping_data']);
        $this->setCartData($cart, $data['shipping_data']);
        $this->setDeliveryDatesData($cart, $data['shipping_data']);
    }

    /**
     * @param Quote $cart
     * @param array $shippingData
     * @return void
     */
    protected function setIntegrationData(Quote $cart, array $shippingData): void
    {
        $integration = $this->cartIntegrationRepository->getByQuoteId($cart->getId());
        $deliveryData = $this->getDeliveryDataFormatted($shippingData, self::IDENTIFIER_KEY);
        $integration->setDeliveryData($deliveryData);

        $this->cartIntegrationRepository->save($integration);
    }

    /**
     * @param Quote $cart
     * @param array $shippingData
     * @return void
     */
    protected function setDeliveryData(
        Quote $cart,
        array $shippingData
    ): void {
        $billingAddress = $cart->getBillingAddress();
        $shippingAddress = $cart->getShippingAddress();

        $regionId = $this->region->loadByCode(
            $shippingData['shipping_location_state'],
            $shippingData['shipping_location_country']
        )->getId();

        $shippingAddress->setFirstname($shippingData['shipping_firstname'] ?? null);
        $shippingAddress->setLastname($shippingData['shipping_lastname'] ?? null);
        $shippingAddress->setCompany($shippingData['shipping_company'] ?? null);
        $shippingAddress->setEmail($shippingData['shipping_email'] ?? null);
        $shippingAddress->setTelephone($shippingData['shipping_phone_number'] ?? null);
        $shippingAddress->setExtNo($shippingData['shipping_phone_ext'] ?? null);
        $shippingAddress->setAddressClassification($shippingData['shipping_address_classification'] ?? null);

        $street = [$shippingData['shipping_location_street1']];
        (isset($shippingData['shipping_location_street2']) && $shippingData['shipping_location_street2']) ? array_push($street, $shippingData['shipping_location_street2']) : null;
        (isset($shippingData['shipping_location_street2']) && $shippingData['shipping_location_street3']) ? array_push($street, $shippingData['shipping_location_street3']) : null;

        foreach ([$billingAddress, $shippingAddress] as $quoteShip) {
            if ($quoteShip->getId()) {
                $quoteShip->setStreet($street ?? null);
                $quoteShip->setCity($shippingData['shipping_location_city'] ?? null);
                $quoteShip->setPostcode($shippingData['shipping_location_zipcode'] ?? null);
                $quoteShip->setCountryId($shippingData['shipping_location_country'] ?? null);
                $quoteShip->setRegionId($regionId ?? null);

                $quoteShip->save();
            }
        }
    }

    /**
     * Set delivery dates data to the cart
     *
     * @param Quote $cart
     * @param array $shippingData
     * @return void
     */
    private function setDeliveryDatesData(Quote $cart, array $shippingData): void
    {
        if (!$this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
            return;
        }

        // Handle shipping_estimated_delivery_local_time
        if (isset($shippingData[self::SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME])) {
            $formattedDate = $this->dateTime->formatDate(
                $shippingData[self::SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME],
                false
            );
            $cart->setShippingEstimatedDeliveryLocalTime($formattedDate);
        }
    }

    /**
     * @param Quote $cart
     * @param array $shippingData
     * @return void
     */
    private function setCartData(Quote $cart, array $shippingData): void
    {
        $cart->setData('fedex_ship_account_number', $shippingData['shipping_account_number'] ?? null);
        $cart->setData('fedex_ship_reference_id', $shippingData['shipping_reference_id'] ?? null);
        $this->cartRepository->save($cart);
    }
}
