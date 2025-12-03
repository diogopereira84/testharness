<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Model;

use Fedex\Shipment\Model\ResourceModel\DueDateLog;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\History;
use Magento\Sales\Model\Order;
use Fedex\Shipment\Helper\ShipmentEmail;
use Fedex\Shipment\Helper\Data;
use Magento\Sales\Model\Order\ShipmentRepository;
use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Magento\Framework\App\State;
use Fedex\Shipment\Helper\StatusOption as ShipmentHelper;
use Fedex\MarketplaceCheckout\Model\CancelOrder as MarketPlaceOrder;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Fedex\Shipment\Api\DueDateLogRepositoryInterface;

class NewOrderUpdate
{
    public const CANCELLED = 'cancelled';

    /**
     * @param RequestInterface $request
     * @param Order $order
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     * @param ShipmentEmail $shipmentEmail
     * @param Data $helper
     * @param SubmitOrderModelAPI $submitOrderModelAPI
     * @param ToggleConfig $toggleConfig
     * @param State $state
     * @param ShipmentHelper $shipmentHelper
     * @param MarketPlaceOrder $marketplaceOrder
     * @param InstoreConfig $inStoreConfig
     * @param History $history
     * @param ShipmentRepository $shipmentRepository
     * @param OrderStatusHistoryInterfaceFactory $historyFactory
     * @param TimezoneInterface $timezone
     * @param DueDateLogFactory $dueDateLogFactory
     * @param DueDateLog $dueDateLogResource
     * @param DueDateLogRepositoryInterface $dueDateLogRepository
     */
    public function __construct(
        protected RequestInterface $request,
        protected Order $order,
        protected PublisherInterface $publisher,
        protected LoggerInterface $logger,
        protected ShipmentEmail $shipmentEmail,
        protected Data $helper,
        protected SubmitOrderModelAPI $submitOrderModelAPI,
        protected ToggleConfig $toggleConfig,
        protected State $state,
        private ShipmentHelper $shipmentHelper,
        private MarketPlaceOrder $marketplaceOrder,
        private readonly InstoreConfig $inStoreConfig,
        private readonly History $history,
        private readonly ShipmentRepository $shipmentRepository,
        private readonly OrderStatusHistoryInterfaceFactory $historyFactory,
        private readonly TimezoneInterface $timezone,
        private readonly DueDateLogFactory $dueDateLogFactory,
        private readonly DueDateLog $dueDateLogResource,
        private readonly DueDateLogRepositoryInterface $dueDateLogRepository
    ) {
    }

