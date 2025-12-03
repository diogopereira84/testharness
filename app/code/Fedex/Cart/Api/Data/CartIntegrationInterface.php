<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Api\Data;

/**
 * Interface CartItemInterface
 * @api
 * @since 100.0.2
 */
interface CartIntegrationInterface
{
    const KEY_INTEGRATION_ID = 'integration_id';
    const KEY_LOCATION_ID = 'location_id';
    const KEY_STORE_ID = 'store_id';
    const KEY_QUOTE_ID = 'quote_id';
    const KEY_PICKUP_LOCATION_ID = 'pickup_location_id';
    const KEY_PICKUP_STORE_ID = 'pickup_store_id';
    public const KEY_PICKUP_LOCATION_DATE = 'pickup_location_date';
    public const KEY_RETAIL_CUSTOMER_ID = 'retail_customer_id';
    const KEY_RAQ_NET_AMOUNT = 'raq_net_amount';
    const DELIVERY_DATA = 'delivery_data';
    const RETRY_TRANSACTION_API = 'retry_transaction_api';
    const FJMP_RATE_QUOTE_ID = 'fjmp_rate_quote_id';

    /**
     * Returns the integration ID.
     *
     * @return int|null Item ID. Otherwise, null.
     */
    public function getIntegrationId();

    /**
     * Sets the integration ID.
     *
     * @param int $integrationId
     * @return $this
     */
    public function setIntegrationId($integrationId);

    /**
     * Returns the Location id.
     *
     * @return string|null Location id. Otherwise, null.
     */
    public function getLocationId();

    /**
     * Sets the Location id.
     *
     * @param string $locationId
     * @return $this
     */
    public function setLocationId($locationId);

    /**
     * Returns the Store id.
     *
     * @return string|null Store id. Otherwise, null.
     */
    public function getStoreId();

    /**
     * Sets the Store id.
     *
     * @param string $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Returns Quote id.
     *
     * @return string
     */
    public function getQuoteId();

    /**
     * Sets Quote id.
     *
     * @param string $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Returns Pickup Store id.
     *
     * @return string
     */
    public function getPickupStoreId();

    /**
     * Sets Pickup Store id.
     *
     * @param string $pickupStoreId
     * @return $this
     */
    public function setPickupStoreId($pickupStoreId);

    /**
     * Returns Pickup Location id.
     *
     * @return string
     */
    public function getPickupLocationId();

    /**
     * Sets Pickup Location id.
     *
     * @param string $pickupLocationId
     * @return $this
     */
    public function setPickupLocationId($pickupLocationId);

    /**
     * Returns Pickup Location Date.
     *
     * @return string|null
     */
    public function getPickupLocationDate(): ?string;

    /**
     * Set Pickup Location Date.
     *
     * @param string $pickupLocationDate
     * @return $this
     */
    public function setPickupLocationDate(string $pickupLocationDate): void;

    /**
     * Returns Retail Customer Id.
     *
     * @return string|null
     */
    public function getRetailCustomerId(): ?string;

    /**
     * Set Retail Customer Id.
     *
     * @param string|null $retailCustomerId
     * @return $this
     */
    public function setRetailCustomerId(?string $retailCustomerId): void;


    /**
     * Returns Raq Net Amount.
     *
     * @return float|null
     */
    public function getRaqNetAmount(): ?float;

    /**
     * Sets Raq Net Amount.
     *
     * @param float|null $raqNetAmount
     * @return $this
     */
    public function setRaqNetAmount(?float $raqNetAmount): void;

    /**
     * Returns Delivery Data.
     *
     * @return null|string
     */
    public function getDeliveryData();

    /**
     * Sets Delivery Data.
     *
     * @param string $deliveryData
     * @return $this
     */
    public function setDeliveryData($deliveryData);

    /**
     * Returns Retry Checkout Api
     *
     * @return null|bool
     */
    public function getRetryTransactionApi();

    /**
     * Sets Retry Checkout Api
     *
     * @param bool $retryCheckoutApi
     * @return $this
     */
    public function setRetryTransactionApi($retryCheckoutApi);

    /**
     * Returns Fujitsu Rate Quote ID
     *
     * @return null|string
     */
    public function getFjmpRateQuoteId();

    /**
     * Sets Fujitsu Rate Quote ID
     *
     * @return $this
     */
    public function setFjmpRateQuoteId($fjmpRateQuoteId);
}
