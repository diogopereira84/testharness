<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Model\AbstractModel;

class Integration extends AbstractModel implements CartIntegrationInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'quote_integration';

    /**
     * Initialise resource model
     * phpcs:disable
     */
    protected function _construct()
    {
        $this->_init('Fedex\Cart\Model\ResourceModel\Quote\Integration');
    }

    public function getIntegrationId()
    {
        return $this->getData(CartIntegrationInterface::KEY_INTEGRATION_ID);
    }

    public function setIntegrationId($integrationId)
    {
        $this->setData(CartIntegrationInterface::KEY_INTEGRATION_ID, $integrationId);
    }

    public function getLocationId()
    {
        return $this->getData(CartIntegrationInterface::KEY_LOCATION_ID);
    }

    public function setLocationId($locationId)
    {
        $this->setData(CartIntegrationInterface::KEY_LOCATION_ID, $locationId);
    }

    public function getStoreId()
    {
        return $this->getData(CartIntegrationInterface::KEY_STORE_ID);
    }

    public function setStoreId($storeId)
    {
        $this->setData(CartIntegrationInterface::KEY_STORE_ID, $storeId);
    }

    public function getQuoteId()
    {
        return $this->getData(CartIntegrationInterface::KEY_QUOTE_ID);
    }

    public function setQuoteId($quoteId)
    {
        $this->setData(CartIntegrationInterface::KEY_QUOTE_ID, $quoteId);
    }

    public function getPickupStoreId()
    {
        return $this->getData(CartIntegrationInterface::KEY_PICKUP_STORE_ID);
    }

    public function setPickupStoreId($pickupStoreId)
    {
        $this->setData(CartIntegrationInterface::KEY_PICKUP_STORE_ID, $pickupStoreId);
    }

    public function getPickupLocationId()
    {
        return $this->getData(CartIntegrationInterface::KEY_PICKUP_LOCATION_ID);
    }

    public function setPickupLocationId($pickupLocationId)
    {
        $this->setData(CartIntegrationInterface::KEY_PICKUP_LOCATION_ID, $pickupLocationId);
    }

    /**
     * @inheritDoc
     */
    public function getPickupLocationDate(): ?string
    {
        return $this->getData(CartIntegrationInterface::KEY_PICKUP_LOCATION_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setPickupLocationDate(string $pickupLocationDate): void
    {
        $this->setData(CartIntegrationInterface::KEY_PICKUP_LOCATION_DATE, $pickupLocationDate);
    }

    /**
     * @inheritDoc
     */
    public function getRetailCustomerId(): ?string
    {
        return $this->getData(CartIntegrationInterface::KEY_RETAIL_CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRetailCustomerId(?string $retailCustomerId): void
    {
        $this->setData(CartIntegrationInterface::KEY_RETAIL_CUSTOMER_ID, $retailCustomerId);
    }

    /**
     * @inheritDoc
     */
    public function getRaqNetAmount(): ?float
    {
        return (float) $this->getData(CartIntegrationInterface::KEY_RAQ_NET_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setRaqNetAmount(?float $raqNetAmount): void
    {
        $this->setData(CartIntegrationInterface::KEY_RAQ_NET_AMOUNT, $raqNetAmount);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryData(): ?string
    {
        return $this->getData(CartIntegrationInterface::DELIVERY_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryData($deliveryData): void
    {
        $this->setData(CartIntegrationInterface::DELIVERY_DATA, $deliveryData);
    }

    /**
     * @inheritDoc
     */
    public function getRetryTransactionApi(): ?bool
    {
        return (bool) $this->getData(CartIntegrationInterface::RETRY_TRANSACTION_API);
    }

    /**
     * @inheritDoc
     */
    public function setRetryTransactionApi($retryCheckoutApi): void
    {
        $this->setData(CartIntegrationInterface::RETRY_TRANSACTION_API, $retryCheckoutApi);
    }

    /**
     * @inheritDoc
     */
    public function getFjmpRateQuoteId(): ?string
    {
        return $this->getData(CartIntegrationInterface::FJMP_RATE_QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setFjmpRateQuoteId($fjmpRateQuoteId): void
    {
        $this->setData(CartIntegrationInterface::FJMP_RATE_QUOTE_ID, $fjmpRateQuoteId);
    }
}
