<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Address\CollectRates;

use Exception;
use Fedex\B2b\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\CollectRateDataInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Model\Quote\Address\RateFactory;

abstract class AbstractRate implements CollectRateDataInterface
{
    /**
     * @param RateFactory $addressRateFactory
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        protected RateFactory $addressRateFactory,
        protected JsonSerializer $jsonSerializer
    ) {
    }

    /**
     * Proceed collecting rate if data applies to current delivery method
     * @param Address|QuoteAddress $shippingAddress
     * @param CartIntegrationInterface $integration
     * @return void
     */
    public function collect(Address|QuoteAddress $shippingAddress, CartIntegrationInterface $integration): void
    {
        try {
            $deliveryData = $this->jsonSerializer->unserialize($integration->getDeliveryData());
        } catch (Exception) {
            $deliveryData = [];
        }

        if (is_array($deliveryData) && array_key_exists($this->getDataKey(), $deliveryData)) {
            $this->proceed($shippingAddress, $deliveryData);
        }
    }

    /**
     * @return string
     */
    abstract public function getDataKey(): string;

    /**
     * Proceed collecting rate if delivery data applies to the current delivery method
     *
     * @param Address|QuoteAddress $shippingAddress
     * @param array $deliveryData
     * @return void
     */
    abstract public function proceed(Address|QuoteAddress $shippingAddress, array $deliveryData): void;
}
