<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\PlaceOrder\RequestData;

use DateTime;
use Exception;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Api\Data\PlaceOrderRequestDataInterface;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData as ResolverPickupData;

class PickupData extends AbstractData implements PlaceOrderRequestDataInterface
{
    public const IDENTIFIER_KEY = ResolverPickupData::IDENTIFIER_KEY;

    /**
     * @return string
     */
    public function getIdentifierKey(): string
    {
        return self::IDENTIFIER_KEY;
    }


    /**
     * @param array|null $deliveryData
     * @return bool
     */
    public function proceedChecker(?array $deliveryData): bool
    {
        if (is_array($deliveryData) && array_key_exists($this->getIdentifierKey(), $deliveryData) ||
            empty($deliveryData)) {
            return true;
        }
        return false;
    }

    /**
     * Proceed getting delivery data to for Checkout API
     *
     * @param CartIntegrationInterface $integration
     * @return array
     */
    public function proceed(CartIntegrationInterface $integration): array
    {
        $estimatePickupTime = $this->getEstimatePickupTime($integration);

        return [
            "pickupData" => json_encode([
                "addressInformation" => [
                    "estimate_pickup_time" => $estimatePickupTime,
                    "estimate_pickup_time_for_api" => $estimatePickupTime
                ]
            ])
        ];
    }

    /**
     * @param CartIntegrationInterface $integration
     * @return string|null
     */
    private function getEstimatePickupTime(CartIntegrationInterface $integration): ?string
    {
        try {
            $expectedDate = new DateTime($integration->getPickupLocationDate());
            $estimatePickupTime = $expectedDate->format("Y-m-d") . "T" . $expectedDate->format("H:i:s");
        } catch (Exception) {
            $estimatePickupTime = null;
        }

        return $estimatePickupTime;
    }
}