    /**
     * Get data from OMS and push in RabbitMQ
     *
     * @param string $orderIncrementId
     * @param string $graphQlRequest
     *
     * @return array
     */
    public function updateOrderStatus($orderIncrementId, $graphQlRequest = null)
    {
        try {
            $requestData = $graphQlRequest ?? $this->request->getContent();
            $logdata = 'Order Status Log : Magento Order Id = '
            .$orderIncrementId. ' Web Hook Request = '. $requestData;
            $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' ' . $logdata);
            $message = '';
            $orderIncrementId = strval($orderIncrementId);
            $orderObj = $this->order->loadByIncrementId($orderIncrementId);
            $requestData = $graphQlRequest ?? $this->request->getContent();
            $requestData = json_decode($requestData, true);

            if ($orderObj->getId() == null){
                $orderIncrementId = $this->helper->createOrderbyGTN($orderIncrementId, $requestData);
                if($orderIncrementId){
                    $orderObj = $this->order->loadByIncrementId(strval($orderIncrementId));
                }
            }

            if ($orderObj->getId()) {
                if ($orderObj->getState() != "canceled") {

                    $requestData['order_increment_id'] = $orderIncrementId;

                    $enableCancel3pUpdatesApiToggle =
                        (bool)$this->toggleConfig->getToggleConfigValue('tiger_d219497_magento_unable_to_cancel_3p_order');

                    if ($enableCancel3pUpdatesApiToggle) {
                        $message = $this->orderUpdateFromOMSRequestEnhancement($orderObj, $requestData, $graphQlRequest ? true : false);
                    } else {
                        $message = $this->orderUpdateFromOMSRequest($orderObj, $requestData, $graphQlRequest ? true : false);
                    }

                } else {
                    $this->logger->info(__METHOD__.':'.__LINE__
                    .': Order is already cancelled. Not able to change status '.$orderIncrementId);
                    $message = 'Order is already cancelled. Not able to change status';
                }

                return ['message' => $message];
            } else {
                $this->logger->info(__METHOD__.':'.__LINE__.': Order not exist.');

                return ['message' => 'Order not exist.'];
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.': OMS Update Exception '.$e->getMessage());

            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Update the order if OMS sent Request and Sent order status email
     * With Shipment creation fix Toggle on
     *
     * @param object $orderObj
     * @param array $requestData
     * @param bool $isMarketplaceCancellationRequest
     *
     * @return string
     */
    public function orderUpdateFromOMSRequest($orderObj, $requestData, $isMarketplaceCancellationRequest = false)
    {
        $orderId     = $orderObj->getId();
        $orderStatus = $requestData["orderStatus"];

        // Check if Marketplace orders can be cancelled
        if (!$this->canCancelMarketplaceOrders($orderId, $requestData)) {
            return 'success';
        }


        try {
            if ((!$isMarketplaceCancellationRequest) && (!$orderObj->hasInvoices() || !$this->shipmentHelper->hasShipmentCreated($orderObj))) {
                // current object assignment needed to make area emulation working.
                $self = $this;

                try {
                    $this->state->emulateAreaCode(
                        \Magento\Framework\App\Area::AREA_FRONTEND,
                        function () use ($self, $orderObj, $orderStatus) {

                            $orderId = $orderObj->getId();

                            if ($orderObj->getStatus() == 'pending') {
                                $this->logger->info(__METHOD__ . ':' . __LINE__
                                    .': Processing start for pending order by OMS request sent status for Order Id: '.
                                    $orderId);
                                /* Update Order Status with New and Deactivate Quote */
                                $this->submitOrderModelAPI->updateOrderWithNewStatus($orderObj);
                                $this->logger->info(__METHOD__ . ':' . __LINE__
                                    .': Processed pending order into new order by OMS request sent status for Order Id: '.
                                    $orderId);
                            }

                            $this->logger->info(__METHOD__ . ':' . __LINE__
                                .': Processing start to create Shipment and Invoice for Order Id: '. $orderId
                            );

                            /* Create Shipment and Invoice for Order*/
                            $enableDuplicateOrderConfirmationEmailsToggle =
                                (bool)$this->toggleConfig->getToggleConfigValue('tiger_d215213_duplicate_order_confirmation_emails');

                            if ($enableDuplicateOrderConfirmationEmailsToggle) {

                                if (strtolower((trim($orderStatus))) === Data::RECEIVED) {
                                    $orderFinalized = $this->submitOrderModelAPI->finalizeOrder($orderObj);

                                    if (!$orderFinalized) {
                                        $this->logger->info(__METHOD__ . ':' . __LINE__
                                            . ': Shipment was not created for Order Id: ' . $orderId);
                                    } else {
                                        $this->logger->info(
                                            __METHOD__ . ':' . __LINE__
                                            . ': Shipment and Invoice has been created successfully for Order Id: ' . $orderId
                                        );
                                    }
                                }
                            } else {
                                $orderFinalized = $this->submitOrderModelAPI->finalizeOrder($orderObj);

                                if (!$orderFinalized) {
                                    $this->logger->info(__METHOD__ . ':' . __LINE__
                                        . ': Shipment was not created for Order Id: ' . $orderId);
                                } else {
                                    $this->logger->info(
                                        __METHOD__ . ':' . __LINE__
                                        . ': Shipment and Invoice has been created successfully for Order Id: ' . $orderId
                                    );
                                }
                            }
                        }
                    );
                } catch (\Exception $exception) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__
                        .':Problem when creating Invoice and shipment for the order GTN: ' . $exception);
                }
            }
            /* Call function to send order status email after receiving status from OMS*/
            return $this->sentEmailAndPublishData($orderObj, $requestData);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__
                .': Error in creating invoice and shipment for order id: '. $orderId . ',' .$e->getMessage());

