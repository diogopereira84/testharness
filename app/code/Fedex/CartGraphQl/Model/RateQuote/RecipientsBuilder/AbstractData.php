<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\RecipientsBuilderDataInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;

abstract class AbstractData implements RecipientsBuilderDataInterface
{
    /**
     * @param Region $region
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param DateTime $dateTime
     * @param CartRepositoryInterface $cartRepository
     * @param InstoreConfig $instoreConfig
     * @param JsonSerializer $jsonSerializer
     * @param ShippingDelivery $shippingDelivery
     */
    public function __construct(
        protected Region $region,
        protected CartIntegrationRepositoryInterface $cartIntegrationRepository,
        protected DateTime $dateTime,
        protected CartRepositoryInterface $cartRepository,
        protected InstoreConfig $instoreConfig,
        protected JsonSerializer $jsonSerializer,
        protected ShippingDelivery $shippingDelivery
    ) {
    }

    /**
     * @param string $referenceId
     * @param CartIntegrationInterface $integration
     * @param array $productAssociations
     * @param string|null $requestedPickupLocalTime
     * @param string|null $requestedDeliveryLocalTime
     * @param string|null $shippingEstimatedDeliveryLocalTime
     * @param string|null $holdUntilDate
     * @return array|null
     */
    public function getData(
        string $referenceId,
        CartIntegrationInterface $integration,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): ?array {
        $deliveryData = $this->jsonSerializer->unserialize($integration->getDeliveryData());

        if (is_array($deliveryData) && array_key_exists($this->getIdentifierKey(), $deliveryData)) {
            return $this->proceed(
                $referenceId,
                $integration,
                $productAssociations,
                $requestedPickupLocalTime,
                $requestedDeliveryLocalTime,
                $shippingEstimatedDeliveryLocalTime,
                $holdUntilDate
            );
        }

        return null;
    }

    /**
     * @return string
     */
    abstract public function getIdentifierKey(): string;

    /**
     * Proceed getting delivery data to for RateQuote API
     *
     * @param string $referenceId
     * @param CartIntegrationInterface $integration
     * @param array $productAssociations
     * @param string|null $requestedPickupLocalTime
     * @param string|null $requestedDeliveryLocalTime
     * @param string|null $shippingEstimatedDeliveryLocalTime
     * @param string|null $holdUntilDate
     * @return array
     */
    abstract public function proceed(
        string $referenceId,
        CartIntegrationInterface $integration,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): array;
}
