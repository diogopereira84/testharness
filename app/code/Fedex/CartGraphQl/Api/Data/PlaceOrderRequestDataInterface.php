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

interface PlaceOrderRequestDataInterface
{
    /**
     * @return string
     */
    public function getIdentifierKey(): string;

    /**
     * @param array|null $deliveryData
     * @return bool
     */
    public function proceedChecker(?array $deliveryData): bool;

    /**
     * @param CartIntegrationInterface $integration
     * @return array|null
     */
    public function getData(CartIntegrationInterface $integration): ?array;

    /**
     * Proceed getting delivery data to for Checkout API
     *
     * @param CartIntegrationInterface $integration
     * @return array
     */
    public function proceed(CartIntegrationInterface $integration): array;
}
