<?php
/**
 * Copyright Infogain All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model;

use Exception;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\Shipment\Api\NewOrderUpdateInterface;
use Fedex\Shipment\Helper\Data;
use Fedex\Shipment\Helper\ShipmentEmail;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Psr\Log\LoggerInterface;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class UpdateOrderInformation
{

    private const ORDER_TYPES = [
        1 => '1p',
        2 => 'mixed'
    ];
    /**
     * Collection
     *
     * @var Collection
     */
    protected $statusCollection;
    private $comment = '';

    /**
     * @param Data $helper
     * @param Order $order
     * @param ShipmentEmail $shipmentEmail
     * @param LoggerInterface $logger
     * @param MiraklOrderHelper $miraklOrderHelper
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param HandleMktCheckout $handleMktCheckout
     */
    public function __construct(
        /**
         * Data
         */
        protected Data $helper,
        /**
         * Order
         */
        protected Order $order,
        /**
         * ShipmentEmail
         */
        protected ShipmentEmail $shipmentEmail,
        /**
         * LoggerInterface
         */
        protected LoggerInterface $logger,
        private readonly MiraklOrderHelper $miraklOrderHelper,
        private OrderItemRepositoryInterface $orderItemRepository,
        private readonly HandleMktCheckout $handleMktCheckout
    )
    {
    }

    /**
     * Use to process data from RabbitMq to update order & send email
     *
     * @param string $message
     * @return boolean
     */
    public function processOrderUpdateInformation($message)
    {
        try {
            $requestData = json_decode((string)$message, true);
            if (isset($requestData['shipmentItems'][0]['reasonCode'])) {
                $this->comment = 'reasonCode: ' .$requestData['shipmentItems'][0]['reasonCode']
                    . ', user reference: ' . $requestData['shipmentItems'][0]['user']['reference']
                    . ', user source: ' . $requestData['shipmentItems'][0]['user']['source'];

                if (isset($requestData['shipmentItems'][0]['user']['name'])) {
                    $this->comment .= ', user name: ' . $requestData['shipmentItems'][0]['user']['name'];
                }
            }
            $orderIncrementId = $requestData['order_increment_id'] ?? '';
            if (!empty($orderIncrementId) && !empty($requestData) && is_array($requestData)) {
                $order = $this->order->loadByIncrementId($orderIncrementId);
                $orderId = $order->getId();

                $this->logger->info(__METHOD__.':'.__LINE__.':Message read from Queue for Order'. $orderIncrementId);

                $requestedShipmentItems = $requestData['shipmentItems'];
                $this->helper->insertOrderReference($requestData);
                $fxoWorkOrderNumber = $requestData['fxoWorkOrderNumber'] ?? '';
                $order->setExtOrderId($fxoWorkOrderNumber);
                $order->save();
                if (!empty($requestedShipmentItems) && is_array($requestedShipmentItems)) {
                    foreach ($requestedShipmentItems as $shipItem) {
                        $fxoShipmentId = $shipItem['shipmentId'] ?? '';
                        $shipmentIds = $this->helper->getShipmentIdByFxoShipmentId($orderId, $fxoShipmentId);
                        $status = $shipItem['status'] ?? '';
                        $shipmentStatus = strtolower(trim($status));

                        $trackingNumber = $shipItem['trackingNumber'] ?? '';
                        $courier = $shipItem['courier'] ?? '';
                        $pickupAllowedUntilDate = $shipItem['pickupAllowedUntilDate'] ?? '';
                        if(is_array($shipmentIds)) {
                            foreach ($shipmentIds as $shipmentId) {
                                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                                $shipment = $this->helper->getShipmentById($shipmentId);
                                if ($shipment != null) {
                                    $this->helper->updateShipmentStatus(
                                        $shipment,
                                        $trackingNumber,
                                        $shipmentStatus,
                                        $courier,
                                        $pickupAllowedUntilDate
                                    );
                                    $shipmentStatusId = $shipment->getShipmentStatus();
                                    $shipmetStatus = $this->helper->getShipmentStatusByValue($shipmentStatusId);

                                    $this->updateOrderItemQtyShipped($shipmetStatus, $order);

                                    $this->updateCancelledStatus($shipmetStatus, $orderId, $shipmentId, $order, $shipment);
                                    $order = $this->order->loadByIncrementId($orderIncrementId);

                                    $shipment->setFxoWorkOrderNumber($fxoWorkOrderNumber);
                                    $shipment->save();

                                }
                            }
                        } else {
                            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                            $shipment = $this->helper->getShipmentById($shipmentIds);
                            if ($shipment != null) {
                                $this->helper->updateShipmentStatus(
                                    $shipment,
                                    $trackingNumber,
                                    $shipmentStatus,
                                    $courier,
                                    $pickupAllowedUntilDate
                                );
                                $shipmentStatusId = $shipment->getShipmentStatus();
                                $shipmetStatus = $this->helper->getShipmentStatusByValue($shipmentStatusId);

                                $this->updateOrderItemQtyShipped($shipmetStatus, $order);

                                $this->updateCancelledStatus($shipmetStatus, $orderId, $shipmentIds, $order, $shipment);
                                $order = $this->order->loadByIncrementId($orderIncrementId);

                                $shipment->setFxoWorkOrderNumber($fxoWorkOrderNumber);
                                $shipment->save();

                            }
                        }
                    }

                    $this->updateOrderStatus($orderId, $order, $shipmetStatus);
                }
            }
            return true;
        } catch (Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':Error message', ['exception' => $e->getMessage()]);
        }
    }

    private function updateOrderItemQtyShipped($shipmetStatus, Order $order)
    {
        if ($shipmetStatus == NewOrderUpdateInterface::READY_FOR_PICKUP
            || $shipmetStatus == NewOrderUpdateInterface::SHIPPED) {
            $orderItems = $order->getAllVisibleItems();
            if ($orderItems) {
                foreach ($orderItems as $item) {
                    // We skip 3P products
                    if ($item->getIsVirtual() || $item->getMiraklOfferId()) {
                        continue;
                    }
                    $item->setQtyShipped($item->getQtyOrdered());
                    $this->orderItemRepository->save($item);
                }
            }
        }
    }

    /**
     * Use to generate refund
     *
     * @param string $shipmetStatus
     * @param int $orderId
     * @param int $shipmentId
     * @param Order $order
     * @param object $shipment
     * @return boolean
     */
    public function updateCancelledStatus($shipmetStatus, $orderId, $shipmentId, $order, $shipment)
    {
        if ($shipmetStatus  ==  "cancelled") {
            $orderItemId = $this->helper->getOrderItemIdByShipmentId($shipmentId);
            $orderStatusRefund = $this->helper->detetermineOrderStatus($orderId);
            $this->helper->generateRefund(
                $orderId,
                $orderStatusRefund,
                $orderItemId,
                $order,
                $shipment
            );
        }

        return true;
    }

    /**
     * Use to update order status
     *
     * @param int $orderId
     * @param Order $order
     * @param String $shipmetStatus
     * @return boolean
     */
    public function updateOrderStatus(int $orderId, Order $order, $shipmetStatus=null): bool
    {
        try {
            if ($shipmetStatus == NewOrderUpdateInterface::CANCELLED) {
                throw new \Exception("For cancellations, go through regular flow");
            }
            $this->setMixedOrderStatus($order, $shipmetStatus);

        } catch (Exception) {
            $canChangeOrderStatus = false;

            // Do not change order status if it is a mixed order and all items are not cancelled
            if (($this->helper->isMixedOrder($order)
                    && $this->helper->isAllItemsCancelled($order)) ||
                $this->miraklOrderHelper->isFullMiraklOrder($order) ||
                !$this->miraklOrderHelper->isMiraklOrder($order)) {
                $canChangeOrderStatus = true;
            }

            if ($canChangeOrderStatus) {
                $orderStatus = $this->helper->detetermineOrderStatus($orderId);
                $orderState = $orderStatus;

                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                    ':Determine Order status from API shipment status ' . $orderStatus . $orderId);

                $this->setOrderStatus($orderStatus, $orderState, $order);
            }
        }

        return true;
    }

    /**
     * @param Order $order
     * @return void
     * @throws Exception
     */
    private function setMixedOrderStatus(Order $order, $shipmetStatus=null): void
    {
        if ($this->helper->isMixedOrder($order)) {

            try {
                if ($shipmetStatus == NewOrderUpdateInterface::READY_FOR_PICKUP) {
                    throw new \Exception('Ready for pickup is still processing state for mixed cart.');
                }

                $this->isAllItemsShipped($order);
                $orderStatus = NewOrderUpdateInterface::COMPLETE;
                $orderState = NewOrderUpdateInterface::COMPLETE;
            } catch (Exception) {
                $orderStatus = NewOrderUpdateInterface::CONFIRMED;
                $orderState = NewOrderUpdateInterface::IN_PROCESS;
            }

            $this->setOrderStatus($orderStatus, $orderState, $order);

            return;
        }
        throw new \Exception('Is 1p order. Continue regular flow to 1p.');
    }

    /**
     * @param string $orderStatus
     * @param string $orderState
     * @param object $order
     * @param string $type
     * @return void
     */
    private function setOrderStatus(string $orderStatus, string $orderState, object $order, string $type = '1'): void
    {
        try {
            $status = $this->getOrderStatusText($orderStatus);
            $state = $this->getOrderStateText($orderState);
            $this->helper->updateStatusOfOrder($status,$state,$order, $this->comment);
        } catch (Exception) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':' . $order->getIncrementId() . ' Passing wrong status in API');
        }
    }

    /**
     * @param string $orderStatus
     * @return string
     * @throws Exception
     */
    private function getOrderStatusText(string $orderStatus): string
    {
        return match ($orderStatus) {
            NewOrderUpdateInterface::COMPLETE => NewOrderUpdateInterface::COMPLETE,
            NewOrderUpdateInterface::CANCELED => NewOrderUpdateInterface::CANCELED,
            NewOrderUpdateInterface::CONFIRMED => NewOrderUpdateInterface::CONFIRMED,
            NewOrderUpdateInterface::READY_FOR_PICKUP => NewOrderUpdateInterface::READY_FOR_PICKUP,
            NewOrderUpdateInterface::SHIPPED => NewOrderUpdateInterface::SHIPPED,
            NewOrderUpdateInterface::DELIVERED => NewOrderUpdateInterface::DELIVERED,
            NewOrderUpdateInterface::IN_PROGRESS => NewOrderUpdateInterface::IN_PROGRESS,
            default => throw new Exception('Non matching status')
        };
    }

    /**
     * @param string $orderStatus
     * @return string
     * @throws Exception
     */
    private function getOrderStateText(string $orderStatus): string
    {
        return match ($orderStatus) {
            NewOrderUpdateInterface::COMPLETE => NewOrderUpdateInterface::COMPLETE,
            NewOrderUpdateInterface::CANCELED => NewOrderUpdateInterface::CANCELED,
            NewOrderUpdateInterface::CONFIRMED,
            NewOrderUpdateInterface::DELIVERED,
            NewOrderUpdateInterface::IN_PROGRESS => NewOrderUpdateInterface::CONFIRMED,
            NewOrderUpdateInterface::IN_PROCESS => NewOrderUpdateInterface::IN_PROCESS,
            NewOrderUpdateInterface::READY_FOR_PICKUP,
            NewOrderUpdateInterface::SHIPPED => NewOrderUpdateInterface::PENDING,
            default => throw new Exception('Non matching state')
        };
    }

    /**
     * @param Order $order
     * @return void
     * @throws Exception
     */
    private function isAllItemsShipped(Order $order): void
    {
        $orderItems = $order->getAllItems();

        foreach ($orderItems as $orderItem) {
            if ($orderItem->getQtyOrdered() > $orderItem->getQtyShipped()) {
                throw new \Exception('Order shipment incomplete. Continue regular flow to 1p');
            }
        }
    }
}
