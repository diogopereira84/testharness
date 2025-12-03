<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Helper;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Fedex\Shipment\Api\NewOrderUpdateInterface;
use \Fedex\Shipment\Model\OrderReferenceFactory;
use \Fedex\Shipment\Model\OrderValueFactory;
use \Fedex\Shipment\Model\ShipmentFactory;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\OrderFactory;
use \Psr\Log\LoggerInterface;
use \Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use \Fedex\SubmitOrderSidebar\Model\TransactionApi\RateQuoteAndTransactionApiHandler;
use \Magento\Quote\Model\QuoteFactory;
use \Magento\Quote\Model\QuoteManagement;
use \Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Magento\Quote\Api\CartRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\HTTP\Client\Curl;
use \Magento\Quote\Model\Quote\ItemFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceHelper;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Api\Data\ShipmentInterface;
/**
 * Class Data toadd functionality of Order Status Update
 */
class Data extends AbstractHelper
{
    private const DELIVERED = 'delivered';
    private const INPROCESS = 'in_progress';
    private const CANCELED = 'canceled';
    private const COMPLETE = 'complete';
    private const CONFIRMED = 'confirmed';
    private const NEW = 'new';
    private const SHIPMENTCANCELED = "cancelled";
    private const READYFORPICKUP = 'ready_for_pickup';
    private const SHIPPED = 'shipped';
    const RECEIVED = 'received';
    const SHIPPING_TYPE_PICKUP = 'fedexshipping_PICKUP';

    private $comment = '';

    /**
     * @param Context $context
     * @param ShipmentFactory $shipmentStatusFactory
     * @param OrderReferenceFactory $orderReferenceFactory
     * @param OrderValueFactory $orderValueFactory
     * @param LoggerInterface $logger
     * @param Order $order
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param TrackFactory $trackFactory
     * @param ShipmentRepository $shipmentRepository
     * @param CreditmemoSender $creditmemoSender
     * @param CreditmemoLoader $creditmemoLoader
     * @param CreditmemoManagementInterface $creditmemoManagementInterface
     * @param Registry $registry
     * @param SubmitOrderApi $submitOrderApi
     * @param RateQuoteAndTransactionApiHandler $rateQuoteTransactionApiHalder
     * @param QuoteFactory $quoteFactory
     * @param QuoteManagement $quoteManagement
     * @param ToggleConfig $toggleConfig
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Curl $curl
     * @param ItemFactory $itemFactory
     * @param SerializerInterface $serializer
     * @param ShopManagement $shopManagement
     * @param MarketplaceHelper $marketplaceHelper
     * @param MiraklOrderHelper $miraklOrderHelper
     * @param ItemRepository $itemRepository
     */
    public function __construct(
        Context                  $context,
        protected ShipmentFactory          $shipmentStatusFactory,
        protected OrderReferenceFactory    $orderReferenceFactory,
        protected OrderValueFactory        $orderValueFactory,
        protected LoggerInterface          $logger,
        public Order                    $order,
        private OrderFactory             $orderFactory,
        private OrderRepositoryInterface $orderRepository,
        protected TrackFactory             $trackFactory,
        private ShipmentRepository       $shipmentRepository,
        private CreditmemoSender         $creditmemoSender,
        protected CreditmemoLoader                  $creditmemoLoader,
        private CreditmemoManagementInterface     $creditmemoManagementInterface,
        private Registry                          $registry,
        protected SubmitOrderApi                    $submitOrderApi,
        protected RateQuoteAndTransactionApiHandler $rateQuoteTransactionApiHalder,
        protected QuoteFactory                      $quoteFactory,
        protected QuoteManagement                   $quoteManagement,
        protected ToggleConfig                      $toggleConfig,
        protected CartRepositoryInterface           $quoteRepository,
        protected StoreManagerInterface             $storeManager,
        protected ProductRepositoryInterface        $productRepository,
        protected SearchCriteriaBuilder             $searchCriteriaBuilder,
        protected Curl                              $curl,
        protected ItemFactory $itemFactory,
        protected SerializerInterface $serializer,
        protected ShopManagement $shopManagement,
        private MarketplaceHelper $marketplaceHelper,
        private MiraklOrderHelper $miraklOrderHelper,
        private ItemRepository $itemRepository,
        private readonly HandleMktCheckout $handleMktCheckout
    ) {
        parent::__construct($context);
    }

    /**
     * Insert Order Reference , Fxo Work Order Number in database
     *
     * @param array $orderStatusUpdateRequest
     * @return array
     */
    public function insertOrderReference($orderStatusUpdateRequest)
    {
        $fxoWorkOrderNumber = "";
        $customerOrderNumber = "";
        $orderCreatedBySystem = "";
        $transactionId = "";
        if (!empty($orderStatusUpdateRequest)) {
            if (isset($orderStatusUpdateRequest["fxoWorkOrderNumber"])) {
                $fxoWorkOrderNumber = $orderStatusUpdateRequest["fxoWorkOrderNumber"];
            }
            if (isset($orderStatusUpdateRequest["customerOrderNumber"])) {
                $customerOrderNumber = $orderStatusUpdateRequest["customerOrderNumber"];
            }
            if (isset($orderStatusUpdateRequest["orderCreatedBySystem"])) {
                $orderCreatedBySystem = $orderStatusUpdateRequest["orderCreatedBySystem"];
            }
            if (isset($orderStatusUpdateRequest["transactionId"])) {
                $transactionId = $orderStatusUpdateRequest["transactionId"];
            }

            if ($customerOrderNumber) {
                $this->setOrderValueNumber(
                    $customerOrderNumber,
                    $transactionId,
                    $fxoWorkOrderNumber,
                    $orderCreatedBySystem
                );
            }
        }
        return true;
    }