            return $e->getMessage();
        }
    }

    /**
     * Update the order if OMS sent Request and Sent order status email
     * With Shipment creation fix Toggle on
     *
     * @param object $orderObj
     * @param array $requestData
     * @param bool $isMarketplaceCancellationRequest
     *
     * @return string
     */
    public function orderUpdateFromOMSRequestEnhancement($orderObj, $requestData, $isMarketplaceCancellationRequest = false)
    {
        $orderId = $orderObj->getId();

        if (!$this->canCancelMarketplaceOrders($orderId, $requestData)) {
            return 'success';
        }

        try {
            if (!$isMarketplaceCancellationRequest) {
                if ($this->inStoreConfig->isUpdateDueDateEnabled()) {
                    $this->updateDueDateAndSaveInOrderHistory($orderObj, $requestData);
                }

                if (!$orderObj->hasInvoices() || !$this->shipmentHelper->hasShipmentCreated($orderObj)) {
                    // current object assignment needed to make area emulation working.
                    $self = $this;

                    try {
                        $this->state->emulateAreaCode(
                            \Magento\Framework\App\Area::AREA_FRONTEND,
                            function () use ($self, $orderObj, $requestData) {
                                $orderId = $orderObj->getId();

                                if ($orderObj->getStatus() == 'pending') {
                                    $this->logger->info(__METHOD__ . ':' . __LINE__
                                        . ': Processing start for pending order by OMS request sent status for Order Id: ' .
                                        $orderId);
                                    /* Update Order Status with New and Deactivate Quote */
                                    $this->submitOrderModelAPI->updateOrderWithNewStatus($orderObj);
                                    $this->logger->info(__METHOD__ . ':' . __LINE__
                                        . ': Processed pending order into new order by OMS request sent status for Order Id: ' .
                                        $orderId);
                                }

                                $this->logger->info(__METHOD__ . ':' . __LINE__
                                    . ': Processing start to create Shipment and Invoice for Order Id: ' . $orderId
                                );

                                /* Create Shipment and Invoice for Order*/
                                $enableDuplicateOrderConfirmationEmailsToggle =
                                    (bool)$this->toggleConfig->getToggleConfigValue('tiger_d215213_duplicate_order_confirmation_emails');

                                if ($enableDuplicateOrderConfirmationEmailsToggle) {
                                    $hasReceivedStatus = false;

                                    if (!empty($requestData['shipmentItems']) && is_array($requestData['shipmentItems'])) {
                                        foreach ($requestData['shipmentItems'] as $item) {
                                            $status = strtolower(trim($item['status'] ?? ''));
                                            if ($status === Data::RECEIVED) {
                                                $hasReceivedStatus = true;
                                                break;
                                            }
                                        }
                                    } else {
                                        $this->logger->warning(__METHOD__ . ':' . __LINE__
                                            . ': shipmentItems missing or invalid in request for order ' . $orderObj->getIncrementId());
                                    }

                                    if ($hasReceivedStatus) {
                                        $orderFinalized = $this->submitOrderModelAPI->finalizeOrder($orderObj);

                                        if (!$orderFinalized) {
                                            $this->logger->info(__METHOD__ . ':' . __LINE__
                                                . ': Shipment was not created for Order Id: ' . $orderId);
                                        } else {
                                            $this->logger->info(
                                                __METHOD__ . ':' . __LINE__
                                                . ': Shipment and Invoice has been created successfully for Order Id: ' . $orderId
                                            );
                                        }
                                    } else {
                                        $this->logger->info(__METHOD__ . ':' . __LINE__
                                            . ': Skipped finalizeOrder — no RECEIVED status found for order ' . $orderObj->getIncrementId());
                                    }
                                } else {
                                    $orderFinalized = $this->submitOrderModelAPI->finalizeOrder($orderObj);

                                    if (!$orderFinalized) {
                                        $this->logger->info(__METHOD__ . ':' . __LINE__
                                            . ': Shipment was not created for Order Id: ' . $orderId);
                                    } else {
                                        $this->logger->info(
                                            __METHOD__ . ':' . __LINE__
                                            . ': Shipment and Invoice has been created successfully for Order Id: ' . $orderId
                                        );
                                    }
                                }
                            }
                        );
                    } catch (\Exception $exception) {
                        $this->logger->critical(__METHOD__ . ':' . __LINE__
                            . ': Problem when creating Invoice and shipment for the order GTN: ' . $exception);
                    }
                }
            }
            /* Call function to send order status email after receiving status from OMS*/
            return $this->sentEmailAndPublishData($orderObj, $requestData);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__
                .': Error in creating invoice and shipment for order id: '. $orderId . ',' .$e->getMessage());

            return $e->getMessage();
        }
    }

    /**
     * Sent order status email and publish data in queue
     *
     * @param object $orderObj
     * @param array $requestData
     *
     * @return string
     */
    public function sentEmailAndPublishData($orderObj, $requestData)
    {
        $orderId = $orderObj->getId();
        $orderIncrementId = $orderObj->getIncrementId();
        $this->sentStatusEmail($orderId, $requestData);
        /* End of call function to send order status email after receiving status from OMS */
        $requestData = json_encode($requestData);
        $this->publisher->publish('updateOrderInformation', $requestData);
        $this->logger->info(__METHOD__.':'.__LINE__.': OMS Update has been pushed in Rabbit MQ '.$orderIncrementId);
        $this->logger->info(__METHOD__.':'.__LINE__.': OMS API JSON data for OMS update'. $requestData);

        return 'success';
    }

    /**
     * Sent order status email
     *
     * @param int $orderId
     * @param array $requestData
     *
     * @return array
     */
    public function sentStatusEmail($orderId, $requestData)
    {
        try {
            if (!empty($requestData) && is_array($requestData) && isset($requestData['shipmentItems'])) {
                $requestedShipmentItems = $requestData['shipmentItems'];
                if (!empty($requestedShipmentItems) && is_array($requestedShipmentItems)) {
                    foreach ($requestedShipmentItems as $shipItem) {
                        $fxoShipmentId = $shipItem['shipmentId'] ?? '';
                        $shipmentIds = $this->helper->getShipmentIdByFxoShipmentId($orderId, $fxoShipmentId);
                        $status = $shipItem['status'] ?? '';
                        $shipmentStatus = strtolower(trim($status));
                        if(is_array($shipmentIds) && !empty($shipmentIds)) {
                            foreach ($shipmentIds as $shipmentId) {
                                $this->shipmentEmail->sendEmail($shipmentStatus, $orderId, $shipmentId);
                            }
                        } else {
                            $this->shipmentEmail->sendEmail($shipmentStatus, $orderId, $shipmentIds);
                        }
                        $this->logger->info(
                            __METHOD__.':'.__LINE__.': OMS sending mail is in progress for order '. $orderId
                        );
                    }
                }
            } else if ($this->inStoreConfig->isUpdateDueDateEnabled() && $requestData['productionDueTime']) {
                $this->shipmentEmail->sendEmail(ShipmentEmail::DELIVERY_DATE_UPDATED, $orderId);
            }

            return ['message' => 'success'];
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__
            .': Error to sending status update mail for order '. $orderId . ',' .$e->getMessage());

            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Sent order status email
     *
     * @param int $orderId
     * @param array $requestData
     *
     * @return bool
     */
    public function canCancelMarketplaceOrders($orderId, $requestData):bool
    {
        $return = true;
        if (!empty($requestData) && is_array($requestData)) {
            $requestedShipmentItems = $requestData['shipmentItems'] ?? null;
            if (!empty($requestedShipmentItems) && is_array($requestedShipmentItems)) {
                foreach ($requestedShipmentItems as $shipItem) {
                    $shipmentStatus = strtolower(trim($shipItem['status'] ?? ''));
                    if ($shipmentStatus === self::CANCELLED) {
                        $fxoShipmentId = $shipItem['shipmentId'] ?? '';
                        $shipmentIds = $this->helper->getShipmentIdByFxoShipmentId($orderId, $fxoShipmentId);
                        if (is_array($shipmentIds)) {
                            foreach ($shipmentIds as $shipmentId) {
                                $return = $this->canCancelMiraklOrder($shipmentId);
                                if (!$return) {
                                    break;
                                }
                            }
                        } else {
                            $shipment = $this->helper->getShipmentById($shipmentIds);
                            if ($shipment && $shipment->getMiraklShippingReference()) {
                                // Cancel order in Mirakl
                                $return = $this->marketplaceOrder->cancelOrder($shipment->getMiraklShippingReference());
                                if (!$return) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $return;
    }

    protected function canCancelMiraklOrder($shipmentId) {
        $shipment = $this->helper->getShipmentById($shipmentId);
        if ($shipment && $shipment->getMiraklShippingReference()) {
            // Cancel order in Mirakl
            $return = $this->marketplaceOrder->cancelOrder($shipment->getMiraklShippingReference());
            if (!$return) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $order
     * @param $requestData
     * @return void
     */
    private function updateDueDateAndSaveInOrderHistory($order, $requestData): void
    {
        try {
            if (isset($requestData['productionDueTime']) && isset($requestData['updateDateTime'])){
                $shipments = $order->getShipmentsCollection();
                $orderShipment = $order->getShipmentsCollection()->getFirstItem();
                if ($orderShipment && !$this->dueDateLogRepository->isNewerThanLast($order->getId(), $requestData['updateDateTime'])) {
                    $this->logger->info(__METHOD__ .':' . __LINE__
                        . ': Skipped due date update — due date log has newer or same updated_at for order '
                        . $order->getIncrementId());
                    return;
                }
                if ($shipments->getSize() == 0) {
                    throw new \Exception("There are no shipments for this order");
                }
                $deliveryDate = (new \DateTime($requestData['productionDueTime']))
                    ->format('M j, Y g:i:s A');

                $dateTimeNow = $this->timezone->date()->format('M j, Y g:i:s A');
                $this->updateOrderShippingDescription($order, $deliveryDate);
                $this->addIntoDueDateLog($order, $orderShipment, $deliveryDate, $requestData['updateDateTime']);
                $orderShipment->setData('shipping_due_date', $deliveryDate);
                $orderShipment->addComment(
                    __('%1 | Shipment #%2 Due date updated to %3', $dateTimeNow, $orderShipment->getIncrementId(), $deliveryDate),
                    false,
                    false
                );

                $this->shipmentRepository->save($orderShipment);
                $this->updateStatusHistory($order);
            }
        } catch (\Exception $exception) {
            $this->logger->error("Something went wrong: " . $exception->getMessage());
        }
    }

    /**
     * Updates the status history of the given order with a comment and status details.
     *
     * @param Order $order The order entity for which the status history is being updated.
     * @return void
     */
    private function updateStatusHistory($order) {
        $formattedDate = $this->timezone->date()->format('M j, Y g:i:s A');
        $comment = __('%1 | Due Date Updated | Customer Notified: Yes', $formattedDate);

        $history = $this->historyFactory->create();
        $history->setEntityName(Order::ENTITY);
        $history->setParentId($order->getEntityId());
        $history->setStatus($order->getStatus());
        $history->setComment($comment);
        $history->setIsCustomerNotified(false);
        $history->setIsVisibleOnFront(false);
        $this->history->save($history);
    }

    /**
     * Adds a record to the due date log with details of the old and new due dates for the given shipment.
     *
     * @param Order $order The order associated with the shipment.
     * @param Shipment $shipment The shipment for which the due date change is logged.
     * @param string $deliveryDate The new delivery date to be updated and logged.
     * @return void
     */
    private function addIntoDueDateLog($order, $shipment, $deliveryDate, $updatedAt): void{
        $oldDueDate = $shipment->getData('shipping_due_date');
        $log = $this->dueDateLogFactory->create();
        $log->setData([
            'order_id' => $order->getId(),
            'shipment_id' => $shipment->getId(),
            'old_due_date' => $oldDueDate,
            'new_due_date' => $deliveryDate,
            'updated_at' => $updatedAt
        ]);
        $this->dueDateLogResource->save($log);
    }

    /**
     * @param $order
     * @param $deliveryDate
     * @return void
     */
    private function updateOrderShippingDescription($order, $deliveryDate): void
    {
        $description = $order->getShippingDescription();

        if (!$description) {
            return;
        }

        $parts = explode('-', $description, 2);
        $date = new \DateTimeImmutable($deliveryDate);
        $formattedDate = $date->format('l, F j, g:ia');

        if (count($parts) < 2) {
            $newDescription = $description . ' - ' . $formattedDate;
        } else {
            $carrierPart = trim($parts[0]);
            $newDescription = $carrierPart . ' - ' . $formattedDate;
        }

        $this->dueDateLogRepository->updateOrderShippingDescription($order, $newDescription);
    }
}
