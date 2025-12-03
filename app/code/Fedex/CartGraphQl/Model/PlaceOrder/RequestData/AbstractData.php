<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\PlaceOrder\RequestData;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\PlaceOrderRequestDataInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

abstract class AbstractData implements PlaceOrderRequestDataInterface
{
    /**
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        protected JsonSerializer $jsonSerializer
    ) {}

    /**
     * @param CartIntegrationInterface $integration
     * @return array|null
     */
    public function getData(
        CartIntegrationInterface $integration
    ): ?array {
        $deliveryData = $this->jsonSerializer->unserialize($integration->getDeliveryData() ?? '{}');

        if ($this->proceedChecker($deliveryData)) {
            return $this->proceed($integration);
        }

        return null;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    abstract public function getIdentifierKey(): string;

    /**
     * @param array|null $deliveryData
     * @return bool
     * @codeCoverageIgnore
     */
    abstract public function proceedChecker(?array $deliveryData): bool;

    /**
     * Proceed getting delivery data to for Checkout API
     *
     * @param CartIntegrationInterface $integration
     * @return array
     * @codeCoverageIgnore
     */
    abstract public function proceed(CartIntegrationInterface $integration): array;
}
