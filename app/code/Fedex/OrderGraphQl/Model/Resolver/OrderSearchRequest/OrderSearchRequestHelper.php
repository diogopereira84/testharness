<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai solanki
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest;

use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderSearchRequestHelper
{
    private const PRODUCT_1P_KEY = '1p';
    private const PRODUCT_3P_KEY = '3p';

    /**
     * @var ?array $itemsWithoutShipment
     */
    private ?array $itemsWithoutShipment = null;

    /**
     * @param string $orderIncrementId
     * @return bool
     */
    public function checkInstore(string $orderIncrementId): bool
    {
        return str_starts_with($orderIncrementId, PunchoutHelper::INSTORE_GTN_PREFIX);
    }

    /**
     * @param $item
     * @return mixed
     */
    public function getQuantity($item): mixed
    {
        return $item->getOrderItem()->getQtyOrdered();
    }

    /**
     * Get date in CST format
     *
     * @param string $datetime
     * @return string
     */
    public function getFormattedCstDate(string $datetime): string
    {
        try {
            $date = new \DateTime($datetime);
            return $date->format('Y-m-d\TH:i:s') . '-06:00';
        } catch (\Exception) {
        }
        return $datetime;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getItemsWithoutShipment(OrderInterface $order): array
    {
        if ($this->itemsWithoutShipment !== null) {
            return $this->itemsWithoutShipment;
        }

        $itemsWithShipment = $this->getItemsWithShipment($order);
        $this->itemsWithoutShipment = $this->filterItemsWithoutShipment($order->getAllItems(), $itemsWithShipment);

        return $this->itemsWithoutShipment;
    }

    /**
     * @param array $orderItems
     * @param array $itemsWithShipment
     * @return array
     */
    private function filterItemsWithoutShipment(array $orderItems, array $itemsWithShipment): array
    {
        $itemsWithoutShipment = [];

        foreach ($orderItems as $item) {
            if (!$this->isItemShipped($item, $itemsWithShipment)) {
                $productTypeKey = $this->getProductTypeKey($item);
                $itemsWithoutShipment[$productTypeKey][] = $item;
            }
        }

        return $itemsWithoutShipment;
    }

    /**
     * @param OrderItemInterface $item
     * @param array $itemsWithShipment
     * @return bool
     */
    private function isItemShipped(OrderItemInterface $item, array $itemsWithShipment): bool
    {
        return in_array($item->getItemId(), $itemsWithShipment);
    }

    /**
     * @param OrderItemInterface $item
     * @return string
     */
    private function getProductTypeKey(OrderItemInterface $item): string
    {
        return $item->getMiraklOfferId() ? self::PRODUCT_3P_KEY : self::PRODUCT_1P_KEY;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function getItemsWithShipment(OrderInterface $order): array
    {
        $itemsWithShipment = [];
        foreach ($order->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getItems() as $shipmentItem) {
                $itemsWithShipment[] = $shipmentItem->getOrderItemId();
            }
        }
        return $itemsWithShipment;
    }
}
