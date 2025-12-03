<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\Data\UnifiedDataLayer;

use Fedex\SubmitOrderSidebar\Api\Data\DeliveryInterface;
use Magento\Framework\DataObject;

class Delivery extends DataObject implements DeliveryInterface
{
    /**
     * Order shipment id key
     */
    private const SHIPMENT_ID = 'shipmentId';

    /**
     * Order produced by key
     */
    private const PRODUCED_BY = 'producedBy';

    /**
     * Order delivery method key
     */
    private const DELIVERY_METHOD = 'deliveryMethod';

    /**
     * Order shipping/item price key
     */
    private const PRICE = 'price';

    /**
     * Order line items key
     */
    private const LINE_ITEMS = 'lineItems';

    /**
     * @inheritDoc
     */
    public function getShipmentId(): string
    {
        return $this->getData(self::SHIPMENT_ID) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setShipmentId(string $shipmentId): DeliveryInterface
    {
        return $this->setData(self::SHIPMENT_ID, $shipmentId);
    }

    /**
     * @inheritDoc
     */
    public function getProducedBy(): string
    {
        return $this->getData(self::PRODUCED_BY) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setProducedBy(string $producedBy): DeliveryInterface
    {
        return $this->setData(self::PRODUCED_BY, $producedBy);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryMethod(): string
    {
        return $this->getData(self::DELIVERY_METHOD) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryMethod(string $deliveryMethod): DeliveryInterface
    {
        return $this->setData(self::DELIVERY_METHOD, $deliveryMethod);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): string
    {
        return $this->getData(self::PRICE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setPrice(string $price): DeliveryInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritDoc
     */
    public function getLineItems(): array
    {
        return $this->getData(self::LINE_ITEMS) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setLineItems(array $lineItems): DeliveryInterface
    {
        return $this->setData(self::LINE_ITEMS, $lineItems);
    }
}