    /**
     * Set order value number
     *
     * @param int $customerOrderNumber
     * @param int $transactionId
     * @param int $fxoWorkOrderNumber
     * @param string $orderCreatedBySystem
     */
    public function setOrderValueNumber(
        $customerOrderNumber,
        $transactionId,
        $fxoWorkOrderNumber,
        $orderCreatedBySystem
    )
    {
        try {
            $orderValue = $this->orderValueFactory->create();
            $orderValueNumber = $orderValue->load($customerOrderNumber, "customer_order_number");

            if ($orderValueNumber->getId()) {
                $orderValueNumber->setFxoWorkOrderNumber($fxoWorkOrderNumber);
                $orderValueNumber->setTransactionId($transactionId);
                $orderValueNumber->setCustomerOrderNumber($customerOrderNumber);
                $orderValueNumber->setOrderCreatedSystem($orderCreatedBySystem);
                $orderValueNumber->save();
            } else {
                $orderValue->setFxoWorkOrderNumber($fxoWorkOrderNumber);
                $orderValue->setTransactionId($transactionId);
                $orderValue->setCustomerOrderNumber($customerOrderNumber);
                $orderValue->setOrderCreatedSystem($orderCreatedBySystem);
                $orderValue->save();
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':'.$transactionId.' saved successfully.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
        }
    }

    /**
     * Get shipment status
     *
     * @param array $shipmentStatus
     * @return string
     */
    public function getShipmentStatus($shipmentStatus, $findByStatusValue = false)
    {
        try {
            if (!$shipmentStatus) {
                return;
            }
            $collection = $this->shipmentStatusFactory->create()->getCollection();
            $statusdata = [];
            foreach ($collection as $shipmentvalue) {
                $statusdata[trim($shipmentvalue->getData("key"))] = trim($shipmentvalue->getData("value"));
            }
            if (!$findByStatusValue && isset($statusdata[trim($shipmentStatus)])) {
                return $statusdata[trim($shipmentStatus)];
            } elseif ($findByStatusValue && array_search(trim($shipmentStatus), $statusdata)) {
                return array_search(trim($shipmentStatus), $statusdata);
            } else {
                return;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get shipment status by value
     *
     * @param string $shipmentStatus
     * @return string
     */
    public function getShipmentStatusByValue($shipmentStatus)
    {
        try {
            $collection = $this->shipmentStatusFactory->create()->getCollection();
            $statusdata = [];
            foreach ($collection as $shipmentvalue) {
                $statusdata[trim((string)$shipmentvalue->getData("value"))] = trim((string)$shipmentvalue->getData("key"));
            }
            if (isset($statusdata[trim((string)$shipmentStatus)])) {
                return $statusdata[$shipmentStatus];
            } else {
                return;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get order id from shipment id
     *
     * @param array $shipmentId
     * @return int
     */
    public function getOrderIdByShipmentId($shipmentId)
    {
        try {
            $shipment = $this->getShipmentById($shipmentId);

            return $shipment->getOrder()->getId();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get shipment item data qty from shipment id
     *
     * @param array $shipmentId
     * @return array
     */
    public function getOrderItemIdByShipmentId($shipmentId)
    {
        try {
            $shipment = $this->getShipmentById($shipmentId);
            $shipmentItems = [];
            foreach ($shipment->getItemsCollection() as $item) {
                if ($shipment->getMiraklShippingReference()) {
                    /** @var \Magento\Sales\Model\Order\Item $orderItem */
                    $orderItem = $this->itemRepository->get($item->getOrderItemId());
                    $shipmentItems[$item->getOrderItemId()]["qty"] = $orderItem->getQtyOrdered();
                } else {
                    $shipmentItems[$item->getOrderItemId()]["qty"] = $item->getQty();
                }
            }
            return $shipmentItems;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }

        return true;
    }

    /**
     * Get shipment items name and qty from shipment id
     *
     * @param $shipmentId
     * @return array
     */
    public function getShipmentItems($shipmentId)
    {
        try {
            $shipment = $this->getShipmentById($shipmentId);
            $shipmentItems = [];
            foreach ($shipment->getItemsCollection() as $item) {
                $shipmentItems[$item->getOrderItemId()]["name"] = $item->getName();
                $shipmentItems[$item->getOrderItemId()]["qty"] = $item->getQty();
            }

            return $shipmentItems;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Order $order
     * @param float|array $shippingAmount3p
     * @return array
     */
    public function getOrderItems(Order $order, $shippingAmount3p): array
    {
        $shipmentItems = [];
        foreach ($order->getItemsCollection() as $item) {
            $shipmentItems[$item->getItemId()]["name"] = $item->getName();
            $shipmentItems[$item->getItemId()]["qty"] = $item->getQtyOrdered();
            $shipmentItems[$item->getItemId()]["row_total"] = $item->getRowTotal();
            $shipmentItems[$item->getItemId()]["is_child"] = $item->getParentItemId() ? true : false;

            if ($item->getMiraklOfferId()) {
                $shipmentItems[$item->getItemId()] = array_merge(
                    $shipmentItems[$item->getItemId()],
                    $this->getMiraklItemInfo($item, $shippingAmount3p[$item->getItemId()])
                );
            } elseif ($order->getShippingMethod() !== self::SHIPPING_TYPE_PICKUP) {
                $isExpectedDeliveryDateEnabled =
                    (bool) $this->toggleConfig->getToggleConfigValue('sgc_enable_expected_delivery_date');

                if ($isExpectedDeliveryDateEnabled) {
                    $shippingDescription = explode(' - ', $order->getShippingDescription() ?? '');
                    if (isset($shippingDescription[0])) {
                        $shipmentItems[$item->getItemId()]["shipping_label"] = $shippingDescription[0];
                    }
                    if (isset($shippingDescription[1])) {
                        $shipmentItems[$item->getItemId()]["shipping_expected_delivery_date"] = $shippingDescription[1];
                    }
                } else {
                    $shipmentItems[$item->getItemId()]["shipping_label"] =
                        explode(' - ', $order->getShippingDescription() ?? '')[0];
                }
                // If the shipping total is null, we need to set it to 0 to avoid errors in the shipment email
                $shipmentItems[$item->getItemId()]["shipping_total"] = $order->getShippingAmount() ?? 0;
            }
        }
        return $shipmentItems;
    }

    /**
     * @param $item
     * @param $shippingAmount3p
     * @return array
     */
    protected function getMiraklItemInfo($item, $shippingAmount3p): array
    {
        $sellerShopProduct = $this->shopManagement->getShopByProduct($item->getProduct());
        $shopAltName = $sellerShopProduct->getSellerAltName();
        $additionalData = json_decode($item->getAdditionalData() ?? '{}');

        return [
            'mirakl_shop_name' => $shopAltName,
            'mirakl_shipping_label' => $additionalData->mirakl_shipping_data->shipping_type_label ??
                $item->getMiraklShippingTypeLabel(),
            'mirakl_shipping_expected_delivery' => $additionalData?->mirakl_shipping_data?->deliveryDateText ?? false,
            'mirakl_shipping_total' => $shippingAmount3p,
            'surcharge' => $additionalData->mirakl_shipping_data->surcharge_amount ?? 0
        ];
    }

    /**
     * Use to generate refund
     *
     * @param $orderId
     * @param $orderStatus
     * @param $orderItemId
     * @param $order
     * @param $shipment
     * @return bool
     */
    public function generateRefund($orderId, $orderStatus, $orderItemId, $order, $shipment): bool
    {
        try {
            $creditMemoData = [];
            if ($orderStatus == self::CANCELED ||
                ($orderStatus == '' && $this->isMixedOrder($order))
            ) {
                $creditMemoData['shipping_amount'] = $this->getShippingAmountByOrder($order, $shipment);
            } else {
                $creditMemoData['shipping_amount'] = 0;
            }

            $creditMemoData['do_offline'] = 1;
            $creditMemoData['adjustment_positive'] = 0;
            $creditMemoData['adjustment_negative'] = 0;
            $creditMemoData['items'] = $orderItemId;

            $this->creditmemoLoader->setOrderId($orderId); //pass order id
            $this->creditmemoLoader->setCreditmemo($creditMemoData);
            $creditmemo = $this->creditmemoLoader->load();

            if ($creditmemo) {
                if (!$creditmemo->isValidGrandTotal()) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Credit memo total must be positive.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The credit memo\'s total must be positive.')
                    );
                }
                $creditmemoManagement = $this->creditmemoManagementInterface;

                $orderOldState = $order->getState();
                $orderOldStatus = $order->getStatus();

                $creditmemoManagement->refund($creditmemo, (bool)$creditMemoData['do_offline']);

                $shipmentStatusId = $shipment->getShipmentStatus();
                $shipmentStatus = $this->getShipmentStatusByValue($shipmentStatusId);

                // Reset order status to old status if mixed order is cancelled
                if ($this->isMixedOrder($order)
                    && $shipmentStatus === NewOrderUpdateInterface::CANCELLED
                    && !$this->isAllItemsCancelled($order)) {
                    //Reload order to get latest values
                    $order = $this->orderRepository->get($orderId);
                    $this->updateStatusOfOrder($orderOldStatus, $orderOldState, $order);
                }
            }

            if ($this->registry->registry('current_creditmemo')) {
                $this->registry->unregister('current_creditmemo');
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ':' . $orderId . 'Credit memo generated successfully');

            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' . $orderId .
                ' Issue in generate Credit memo' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get shipping amount from order
     * @param $order
     * @param $shipment
     * @return array|int
     */
    public function getShippingAmountByOrder($order, $shipment)
    {
        try {
            $shippingAmount = 0;

            if (!$shipment->getMiraklShippingReference()) {
                $shippingAmount = $order->getShippingAmount();
            }
            return $shippingAmount;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get grand total from order
     *
     * @param array $orderId
     * @return string
     */
    public function getGrandTotalByOrder($orderId)
    {
        try {
            $orderObj = $this->orderRepository->get($orderId);
            $orderIncrementId = $orderObj->getIncrementId();
            $orderdata = $this->order->loadByIncrementId($orderIncrementId);

            return $orderdata->getGrandTotal();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get order status from all shipment status
     *
     * @param array $orderId
     * @return array|string
     */
    public function detetermineOrderStatus(int $orderId): array|string
    {
        try {
            $shipmentIds = $this->getShipmentIds($orderId);
            $shipmentStatus = [];
            $shipmentStatusArray = [];
            if (!empty($shipmentIds)) {
                foreach ($shipmentIds as $shipmentId) {
                    $shipment = $this->getShipmentById($shipmentId);
                    $shipmentStatusKey = $shipment->getShipmentStatus();
                    if (!$shipmentStatusKey) {
                        continue;
                    }
                    $shipmentStatusKey = $this->getShipmentStatusByValue($shipmentStatusKey);
                    $shipmentStatus[] = strtolower(trim($shipmentStatusKey));
                    $shipmentStatusArray = array_unique($shipmentStatus);
                }
            }

            return $this->getShipmentStatusLabel($shipmentStatusArray);

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array $shipmentStatusArray
     * @return string
     */
    private function getShipmentStatusLabel(array $shipmentStatusArray): string
    {
        $returnData = '';
        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::DELIVERED))]) {
            return self::COMPLETE;
        }

        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::SHIPMENTCANCELED))]) {
            return self::CANCELED;
        }

        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::CONFIRMED))]) {
            return self::CONFIRMED;
        }

        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::NEW))]) {
            return self::NEW;
        }

        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::READYFORPICKUP))]) {
            return self::READYFORPICKUP;
        }

        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::SHIPPED))]) {
            return self::SHIPPED;
        }

        if (array_unique($shipmentStatusArray) === [strtolower(trim(self::INPROCESS))]) {
            return self::INPROCESS;
        }

        return $returnData;

    }

    /**
     * Get shipment ids from order
     *
     * @param int $orderId
     * @return array
     */
    public function getShipmentIds(int $orderId)
    {
        try {
            $order = $this->orderFactory->create()->load($orderId);
            $shipmentCollection = $order->getShipmentsCollection();
            $shipmentIds = [];
            if (!empty($shipmentCollection)) {
                foreach ($shipmentCollection as $shipment) {
                    $shipmentId[] = $shipment->getId();
                    array_push($shipmentIds, $shipment->getId());
                }
            }
            return $shipmentIds;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get shipment from shipment id
     *
     * @param int $shipmentId
     * @return object
     */
    public function getShipmentById($shipmentId)
    {
        try {
            $shipment = $this->shipmentRepository->get($shipmentId);
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$exception->getMessage());
            $shipment = null;
        }
        return $shipment;
    }

    /**
     * Get shipment id from fxo shipment id and order id
     *
     * @param int $orderId
     * @param int $fxoShipmentId
     * @return int|int[]
     */
    public function getShipmentIdByFxoShipmentId($orderId, $fxoShipmentId)
    {
        try {
            $order = $this->orderFactory->create()->load($orderId);
            $shipmentCollection = $order->getShipmentsCollection();
            $shipmentId = [];
            if (!empty($shipmentCollection)) {
                foreach ($shipmentCollection as $shipment) {
                    if ($shipment->getFxoShipmentId() == $fxoShipmentId) {
                        $shipmentId[] = $shipment->getId();
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.
                ': An Error has occured while retrieving shipment for order :' . $orderId .
                ' is: ' . $exception->getMessage());
            $shipmentId = null;
        }

        return $shipmentId;
    }

    /**
     * Set shipment email data
     *
     * @param int $shipmentId
     * @param date $date
     * @return array
     */
    public function setShipmentEmail($shipmentId, $date)
    {
        try {
            $shipment = $this->getShipmentById($shipmentId);
            $shipment->setReadyForPickupEmailSent("true");
            $shipment->setReadyForPickupEmailSentDate($date);
            $shipment->save();
            return ['message' => 'success'];
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get tracking data from shipment id
     *
     * @param array $shipmentId
     * @return int
     */
    public function getTracking($shipmentId)
    {
        try {
            $shipment = $this->getShipmentById($shipmentId);
            $trackId = null;
            if ($shipment) {
                $shipmentTrack = $shipment->getTracks();
                $i = 0;
                foreach ($shipmentTrack as $shipTrack) {
                    if ($i === 0) {
                        $trackId = $shipTrack->getData('entity_id');
                        break;
                    }
                    $i++;
                }
                return $trackId;
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }


    /**
     * Get tracking number from shipment id
     *
     * @param int $shipmentId
     * @return ?string
     */
    public function getFirstTrackingNumber($shipmentId): ?string
    {
        try {
            $shipment = $this->getShipmentById($shipmentId);
            if ($shipment) {
                $shipmentTrack = $shipment->getTracks();
                foreach ($shipmentTrack as $shipTrack) {
                    return $shipTrack->getTrackNumber();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
        }
        return false;
    }

    /**
     * @param Order $order
     * @param $shipmentId
     * @param bool $isMarketPlaceOrder
     * @return bool
     */
    public function isMultipleShipment($order, $shipmentId, bool $isMarketPlaceOrder): bool
    {
        if (!$isMarketPlaceOrder) {
            return false;
        }

        try {
            $totalOrderedItems = 0;
            $totalShippedItems = 0;
            $order1pIsPickup = $order->getShippingMethod() == self::SHIPPING_TYPE_PICKUP;
            foreach ($order->getItems() as $orderItem) {
                if (!$orderItem->getMiraklOfferId() && $order1pIsPickup) {
                    continue;
                }

                $totalOrderedItems += $orderItem->getQtyOrdered();
                $totalShippedItems += $orderItem->getQtyShipped();
            }

            return $totalOrderedItems != $totalShippedItems;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ':' . $e->getMessage());
        }
        return false;
    }

    /**
     * Get order from order id
     *
     * @param array $orderId
     * @return Order|array
     */
    public function getOrderById($orderId)
    {
        try {
            $orderObj = $this->orderRepository->get($orderId);
            $orderIncrementId = $orderObj->getIncrementId();

            return $this->order->loadByIncrementId($orderIncrementId);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Use to update status of order
     *
     * @param string $status
     * @param string $state
     * @param object $orderdata
     * @param string $comment
     * @return string
     */
    public function updateStatusOfOrder($status, $state, $orderdata, $comment = '')
    {
        try {
            $orderdata->setState($state);
            $orderdata->setStatus($status);

            if (!empty($comment)) {
                $orderdata->addStatusHistoryComment($comment);
            }
            $orderdata->addStatusHistoryComment($status." status updated");
            $orderdata->save();

            return $this->getShipmentCollection($orderdata);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get shipment collection from order id
     *
     * @param object $orderdata
     * @return array
     */
    public function getShipmentCollection($orderdata)
    {
        try {
            $orderStatus = $orderdata->getStatus();
            $shipmentCollection = $orderdata->getShipmentsCollection();
            foreach ($shipmentCollection as $shipment) {
                $shipmentIdValue = $shipment->getId();
                $shipment = $this->getShipmentById($shipmentIdValue);
                $shipment->setOrderStatus($orderStatus);
                $shipment->save();
            }
            return ['message' => 'success'];
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return ['code' => '400', 'message' => $e->getMessage()];
        }
    }

    /**
     * Use to update shipment status and add tracking detail
     *
     * @param object $shipment
     * @param string $trackingNumber
     * @param string $shipmentStatus
     * @param string $courier
     * @param date $pickupAllowedUntilDate
     *
     * @return array
     */
    public function updateShipmentStatus($shipment, $trackingNumber, $shipmentStatus, $courier, $pickupAllowedUntilDate)
    {
        $shipmentId = $shipment->getId();
        $shipmmentTrackId = $this->getTracking($shipmentId);
        $number = $trackingNumber;
        $carrier = $courier;
        if ($carrier == "fedex_office") {
            $title = 'Fedex Office';
        } else {
            $title = 'Federal Express'; // need to check
        }

        $shipStatus = $this->getShipmentStatus($shipmentStatus);
        $this->setTrackData(
            $trackingNumber,
            $courier,
            $number,
            $carrier,
            $title,
            $shipStatus,
            $shipment,
            $pickupAllowedUntilDate,
            $shipmmentTrackId
        );
    }

    /**
     * Set Track Data
     *
     * @param int $trackingNumber
     * @param string $courier
     * @param int $number
     * @param string $title
     * @param string $shipStatus
     * @param object $shipment
     * @param date $pickupAllowedUntilDate
     * @param int $shipmmentTrackId
     *
     */
    public function setTrackData(
        $trackingNumber,
        $courier,
        $number,
        $carrier,
        $title,
        $shipStatus,
        $shipment,
        $pickupAllowedUntilDate,
        $shipmmentTrackId
    )
    {
        if ($shipmmentTrackId == null) {
            try {
                if ($trackingNumber!="" && $courier!="") {
                    $track = $this->trackFactory->create();
                    $track->setNumber($number);
                    $track->setCarrierCode($carrier);
                    $track->setTitle($title);
                    $shipment->addTrack($track);
                }
                if ($shipStatus) {
                    $shipment->setShipmentStatus($shipStatus);
                }
                $shipment->setPickupAllowedUntilDate($pickupAllowedUntilDate);
                $this->shipmentRepository->save($shipment);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
                return ['code' => '400', 'message' => $e->getMessage()];
            }
        } else {
            try {
                if ($trackingNumber!="" && $courier!="") {
                    $trackData = $this->trackFactory->create()->load($shipmmentTrackId);
                    $trackData->setNumber($number);
                    $trackData->setCarrierCode($carrier);
                    $trackData->setTitle($title);
                    $shipment->addTrack($trackData);
                }
                $shipment->setShipmentStatus($shipStatus);
                $shipment->setPickupAllowedUntilDate($pickupAllowedUntilDate);
                $this->shipmentRepository->save($shipment);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
                return ['code' => '400', 'message' => $e->getMessage()];
            }
        }
    }

    /**
     * getQuotebyGTN
     * @param  string $gtn
     * @return object
     */
    public function getQuotebyGTN($gtn)
    {
        return  $this->quoteFactory->create()->getCollection()->addFieldToFilter('gtn', $gtn)->getFirstItem();
    }

    /**
     * createOrderbyGTN
     *
     * @param  string $gtn
     * @param  array $requestData
     * @return int
     */
    public function createOrderbyGTN($gtn, $requestData)
    {
        $orderId = 0;
        try {
            $quote = $this->getQuotebyGTN($gtn);
            if (isset($requestData['transactionId']) && $requestData['transactionId'] != '') {
                $transactionId = $requestData['transactionId'];
            } else {
                $transactionId = $this->submitOrderApi->getRetailTransactionIdByGtnNumber($gtn);
            }

            if ($transactionId != '' && isset($transactionId) && $quote->getId() !== null) {
                $order = $this->quoteManagement->submit($quote);
                if ($order) {
                    $order->setStatus("new");
                    $order->save();
                    $orderId = $order->getIncrementId();
                }
            } else {
                $orderId = $this->createMissedOrders($quote, $transactionId, $gtn);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return false;
        }
       return $orderId;
    }

    /**
     * Get Prodyct SKU
     *
     * @param  int $productId
     * @return boolean
     */
    public function getProductSku($productId)
    {
        $productHierarchyUrl = $this->scopeConfig
        ->getValue('fedex/general/product_hierarchy_json_url', ScopeInterface::SCOPE_STORE);

        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)
        Chrome/113.0.0.0 Safari/537.36';

        $this->curl->setOption(CURLOPT_USERAGENT, $userAgent);
        $this->curl->get($productHierarchyUrl);
        $productResponse = json_decode($this->curl->getBody());

        foreach ($productResponse->productMenuDetails as $product) {
            if ($product->productId == $productId) {
                return $product->id;
            }
        }

        return false;
    }

    /**
     * Add Products to Quote
     *
     * @param array $productDetails
     * @param object $quote
     * @return array
     */
    public function addProductsToQuote($productDetails, $quote)
    {
        $productsAdded = [];
        if (!empty($productDetails)) {
            $count = 0;
            foreach ($productDetails as $product) {
                $sku = $this->getProductSku($product['productId']);
                if ($sku) {
                    $qty = $product['unitQuantity'];
                    $searchCriteria = $this->searchCriteriaBuilder
                                            ->addFilter('sku', $sku . '%', 'like')
                                            ->addFilter('status', 1)
                                            ->create();
                    $searchResults = $this->productRepository->getList($searchCriteria);
                    $firstProduct = null;

                    $itemCount = 0;
                    foreach ($searchResults->getItems() as $searchItem) {
                        if ($itemCount==0) {
                            $firstProduct = $searchItem;
                            break;
                        }
                        $itemCount++;
                    }

                    $firstProductSku = $firstProduct == null ? '1534436209752-4-3' : $firstProduct->getSku();

                    $productObject = $this->productRepository->get($firstProductSku);
                    $externalData['qty'] = $qty;
                    $externalData['userProductName'] = $product['userProductName'];
                    $infoBuyRequest = ['external_prod' => [0 => $externalData]];

                    $price = $product['productRetailPrice']/$qty;
                    $rowTotal = $product['productRetailPrice'];
                    $discount = $product['productDiscountAmount'];

                    $productsAdded[$count]['qty'] = $qty;
                    $productsAdded[$count]['price'] = $price;
                    $productsAdded[$count]['row_total'] = $rowTotal;
                    $productsAdded[$count]['discount'] = $discount;

                    $item = $this->itemFactory->create();
                    $item->setBasePrice($price);
                    $item->setPrice($price);
                    $item->setBasePriceInclTax($price);
                    $item->setPriceInclTax($price);
                    $item->setDiscount($discount);
                    $item->setRowTotal($rowTotal);
                    $item->setBaseRowTotal($rowTotal);
                    $item->setQty($qty);
                    $item->addOption([
                        'product_id' => $productObject->getId(),
                        'code' => 'info_buyRequest',
                        'value' => $this->serializer->serialize($infoBuyRequest),
                    ]);
                    $item->setProduct($productObject);
                    $quote->addItem($item);

                }
                $count++;
            }
        }
        return $productsAdded;
    }

    /**
     * Create Missed Orders
     *
     * @param object $quote
     * @param string $transactionId
     * @param string $gtn
     * @return string
     */
    public function createMissedOrders($quote, $transactionId, $gtn)
    {
        $transactionResponse = $this->rateQuoteTransactionApiHalder->getTransactionResponse($quote, $transactionId);

        if (isset($transactionResponse['error']) && !$transactionResponse['error']) {

            $store = $this->storeManager->getStore();

            $deliveryDetails = $transactionResponse['response']['output']['checkout']['lineItems'][0]
            ['retailPrintOrderDetails'][0]['deliveryLines'] ?? [];

            $customerEmailId = $deliveryDetails[0]['recipientContact']['emailDetail']['emailAddress'] ?? '';

            $quote = $this->quoteFactory->create();
            $quote->setStore($store);
            $quote->setReservedOrderId($gtn);
            $quote->setGtn($gtn);
            $quote->setData('customer_email',$customerEmailId);
            $quote->setCustomerIsGuest(true);

            $productDetails = $transactionResponse['response']['output']['checkout']['lineItems'][0]
            ['retailPrintOrderDetails'][0]['productLines'] ?? [];

            $productsAdded = $this->addProductsToQuote($productDetails, $quote);

            $firstname = $deliveryDetails[0]['recipientContact']['personName']['firstName'] ?? '';
            $lastname = $deliveryDetails[0]['recipientContact']['personName']['lastName'] ?? '';

            $quote->setData('customer_firstname',$firstname);
            $quote->setData('customer_lastname',$lastname);

            if (isset($deliveryDetails[0]['deliveryLineType']) &&
            $deliveryDetails[0]['deliveryLineType'] == 'SHIPPING') {

            $shippingAddress = [
                                'firstname' => $firstname,
                                'lastname' => $lastname,
                                'street' => $deliveryDetails[0]['shipmentDetails']['address']['streetLines'][0] ?? '',
                                'city' => $deliveryDetails[0]['shipmentDetails']['address']['city'] ?? '',
                                'country_id' => $deliveryDetails[0]['shipmentDetails']['address']['countryCode'] ?? '',
                                'region' => $deliveryDetails[0]['shipmentDetails']['address']
                                ['stateOrProvinceCode'] ?? '',
                                'postcode' => $deliveryDetails[0]['shipmentDetails']['address']['postalCode'] ?? '',
                                'telephone' => $deliveryDetails[0]['recipientContact']['phoneNumberDetails'][0]
                                ['phoneNumber']['number'] ?? '',
                                'company' => null,
                                'save_in_address_book'=> 1
                               ];

                $fxoShipmentId = $deliveryDetails[0]['deliveryLineId'] ?? null;
                $quote->setData('fxo_shipment_id', $fxoShipmentId);

                //Set Address to quote
                $quote->getBillingAddress()->addData($shippingAddress);
                $quote->getShippingAddress()->addData($shippingAddress);

                $shippingMethod = 'fedexshipping_'.$deliveryDetails[0]['shipmentDetails']['serviceType'];
                $shippingAmount = $deliveryDetails[0]['deliveryRetailPrice'];
                $quote->setShippingCost($shippingAmount);
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setPrice($shippingAmount);
                $shippingAddress->setShippingAmount($shippingAmount)->setBaseShippingAmount($shippingAmount);

            } else {

                $pickupAddress = [
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'street' => $deliveryDetails[0]['pickupDetails']['address']['streetLines'][0] ?? '',
                            'city' => $deliveryDetails[0]['pickupDetails']['address']['city'] ?? '',
                            'country_id' => $deliveryDetails[0]['pickupDetails']
                                ['address']['countryCode'] ?? '',
                            'region' => $deliveryDetails[0]
                                ['pickupDetails']['address']['stateOrProvinceCode'] ?? '',
                            'postcode' => $deliveryDetails[0]['pickupDetails']['address']['postalCode'] ?? '',
                            'telephone' => $deliveryDetails[0]['recipientContact']['phoneNumberDetails'][0]
                                ['phoneNumber']['number'] ?? '',
                            'company' => null,
                            'save_in_address_book'=> 1
                            ];

                $fxoShipmentId = $deliveryDetails[0]['deliveryLineId'] ?? null;
                $quote->setData('fxo_shipment_id', $fxoShipmentId);

                $quote->getBillingAddress()->addData($pickupAddress);
                $quote->getShippingAddress()->addData($pickupAddress);
                $shippingMethod = 'fedexshipping_PICKUP';
            }

            $shippingAddress = $quote->getShippingAddress();
            $deliveryTime = $deliveryDetails[0]['estimatedDeliveryLocalTime'] ?? 0;
            $shippingDescription = date('D, F d, g:iA',strtotime($deliveryTime));

            $shippingAddress->setShippingDescription($shippingDescription);

            $billingAddress = $quote->getBillingAddress();
            $billingAddress->setShippingMethod($shippingMethod);

            $billingAddress->setShippingDescription($shippingDescription);

            $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod($shippingMethod);

            $paymentTenders = $transactionResponse['response']['output']['checkout']['tenders'] ?? [];
            $cardNumber = '';
            $accountNumber = '';
            $shippingAccountNumber = null;
            if (!empty($paymentTenders)){
                foreach ($paymentTenders as $payment) {
                    $paymentMethod = $payment['paymentType'];
                    if ($paymentMethod == 'CREDIT_CARD') {
                        $cardNumber = $payment['creditCard']['accountLast4Digits'];
                    } else {
                        $accountNumber = $payment['account']['accountNumber'];
                        $accountType = $payment['account']['accountType'] ?? '';
                        if ($accountType == 'FX') {
                            $shippingAccountNumber = $payment['account']['accountNumber'];
                        }
                    }
                }
            }
            $quote->setData('fedex_ship_account_number', $shippingAccountNumber);
            $quote->setInventoryProcessed(false);
            $quote->save();

            $quote = $this->saveQuoteData($quote, $cardNumber, $paymentMethod, $productsAdded);

            // Create Order From Quote
            $order = $this->quoteManagement->submit($quote);

            return $this->saveOrderData(
                $order,
                $accountNumber,
                $cardNumber,
                $paymentMethod,
                $productsAdded,
                $transactionResponse
            );

        }
    }

    /**
    * Save Quote Data
    *
    * @param  object $quote
    * @param  string $cardNumber
    * @param  string $paymentMethod
    * @param  array $productsAdded
    * @return object
    */
    public function saveQuoteData($quote, $cardNumber, $paymentMethod, $productsAdded)
    {
        if ($paymentMethod == 'CREDIT_CARD') {
            $quote->setPaymentMethod('fedexccpay');
            $cardNumber = str_replace('x','',$cardNumber);
            $quote->getPayment()->importData(['method' => 'fedexccpay','cc_last_4' => $cardNumber]);
        } else {
            $quote->getPayment()->importData(['method' => 'fedexaccount']);
            $quote->setPaymentMethod('fedexaccount');
        }

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        return $quote;
    }

    /**
    * saveOrderData
    *
    * @param  object $order
    * @param  string $accountNumber
    * @param  string $cardNumber
    * @param  string $paymentMethod
    * @param  array $productsAdded
    * @param  array $transactionResponse
    * @return string
    */
    public function saveOrderData(
        $order,
        $accountNumber,
        $cardNumber,
        $paymentMethod,
        $productsAdded,
        $transactionResponse
    )
    {
        if ($paymentMethod == 'CREDIT_CARD') {
            $cardNumber = str_replace('x','',$cardNumber);
            $order->getPayment()->setCcLast4($cardNumber);
        } else {
            $order->getPayment()->setFedexAccountNumber($accountNumber);
        }

        $count = 0;
        foreach ($order->getAllItems() as $item) {
            $item->setBasePrice($productsAdded[$count]['price']);
            $item->setPrice($productsAdded[$count]['price']);
            $item->setBaseOriginalPrice($productsAdded[$count]['price']);
            $item->setOriginalPrice($productsAdded[$count]['price']);
            $item->setBasePriceInclTax($productsAdded[$count]['price']);
            $item->setPriceInclTax($productsAdded[$count]['price']);
            $item->setDiscount($productsAdded[$count]['discount']);
            $item->setRowTotal($productsAdded[$count]['row_total']);
            $item->setBaseRowTotal($productsAdded[$count]['row_total']);
            $count++;
        }

        $transactionTotals = $transactionResponse['response']['output']['checkout']['transactionTotals'] ?? [];

        if (!empty($transactionTotals)){
            $order->setBaseSubtotal($transactionTotals['grossAmount']);
            $order->setSubtotal($transactionTotals['grossAmount']);
            $order->setBaseDiscountAmount($transactionTotals['totalDiscountAmount']);
            $order->setDiscountAmount($transactionTotals['totalDiscountAmount']);
            $order->setTaxAmount($transactionTotals['taxAmount']);
            $order->setBaseTaxAmount($transactionTotals['taxAmount']);
            $order->setGrandTotal($transactionTotals['totalAmount']);
            $order->setBaseGrandTotal($transactionTotals['totalAmount']);
        }

        $order->addStatusHistoryComment('Order Created by system through order rehydration logic ');
        $order->save();

        return $order->getIncrementId();
    }

    /**
     * Get Shipment by order id.
     *
     * @param OrderRepositoryInterface $order
     * @param string $itemId
     * @return void
     */
    public function getShippingByOrderAndItemId($order, $itemId)
    {
        foreach ($order->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getAllItems() as $shipmentItem) {
                if ($shipmentItem->getOrderItemId() === $itemId) {
                    return $shipment;
                }
            }
        }
    }

    /**
     * Get delivery date from mkt item.
     *
     * @param \Magento\Sales\Model\Order\ItemRepository $item
     * @return array
     */
    public function getDeliveryDateFromMktItem($item)
    {
        $additionalData = $item->getAdditionalData();
        $jsonData = json_decode($additionalData, true);

        if(isset($jsonData['mirakl_shipping_data'])) {
            $deliveryDate = $jsonData['mirakl_shipping_data']['deliveryDate'];
            $shippingDescription = explode(" ", $deliveryDate);
            $estimatedDelivery = (isset($shippingDescription[0])) ? trim($shippingDescription[0], ', ') . "-" : '';
            $estimatedDelivery .= (isset($shippingDescription[1])) ? $shippingDescription[1] . " " : '';
            $estimatedDelivery .= (isset($shippingDescription[2])) ? $shippingDescription[2] . " " : '';
            $estimatedDelivery .= date('Y') . "-";
            $estimatedDelivery .= (isset($shippingDescription[3])) ? $shippingDescription[3] . " " : '';
            $shippingDescription = $estimatedDelivery;

            return explode('-', $shippingDescription);
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isMixedOrder(Order $order): bool
    {
        return $this->miraklOrderHelper->isMiraklOrder($order) && !$this->miraklOrderHelper->isFullMiraklOrder($order);
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function isAllItemsCancelled(Order $order): bool
    {
        // Load order to get latest object
        $order = $this->orderRepository->get($order->getId());
        $status = true;
        $orderItems = $order->getAllItems();

        foreach ($orderItems as $orderItem) {
            if ($orderItem->getQtyOrdered() != $orderItem->getQtyRefunded()) {
                $status = false;
                break;
            }
        }

        return $status;
    }

    /**
     * @param Order $orderId
     * @param ShipmentInterface $currentShipment
     * @return bool
     */
    public function canMixedOrderCancelEmailsBeSent(Order $order, ShipmentInterface $currentShipment): bool
    {
        $shipmentCollection = $order->getShipmentsCollection();
        $is1PShipment = $is3PShipment = $is1PEmailSent = $is3PEmailSent = false;
        $shipmentStatus1P = $shipmentStatus3P = '';
        if (!empty($shipmentCollection)) {
            foreach ($shipmentCollection as $shipment) {
                if (($shipment->getMiraklShippingReference() && !$is3PShipment) || ($currentShipment->getMiraklShippingReference() && !$is3PShipment)) {
                    $is3PShipment = true;
                    $is3PEmailSent = (bool)$shipment->getIsCancellationEmailSent();
                    $shipmentStatusId = $shipment->getShipmentStatus();
                    $shipmentStatus3P = $currentShipment->getMiraklShippingReference() ? NewOrderUpdateInterface::CANCELLED : $this->getShipmentStatusByValue($shipmentStatusId);
                }
                if ((!$shipment->getMiraklShippingReference() && !$is1PShipment) || (!$currentShipment->getMiraklShippingReference() && !$is1PShipment)) {
                    $is1PShipment = true;
                    $is1PEmailSent = (bool)$shipment->getIsCancellationEmailSent();
                    $shipmentStatusId = $shipment->getShipmentStatus();
                    $shipmentStatus1P = !$currentShipment->getMiraklShippingReference() ? NewOrderUpdateInterface::CANCELLED : $this->getShipmentStatusByValue($shipmentStatusId);
                }
            }

            // If both shipments exist AND both shipments are cancelled AND no emails are not yet sent
            if ($is3PShipment && $is1PShipment && !$is3PEmailSent && !$is1PEmailSent
                && $shipmentStatus1P === NewOrderUpdateInterface::CANCELLED
                && $shipmentStatus3P === NewOrderUpdateInterface::CANCELLED) {
                return true;
            }
        }
        return false;
    }
}
