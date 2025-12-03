<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Api\Data;

interface SendOrderEmailMessageInterface
{
    public const SHIPMENT_STATUS = "shipment_status";
    public const ORDER_ID = "order_id";
    public const SHIPMENT_ID = "shipment_id";
    public const TRACK = "track";

    /**
     * Getter for ShipmentStatus.
     *
     * @return string|null
     */
    public function getShipmentStatus(): ?string;

    /**
     * Setter for ShipmentStatus.
     *
     * @param string|null $shipmentStatus
     *
     * @return void
     */
    public function setShipmentStatus(?string $shipmentStatus): void;

    /**
     * Getter for OrderId.
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Setter for OrderId.
     *
     * @param int|null $orderId
     *
     * @return void
     */
    public function setOrderId(?int $orderId): void;

    /**
     * Getter for ShipmentId.
     *
     * @return int|null
     */
    public function getShipmentId(): ?int;

    /**
     * Setter for ShipmentId.
     *
     * @param int|null $shipmentId
     *
     * @return void
     */
    public function setShipmentId(?int $shipmentId): void;

    /**
     * @param string|null $track
     * @return void
     */
    public function setTrack(?string $track): void;

    /**
     * @return string|null
     */
    public function getTrack(): ?string;
}
