<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api\Data;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Quote\Model\Quote;

interface RecipientsBuilderDataInterface
{
    /**
     * @return string
     */
    public function getIdentifierKey(): string;

    /**
     * @param string $referenceId
     * @param CartIntegrationInterface $integration
     * @param array $productAssociations
     * @param string|null $requestedPickupLocalTime
     * @param string|null $requestedDeliveryLocalTime
     * @param string|null $shippingEstimatedDeliveryLocalTime
     * @param string|null $holdUntilDate
     * @return null|array
     */
    public function getData(
        string $referenceId,
        CartIntegrationInterface $integration,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): ?array;

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
    public function proceed(
        string $referenceId,
        CartIntegrationInterface $integration,
        array $productAssociations,
        ?string $requestedPickupLocalTime = null,
        ?string $requestedDeliveryLocalTime = null,
        ?string $shippingEstimatedDeliveryLocalTime = null,
        ?string $holdUntilDate = null
    ): array;
}
