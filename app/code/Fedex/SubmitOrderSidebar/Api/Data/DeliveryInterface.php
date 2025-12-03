<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api\Data;

interface DeliveryInterface
{
    /**
     * Retrieve shipment id
     *
     * @return string
     */
    public function getShipmentId(): string;

    /**
     * Set shipment id
     *
     * @param string $shipmentId
     *
     * @return DeliveryInterface
     */
    public function setShipmentId(string $shipmentId): DeliveryInterface;

    /**
     * Retrieve order id
     *
     * @return string
     */
    public function getProducedBy(): string;

    /**
     * Set order id
     *
     * @param string $producedBy
     *
     * @return DeliveryInterface
     */
    public function setProducedBy(string $producedBy): DeliveryInterface;

    /**
     * Retrieve delivery method
     *
     * @return string
     */
    public function getDeliveryMethod(): string;

    /**
     * Set delivery method
     *
     * @param string $deliveryMethod
     *
     * @return DeliveryInterface
     */
    public function setDeliveryMethod(string $deliveryMethod): DeliveryInterface;

    /**
     * Retrieve delivery date
     *
     * @return string
     */
    public function getPrice(): string;

    /**
     * Set delivery date
     *
     * @param string $price
     *
     * @return DeliveryInterface
     */
    public function setPrice(string $price): DeliveryInterface;

    /**
     * Retrieve delivery time
     *
     * @return array
     */
    public function getLineItems(): array;

    /**
     * Set delivery time
     *
     * @param array $lineItems
     *
     * @return DeliveryInterface
     */
    public function setLineItems(array $lineItems): DeliveryInterface;
}
