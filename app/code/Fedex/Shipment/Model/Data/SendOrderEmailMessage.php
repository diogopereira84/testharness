<?php
/**
 * @category     Fedex
 * @package      Fedex_Shipment
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Shipment\Model\Data;

use Fedex\Shipment\Api\Data\SendOrderEmailMessageInterface;
use Magento\Framework\DataObject;

class SendOrderEmailMessage extends DataObject implements SendOrderEmailMessageInterface
{
    /**
     * Getter for ShipmentStatus.
     *
     * @return string|null
     */
    public function getShipmentStatus(): ?string
    {
        return $this->getData(self::SHIPMENT_STATUS);
    }

    /**
     * Setter for ShipmentStatus.
     *
     * @param string|null $shipmentStatus
     *
     * @return void
     */
    public function setShipmentStatus(?string $shipmentStatus): void
    {
        $this->setData(self::SHIPMENT_STATUS, $shipmentStatus);
    }

    /**
     * Getter for OrderId.
     *
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID) === null ? null : (int)$this->getData(self::ORDER_ID);
    }

    /**
     * Setter for OrderId.
     *
     * @param int|null $orderId
     *
     * @return void
     */
    public function setOrderId(?int $orderId): void
    {
        $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Getter for ShipmentId.
     *
     * @return int|null
     */
    public function getShipmentId(): ?int
    {
        return $this->getData(self::SHIPMENT_ID) === null ? null : (int)$this->getData(self::SHIPMENT_ID);
    }

    /**
     * Setter for ShipmentId.
     *
     * @param int|null $shipmentId
     *
     * @return void
     */
    public function setShipmentId(?int $shipmentId): void
    {
        $this->setData(self::SHIPMENT_ID, $shipmentId);
    }

    /**
     * Getter for track.
     *
     * @return string|null
     */
    public function getTrack(): ?string
    {
        $track = $this->getData(self::TRACK);
        return $track;
    }

    public function setTrack(?string $track): void
    {
        if (!is_string($track) && !is_null($track)) {
            throw new \InvalidArgumentException('Track must be a string or null.');
        }

        $this->setData(self::TRACK, $track);
    }
}
