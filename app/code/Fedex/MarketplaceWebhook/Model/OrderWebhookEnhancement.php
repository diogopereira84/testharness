<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\Shipment\Model\SendOrderEmailPublisher;
use Magento\Framework\App\Request\Http as HttpRequest;
use Fedex\MarketplaceWebhook\Model\Middleware\AuthorizationMiddleware;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\Shipment\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Fedex\MarketplaceWebhook\Model\CreateInvoicePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class OrderWebhookEnhancement
{
    private const ITEM_QTY = 'quantity';

    private const ORDER_ITEM_ID = 'order_line_id';

    private const INCREMENT_ID = 'id';

    private const SHIPMENT_LINES = 'shipment_lines';

    private const PAYLOAD = 'payload';

    private const DETAILS = 'details';

    private const CHANGES = 'changes';

    private const FIELD = 'field';

    private const SHIPMENTS = 'SHIPMENTS';

    private const MIRAKL_SHIPPING_ID = 'id';

    private const TO = 'to';

    private const STATUS = 'shipped';

    private const STATUS_SHIPPING = 'shipping';

    private const TRACKING = 'tracking';

    private const CARRIER_CODE = 'carrier_code';

    private const CARRIER_NAME = 'carrier_name';

    private const TRACKING_NUMBER = 'tracking_number';

    private const STATUS_LABEL = 'status';

    private const SHIPPED = 'SHIPPED';

    private const SHIPPING = 'SHIPPING';

    private const ORDER_EMAIL_STATUS_CONFIRMED = 'confirmed';
    private const ORDER_EMAIL_STATUS_COMPLETE = 'complete';

    private const ORDER_EMAIL_STATUS_SHIPPED = 'shipped';

    private const STATETYPE = 'STATE';

    private const ORDER_LINE_STATE = 'ORDER_LINE_STATE';

    private const FROM = 'from';

    private const WAITING_ACCEPTANCE = 'WAITING_ACCEPTANCE';

    private const SHIPMENT_STATUS_SHIPPING = '9';

    private Order|OrderInterface $order;

    private $referenceId;

    private $statusType;

    private $trackingNumberTo;

    private $trackingNumberFrom;

    private $existingShipment;

    private $newTracking;

    /**
     * @param AuthorizationMiddleware $authorizationMiddleware
     * @param HttpRequest $request
     * @param OrderFactory $orderFactory
     * @param OrderConverter $orderConverter
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SendOrderEmailPublisher $sendOrderEmailPublisher
     * @param LoggerInterface $logger
     * @param SubmitOrderHelper $submitOrderHelper
     * @param MiraklOrderHelper $miraklOrderHelper
     * @param \Fedex\MarketplaceWebhook\Model\CreateInvoicePublisher $createInvoicePublisher
     * @param PublisherInterface $publisher
     * @param TrackRepository $trackRepository
     * @param TrackFactory $trackFactory
     * @param MarketPlaceHelper $marketplaceHelper
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private AuthorizationMiddleware     $authorizationMiddleware,
        private HttpRequest                 $request,
        private OrderFactory                $orderFactory,
        private OrderConverter              $orderConverter,
        private ShipmentRepositoryInterface $shipmentRepository,
        private OrderRepositoryInterface    $orderRepository,
        private Data                        $helper,
        private SearchCriteriaBuilder       $searchCriteriaBuilder,
        private SendOrderEmailPublisher     $sendOrderEmailPublisher,
        private LoggerInterface             $logger,
        private SubmitOrderHelper           $submitOrderHelper,
        private MiraklOrderHelper           $miraklOrderHelper,
        private CreateInvoicePublisher      $createInvoicePublisher,
        private PublisherInterface          $publisher,
        private TrackRepository             $trackRepository,
        private TrackFactory                $trackFactory,
        private MarketPlaceHelper           $marketplaceHelper,
        private TimezoneInterface           $timezone,
        readonly private HandleMktCheckout  $handleMktCheckout
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(string $message)
    {
        $content = json_decode($message, true);
        try {
            if (!isset($content[self::PAYLOAD])) {
                return true;
            } elseif (empty($content[self::PAYLOAD][0][self::DETAILS][self::CHANGES] ?? [])) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . json_encode($content));
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Content doesn\'t contains changes');
                return true;
            }

            $changesDetail = $content[self::PAYLOAD][0][self::DETAILS][self::CHANGES];
            if (!$this->hasShipmentUpdate($changesDetail) && !$this->hasAcceptanceStatus($changesDetail)) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . json_encode($content));
                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'There\'s no shipment/tracking or acceptance to update');
                return true;
            }

            $incrementId = $this->getIncrementId($content);
            $this->order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if (!$this->order->getId()) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . " Order not found $incrementId");
                return true;
            }
            if ($this->hasAcceptanceStatus($changesDetail)) {
                $initialStatus = $this->order->getStatus();
                $this->createInvoice($this->order);
                $this->updateOrderStatus($this->order);
                $acceptedItemIds  = $this->getAcceptedItemsFromWebhook($content, $this->order);
                if (!$acceptedItemIds) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . " There's no order_line_id in the request Increment ID: $incrementId, content: $message");
                    return true;
                }
                if(!$this->getShipmentByMiraklReference($content[self::PAYLOAD][0][self::INCREMENT_ID])) {
                    $shipment = $this->createNewShipmentItemsAcceptance(
                        $acceptedItemIds, $this->order, $content[self::PAYLOAD][0][self::INCREMENT_ID], $changesDetail
                    );

                    $this->shipmentRepository->save($shipment);
                }

                /**
                 * Runs after $this->updateOrderStatus($this->order); which will set order
                 * status to either "complete" or "confirmed"
                 */
                if ($initialStatus != self::ORDER_EMAIL_STATUS_CONFIRMED && $initialStatus != self::ORDER_EMAIL_STATUS_COMPLETE) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . " Order already accepted $incrementId");
                    $this->sendOrderConfirmationAndFujitsuReceiptEmails($this->order);
                }

            } elseif($this->hasShipmentUpdate($changesDetail)) {
                $this->existingShipment = $this->getShipmentByMiraklReference($content[self::PAYLOAD][0][self::INCREMENT_ID]);
                if ($this->existingShipment) {
                    $this->trackingHandle($changesDetail, $this->existingShipment);
                    $itemIds    = $this->getItemsFromWebhook($content);
                    if (!empty($itemIds) && !$this->hasAll3pShipmentCreated($this->order)) {
                        $this->updateShipmentItemsQtyAcceptance($this->existingShipment, $itemIds);
                    }

                    if ($this->existingShipment && $this->checkShippedQty()) {

                        $this->updateShipmentStatusShipped($this->existingShipment);
                    }else{
                        $this->updateShipmentStatus($this->existingShipment);
                    }

                    $this->logger->info(__METHOD__ . ':' . __LINE__ . " Order tracking added by seller: $incrementId");

                    if ($this->newTracking) {
                        $this->sendOrderEmailPublisher->execute(
                            self::ORDER_EMAIL_STATUS_SHIPPED,
                            (int)$this->order->getId(),
                            (int)$this->existingShipment->getId(),
                            $this->newTracking
                        );
                    }

                    $this->logger->info(__METHOD__ . ':' . __LINE__ . " Shipment email added to the queue: $incrementId");

                } else {
                    $itemIds          = $this->getItemsFromWebhook($content);
                    $this->existingShipment         = $this->createNewShipmentItemsAcceptance(
                        $itemIds, $this->order, $content[self::PAYLOAD][0][self::INCREMENT_ID], $changesDetail
                    );

                    $this->trackingHandle($changesDetail, $this->existingShipment);

                    if (!empty($itemIds) && !$this->hasAll3pShipmentCreated($this->order)) {
                        $this->updateShipmentItemsQtyAcceptance($this->existingShipment, $itemIds);
                    }
                    if ($this->existingShipment && $this->checkShippedQty()) {

                        $this->updateShipmentStatusShipped($this->existingShipment);
                    }else{
                        $this->updateShipmentStatus($this->existingShipment);
                    }

                    $this->logger->info(__METHOD__ . ':' . __LINE__ . " Order tracking added by seller: $incrementId");

                    if ($this->newTracking) {
                        $this->sendOrderEmailPublisher->execute(
                            self::ORDER_EMAIL_STATUS_SHIPPED,
                            (int) $this->order->getId(),
                            (int) $this->existingShipment->getId(),
                            $this->newTracking
                        );
                    }

                    $this->logger->info(__METHOD__ . ':' . __LINE__ . " Shipment email added to the queue: $incrementId");


                }
                $this->updateOrderStatus($this->order);
            }

            if ($this->hasAll3pShipmentCreated($this->order)) {
                $message = $this->createDataForDeliveryNotification($this->order);
                $this->publisher->publish('sendDeliveryNotification', json_encode($message));
                $this->logger->info(__METHOD__ . ':' . __LINE__ . " Delivery notification sent to the queue: $incrementId");
            }
        } catch (\Exception $e) {
            // Even if there is an error, we should pass 200 to webhook so it doesn't hang
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . json_encode($content));
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * @param $changesDetail
     * @param $shipment
     * @return void
     */
    public function trackingHandle($changesDetail, $shipment): void
    {
        $track = $this->getTrackingByNumber($shipment->getId());

        if ($this->newTracking = $this->hasTrackingChanged($changesDetail, $track)) {
            $this->createTrackingData($shipment, $this->newTracking);
        }
    }

    /**
     * Create new shipment items acceptance.
     *
     * @param $itemIds
     * @param $order
     * @param $acceptanceOrderId
     * @param $changesDetail
     * @return Shipment
     * @throws LocalizedException
     */
    private function createNewShipmentItemsAcceptance($itemIds, $order, $acceptanceOrderId, $changesDetail): Shipment
    {
        $shipment = $this->orderConverter->toShipment($order);
        $totalQty = 0;
        foreach ($itemIds as $itemId => $qty) {
            foreach ($order->getAllVisibleItems() as $item) {
                if (!$item->getQtyToShip() || $item->getIsVirtual()) {
                    continue;
                }

                if ($item->getId() == $itemId) {
                    $shipmentItem = $this->orderConverter->itemToShipmentItem($item);
                    $shipment->addItem($shipmentItem);
                    $totalQty += $qty;
                }
            }
        }

        return $this->createShipmentAcceptanceForItem($shipment, $order, (string)$acceptanceOrderId, $changesDetail, $totalQty);
    }

    /**
     * Update shipment acceptance.
     *
     * @param $shipment
     * @param $itemIds
     * @return void
     */
    private function updateShipmentItemsQtyAcceptance($shipment, $itemIds): void
    {
        $shipmentItems = $shipment->getItems();
        foreach ($itemIds as $itemId => $qty) {
            foreach ($shipmentItems as $item) {
                if ($item->getOrderItemId() == $itemId) {
                    $orderItem = $item->getOrderItem();
                    if ($orderItem->getQtyShipped() + $qty <= $orderItem->getQtyOrdered() ) {
                        $orderItem->setQtyShipped($orderItem->getQtyShipped() + $qty);
                        $orderItem->save();
                        $item->setQty($item->getQty() + $qty);
                    }
                }
            }
        }
        $this->shipmentRepository->save($shipment);
    }

    /**
     * Has tracking changed.
     *
     * @param $changesDetail
     * @return false|mixed
     */
    private function hasTrackingChanged($changesDetail, $track = null)
    {
        foreach ($changesDetail as $changes) {
            if ($changes[self::FIELD] !== self::SHIPMENTS) {
                continue;
            }

            $statusLabel = $changes[self::TO][self::STATUS_LABEL] ?? null;
            if ($statusLabel !== self::SHIPPING && $statusLabel !== self::SHIPPED) {
                continue;
            }

            $trackingTo = $changes[self::TO][self::TRACKING][self::TRACKING_NUMBER] ?? null;
            $trackingFrom = $changes[self::FROM][self::TRACKING][self::TRACKING_NUMBER] ?? null;

            if (!empty($track) && $trackingTo == $track->getTrackNumber()) {
                continue;
            }

            if ($trackingTo !== $trackingFrom) {
                $this->trackingNumberTo = $trackingTo;
                $this->trackingNumberFrom = $trackingFrom;
                return $changes[self::TO][self::TRACKING];
            }
        }
        return false;
    }

    /**
     * Get total qty ordered.
     *
     * @param $order
     * @return int|mixed
     */
    public function getTotalQtyOrdered($order): mixed
    {
        $order         = $this->orderRepository->get($order->getId());
        $totalQty      = 0;
        $allItems      = $order->getAllItems();
        $filteredItems = array_filter($allItems, function ($item) {
            return $item->getMiraklShopId() != null;
        });
        $filteredItems = array_values($filteredItems);
        foreach ($filteredItems as $item) {
            /** @var CartItemInterface $item */
            $totalQty+=$item->getQtyOrdered();

        }
        return $totalQty;
    }

    /**
     * Create a shipment for 3p items.
     *
     * @param Shipment $shipment
     * @param \Magento\Sales\Model\Order $order
     * @param string $miraklShippingId
     * @throws LocalizedException
     */
    private function createShipmentForItem(Shipment $shipment, Order $order, string $miraklShippingId)
    {
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->setMiraklShippingId($miraklShippingId);
        $this->shipmentRepository->save($shipment);
        $this->orderRepository->save($order);

        return $shipment;
    }

    /**
     * Create a shipment acceptance for 3p items.
     *
     * @param Shipment $shipment
     * @param Order $order
     * @param string $acceptanceOrderId
     * @param $changesDetail
     * @param $totalQty
     * @return Shipment
     * @throws LocalizedException
     */
    private function createShipmentAcceptanceForItem(
        Shipment $shipment,
        Order $order,
        string $acceptanceOrderId,
        $changesDetail,
        $totalQty
    ): Shipment
    {
        $miraklShippingId = $this->getMiraklWebhookShippingId($changesDetail);
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->setMiraklShippingId($miraklShippingId);
        $shipment->setMiraklShippingReference($acceptanceOrderId);
        $shipment->setShipmentStatus(self::SHIPMENT_STATUS_SHIPPING);

        $additionalData = $this->marketplaceHelper->getOrderItemMiraklShippingData($shipment);

        if ($additionalData) {
            if (isset($additionalData['reference_id'])) {
                $shipment->setFxoShipmentId($additionalData['reference_id']);
            }
            if (isset($additionalData['deliveryDate'])) {
                $shippingDueDate = $this->timezone->date(new \DateTime($additionalData['deliveryDate']))->format('Y-m-d') . ' 00:00:00';
                $shipment->setShippingDueDate($shippingDueDate);
            }
        }

        $shipment->setTotalQty($totalQty);
        $this->orderRepository->save($order);

        return $shipment;
    }

    /**
     * Update tracking and status shipped.
     *
     * @param Shipment|ShipmentInterface $shipment
     * @param array $changesDetail
     * @return void
     */
    private function updateShipmentStatusShipped(Shipment|ShipmentInterface $shipment)
    {
        $shipStatus = $this->helper->getShipmentStatus(self::STATUS);
        $shipment->setShipmentStatus($shipStatus);
        $shipment->setPickupAllowedUntilDate(null);
        $this->shipmentRepository->save($shipment);
    }

    /**
     * Update tracking and status shipped.
     *
     * @param Shipment|ShipmentInterface $shipment
     * @param array $changesDetail
     * @return void
     */
    private function updateShipmentStatus(Shipment|ShipmentInterface $shipment)
    {
        $shipStatus = $this->helper->getShipmentStatus(self::STATUS_SHIPPING);
        $shipment->setShipmentStatus($shipStatus);
        $shipment->setPickupAllowedUntilDate(null);
        $this->shipmentRepository->save($shipment);
    }

    /**
     * Check if the update is for shipment status and has tracking data.
     *
     * @param array $changesDetail
     * @return boolean
     */
    private function hasShipmentUpdate($changesDetail)
    {
        foreach($changesDetail as $changes) {
            $updateType   = $changes[self::FIELD];
            if ($updateType == self::SHIPMENTS &&
                ($changes[self::TO][self::STATUS_LABEL] == self::SHIPPING
                    || $changes[self::TO][self::STATUS_LABEL] == self::SHIPPED)) {

                $hasTracking      = $changes[self::TO][self::TRACKING][self::CARRIER_CODE] ?? null;
                $this->statusType = $changes[self::TO][self::STATUS_LABEL];
                if ($hasTracking && ($this->statusType == self::SHIPPED || $this->statusType == self::SHIPPING)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if the update is for acceptance status.
     *
     * @param array $changesDetail
     * @return boolean
     */
    private function hasAcceptanceStatus($changesDetail)
    {
        foreach($changesDetail as $changes) {
            $updateType       = $changes[self::FIELD];
            $orderLineItems   = isset($changes[self::ORDER_ITEM_ID]) ? $changes[self::ORDER_ITEM_ID] : null;
            if (($updateType == self::STATETYPE || $updateType == self::ORDER_LINE_STATE)
                && $changes[self::TO] == self::SHIPPING && $orderLineItems) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get shipment by mirakl_shipping_id.
     *
     * @param string $miraklShippingId
     * @return array
     */
    private function getShipment($miraklShippingId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('mirakl_shipping_id', $miraklShippingId)
            ->create();

        $shipment = $this->shipmentRepository->getList($searchCriteria)->getFirstItem();

        return (!empty($shipment->getData())) ? $shipment : null;
    }

    /**
     * Get tracking by track_number and shipment_id.
     *
     * @param string $miraklShippingId
     * @return array
     */
    private function getTrackingByNumber($shipmentId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_id', $shipmentId);


        if ($this->trackingNumberTo != null) {
            $searchCriteria->addFilter('track_number', $this->trackingNumberFrom);
        }

        $searchCriteria = $searchCriteria->create();

        $track = $this->trackRepository->getList($searchCriteria)->getFirstItem();

        return (!empty($track->getData())) ? $track : null;
    }

    /**
     * Get tracking by track_number and shipment_id.
     *
     * @param $track
     * @param $shipment
     * @param $newTrackingData
     * @return void
     */
    private function createTrackingData($shipment, $newTrackingData)
    {
        $title = (isset($newTrackingData['carrier_name']) && $newTrackingData['carrier_name'] === 'fedex_office')
            ? 'Fedex Office'
            : 'Federal Express';

        $track = $this->trackFactory->create();
        $track->setNumber($newTrackingData['tracking_number']);
        $track->setCarrierCode($newTrackingData['carrier_name']);
        $track->setTitle($title);
        $shipment->addTrack($track);
        $this->shipmentRepository->save($shipment);
    }

    /**
     * Get shipment by mirakl_shipping_reference.
     *
     * @param string $miraklShippingId
     * @return mixed
     */
    public function getShipmentByMiraklReference($miraklShippingId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('mirakl_shipping_reference', $miraklShippingId)
            ->create();

        $shipment = $this->shipmentRepository->getList($searchCriteria)->getFirstItem();

        return (!empty($shipment->getData())) ? $shipment : null;
    }

    /**
     * Get items from webhook payload.
     *
     * @param array $content
     * @return void
     */
    private function getItemsFromWebhook($content)
    {
        $shipmentLines = $this->getShipmentLines($content);

        if (empty($shipmentLines)) {
            return null;
        }

        $itemIds = [];
        foreach ($shipmentLines as $itemId) {
            $itemIds[$itemId[self::ORDER_ITEM_ID]] = $itemId[self::ITEM_QTY];
        }

        return $itemIds;
    }

    /**
     * Get accepted items from webhook payload.
     *
     * @param array $content
     * @return array
     */
    public function getAcceptedItemsFromWebhook($content, $order)
    {
        $shipmentLines = $this->getAcceptedItemsLines($content);
        $itemIds = [];

        foreach($order->getAllVisibleItems() as $item){
            foreach ($shipmentLines as $itemId) {
                if(!isset($itemId[self::ORDER_ITEM_ID])){
                    continue;
                }
                if ($itemId[self::ORDER_ITEM_ID] == $item->getId()) {
                    $itemIds[$itemId[self::ORDER_ITEM_ID]] = $item->getQtyOrdered();
                }
            }
        }

        return $itemIds;
    }

    /**
     * Get order increment id from webhook.
     *
     * @param array $content
     * @return mixed
     */
    private function getIncrementId(array $content)
    {
        $incrementId =  explode('-', $content[self::PAYLOAD][0][self::INCREMENT_ID]);
        return $incrementId[0];
    }

    /**
     * Get items from webhook.
     *
     * @param array $content
     * @return mixed
     */
    public function getShipmentLines(array $content): mixed
    {
        $changes = $content[self::PAYLOAD][0][self::DETAILS][self::CHANGES];
        foreach ($changes as $item) {
            $to = $item[self::TO];
            if (is_array($to) && $to[self::STATUS_LABEL] == self::SHIPPED || $to[self::STATUS_LABEL] == self::SHIPPING) {
                return $to[self::SHIPMENT_LINES];
            }
        }

        return [];
    }

    /**
     * Get accepted items from webhook.
     *
     * @param array $content
     * @return mixed
     */
    public function getAcceptedItemsLines(array $content): mixed
    {
        return $content[self::PAYLOAD][0][self::DETAILS][self::CHANGES];
    }

    /**
     * Set order status.
     *
     * @param $order
     * @return void
     */
    public function updateOrderStatus($order): void
    {
        if ($this->validateShipments($order)) {
            $this->helper->updateStatusOfOrder(
                "complete",
                "complete",
                $order
            );
        } else {
            $this->helper->updateStatusOfOrder(
                "confirmed",
                "in_process",
                $order
            );
        }
    }

    /**
     * Validating shipments.
     *
     * @param Order $order
     * @return boolean
     */
    public function validateShipments(Order $order): bool
    {
        $order = $this->orderRepository->get($order->getId());
        $result = true;
        // Check items in the quote
        foreach ($order->getAllItems() as $item) {
            /** @var CartItemInterface $item */
            if ($item->getQtyOrdered() != $item->getQtyShipped()) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    private function checkShippedQty(): bool
    {
        $shippedItemsFromShipment = $this->getItemsForCurrentShipment();

        $allItems      = $this->order->getAllItems();
        $itemsForCurrentShipment = [];
        foreach ($allItems as $item) {
            if (in_array($item->getItemId(), $shippedItemsFromShipment)) {
                $itemsForCurrentShipment[] = $item;
            }
        }

        $hasBeenFullyShipped = true;
        foreach ($itemsForCurrentShipment as $item) {
            if (!$item->getQtyShipped()) {
                $hasBeenFullyShipped = false;
                break;
            }
        }

        return $hasBeenFullyShipped;
    }

    /**
     * Return Items List for current Shipment
     *
     * @return array
     */
    private function getItemsForCurrentShipment()
    {
        if ($this->existingShipment) {
            $shippedItemsFromShipment = $this->existingShipment->getItemsCollection();
            $shippedItems = [];
            foreach ($shippedItemsFromShipment as $shippedItem) {
                $shippedItems[] = $shippedItem->getOrderItemId();
            }

            return $shippedItems;
        }

        return [];
    }

    /**
     * Has all 3p shipments created.
     *
     * @param Order $order
     * @return boolean
     */
    public function hasAll3pShipmentCreated(Order $order): bool
    {
        $order         = $this->orderRepository->get($order->getId());
        $result        = true;
        $allItems      = $order->getAllItems();
        $filteredItems = array_filter($allItems, function ($item) {
            return $item->getMiraklShopId() != null;
        });
        $filteredItems = array_values($filteredItems);
        foreach ($filteredItems as $item) {
            /** @var CartItemInterface $item */
            if ($item->getQtyOrdered() != $item->getQtyShipped()) {
                $result = false;
                break;
            }

            $this->referenceId = $this->getReferenceId($item->getAdditionalData());
        }

        return $result;
    }

    /**
     * Get reference ID.
     *
     * @param $additionalData
     * @return mixed
     */
    public function getReferenceId($additionalData)
    {
        $data = json_decode($additionalData, true);

        $referenceId = !empty($data['mirakl_shipping_data']['reference_id'])
            ? $data['mirakl_shipping_data']['reference_id']
            : '';
        return $referenceId;
    }

    /**
     * Create Invoice
     *
     * @param Order $order
     * @return void
     */
    public function createInvoice(Order $order) {
        if ($this->miraklOrderHelper->isFullMiraklOrder($order)
            && !$order->hasInvoices()) {
            // Generate order invoice
            $this->createInvoicePublisher->execute((int)$order->getId());
        }
    }

    /**
     * Create delivery notifications data.
     *
     * @param Order $order
     * @return array
     */
    public function createDataForDeliveryNotification(Order $order)
    {
        $data = [
            'retailTransactionId' => $order->getPayment()->getData('retail_transaction_id'),
            'orderNumber'         => $order->getIncrementId(),
            'deliveryRefId'       => $this->referenceId
        ];

        return $data;
    }

    /**
     * Get mirakl shipping id from webhook.
     *
     * @param array $changesDetail
     * @return null
     */
    private function getMiraklWebhookShippingId(array $changesDetail)
    {
        return $changesDetail[0][self::TO][self::MIRAKL_SHIPPING_ID] ?? null;
    }

    /**
     * Send Emails
     *
     * @param Order $order
     * @return void
     */
    public function sendOrderConfirmationAndFujitsuReceiptEmails(Order $order)
    {
        try {
            if ($this->miraklOrderHelper->isFullMiraklOrder($order)) {
                $this->sendOrderEmailPublisher->execute(
                    self::ORDER_EMAIL_STATUS_CONFIRMED,
                    (int)$order->getId()
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
