<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentSearchResultInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\Data\ShipmentTrackSearchResultInterface;
use Magento\Sales\Model\Order\Shipment\Item as ShipmentItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement;
use Fedex\MarketplaceWebhook\Model\Middleware\AuthorizationMiddleware;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Fedex\Shipment\Model\SendOrderEmailPublisher;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Convert\Order as OrderConverter;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\Shipment\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Psr\Log\LoggerInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Fedex\MarketplaceWebhook\Model\CreateInvoicePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class OrderWebhookEnhancementTest extends TestCase
{
    protected $orderFactory;
    protected $order;
    protected $orderConverter;
    protected $helper;
    protected $publisher;
    protected $trackRepository;
    protected $shipmentTrackSearchResultInterface;
    protected $shipmentTrackInterface;
    protected $trackModel;
    protected $marketplaceHelper;
    protected $timezone;
    protected $handleMktCheckout;
    /**
     * @var OrderWebhookEnhancement
     */
    private $orderWebhookEnhancement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var MiraklOrderHelper
     */
    private $miraklOrderHelper;

    /**
     * @var CreateInvoicePublisher
     */
    private $createInvoicePublisher;

    private ShipmentRepositoryInterface|MockObject $shipmentRepository;

    private ShipmentSearchResultInterface|MockObject $shipmentSearchResultInterface;

    private ShipmentInterface|MockObject $shipmentInterface;

    private Shipment|MockObject $shipment;

    private ShipmentItem|MockObject $shipmentItem;

    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilder;

    private SearchCriteria|MockObject $searchCriteria;

    private SendOrderEmailPublisher|MockObject $sendOrderEmailPublisher;

    private LoggerInterface|MockObject $logger;

    private TrackFactory|MockObject $trackFactory;

    private \DateTime|MockObject $dateTime;

    protected function setUp(): void
    {
        $authorizationMiddleware = $this->createMock(AuthorizationMiddleware::class);
        $request = $this->createMock(HttpRequest::class);
        $this->orderFactory = $this->createMock(OrderFactory::class);
        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayment', 'getIncrementId', 'hasInvoices', 'getId', 'getAllItems', 'getAllVisibleItems', 'loadByIncrementId'])
            ->addMethods(['setIsInProcess'])
            ->getMock();
        $this->orderConverter = $this->createMock(OrderConverter::class);
        $this->shipmentRepository = $this->getMockBuilder(ShipmentRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMockForAbstractClass();
        $this->shipmentSearchResultInterface = $this->getMockBuilder(ShipmentSearchResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFirstItem'])
            ->getMockForAbstractClass();
        $this->shipmentInterface = $this->getMockBuilder(ShipmentInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setShipmentStatus', 'getItems', 'setTotalQty'])
            ->addMethods(['setPickupAllowedUntilDate', 'setMiraklShippingId', 'setMiraklShippingReference', 'setFxoShipmentId', 'setShippingDueDate', 'itemToShipmentItem', 'getData', 'addTrack', 'getId', 'getItemsCollection', 'register', 'getOrder', 'addItem'])
            ->getMockForAbstractClass();
        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setShipmentStatus', 'register', 'getOrder', 'getItems', 'addItem', 'getId', 'addTrack', 'getItemsCollection', 'setTotalQty'])
            ->addMethods(['setPickupAllowedUntilDate', 'setMiraklShippingId', 'setMiraklShippingReference', 'setFxoShipmentId', 'setShippingDueDate', 'itemToShipmentItem'])
            ->getMock();
        $this->shipmentItem = $this->getMockBuilder(ShipmentItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrderItemId', 'getOrderItem', 'setQty'])
            ->getMock();
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->helper = $this->createMock(Data::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria = $this->createMock(SearchCriteria::class);
        $this->sendOrderEmailPublisher = $this->createMock(SendOrderEmailPublisher::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $submitOrderHelper = $this->createMock(SubmitOrderHelper::class);
        $this->miraklOrderHelper = $this->createMock(MiraklOrderHelper::class);
        $this->createInvoicePublisher = $this->createMock(CreateInvoicePublisher::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->trackRepository = $this->createMock(TrackRepository::class);
        $this->shipmentTrackSearchResultInterface = $this->getMockBuilder(ShipmentTrackSearchResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getFirstItem'])
            ->getMockForAbstractClass();
        $this->shipmentTrackInterface = $this->getMockBuilder(ShipmentTrackInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getData', 'setNumber'])
            ->getMockForAbstractClass();
        $this->trackFactory = $this->createMock(TrackFactory::class);
        $this->trackModel = $this->getMockBuilder(Track::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setCarrierCode', 'setNumber', 'setTitle', 'getData'])
            ->getMockForAbstractClass();
        $this->marketplaceHelper = $this->createMock(MarketPlaceHelper::class);
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->handleMktCheckout = $this->createMock(HandleMktCheckout::class);
        $this->dateTime = $this->createMock(\DateTime::class);

        $this->orderWebhookEnhancement = new OrderWebhookEnhancement(
            $authorizationMiddleware,
            $request,
            $this->orderFactory,
            $this->orderConverter,
            $this->shipmentRepository,
            $this->orderRepository,
            $this->helper,
            $this->searchCriteriaBuilder,
            $this->sendOrderEmailPublisher,
            $this->logger,
            $submitOrderHelper,
            $this->miraklOrderHelper,
            $this->createInvoicePublisher,
            $this->publisher,
            $this->trackRepository,
            $this->trackFactory,
            $this->marketplaceHelper,
            $this->timezone,
            $this->handleMktCheckout
        );
    }

    public function testExecuteThrowException()
    {
        $incrementId = '12345';
        $incrementIdSeller = '12345-A';
        $itemId = 1000;
        $itemIdQty = 100;
        $trackingCode = 'TRACKING_CODE';
        $carrierCode = 'fedexshipping';
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'SHIPMENTS',
                                'to' => [
                                    'status' => 'SHIPPED',
                                    'tracking' => [
                                        'carrier_code' => $carrierCode,
                                        'tracking_number' => $trackingCode
                                    ],
                                    'shipment_lines' => [
                                        ['order_line_id' => $itemId, 'quantity' => $itemIdQty],
                                    ],
                                ],
                                'from' => [
                                    'status' => 'SHIPPING',
                                    'tracking' => [
                                        'carrier_code' => null,
                                        'tracking_number' => null
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'id' => $incrementIdSeller
                ]
            ]
        ]);

        $this->orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($incrementId)
            ->willThrowException(new \Exception('Exception for Unit Test.'));

        $this->logger->expects($this->any())
            ->method('critical')
            ->withConsecutive(
                ['Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:278 ' . $payload],
                ['Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:279 Exception for Unit Test.']
            )
            ->willReturnSelf();

        $return = $this->orderWebhookEnhancement->execute($payload);
    }

    public function testExecuteHasShipmentNewShipmentUpdateModifyApiToggleOn()
    {
        $orderId = 5;
        $shipmentId = null;
        $incrementId = '12345';
        $incrementIdSeller = '12345-A';
        $additionalData = '{"mirakl_shipping_data":{"reference_id":"123456"}}';
        $itemId = 1000;
        $itemIdQty = 100;
        $trackingCode = 'TRACKING_CODE';
        $carrierCode = 'fedex_office';
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'SHIPMENTS',
                                'to' => [
                                    'status' => 'SHIPPED',
                                    'tracking' => [
                                        'carrier_code' => $carrierCode,
                                        'tracking_number' => $trackingCode,
                                        'carrier_name' => $carrierCode
                                    ],
                                    'shipment_lines' => [
                                        ['order_line_id' => $itemId, 'quantity' => $itemIdQty],
                                    ],
                                ],
                                'from' => [
                                    'status' => 'SHIPPING',
                                    'tracking' => [
                                        'carrier_code' => null,
                                        'tracking_number' => null
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'id' => $incrementIdSeller
                ]
            ]
        ]);

        $shipmentId = 123;

        $this->shipment->expects($this->any())
            ->method('getId')
            ->willReturn($shipmentId);

        $this->orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($incrementId)
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $this->miraklOrderHelper->expects($this->any())
            ->method('isFullMiraklOrder')
            ->willReturn(true);

        $this->searchCriteriaBuilder->expects($this->atMost(3))
            ->method('addFilter')
            ->withConsecutive(
                ['mirakl_shipping_reference', $incrementIdSeller],
                ['parent_id', $shipmentId],
                ['track_number', null]
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atMost(2))
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->shipmentRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentSearchResultInterface);

        $this->shipmentSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->shipmentInterface);

        $this->shipmentInterface->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->orderConverter->expects($this->once())
            ->method('toShipment')
            ->with($this->order)
            ->willReturn($this->shipment);

        $this->shipmentInterface->expects($this->atMost(3))
            ->method('getId')
            ->willReturn($shipmentId);

        $this->trackRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentTrackSearchResultInterface);

        $this->shipmentTrackSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->trackFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('setNumber')
            ->with($trackingCode)
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setCarrierCode')
            ->with($carrierCode)
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setTitle')
            ->with('Fedex Office')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('addTrack')
            ->with($this->trackModel)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->atMost(4))
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $this->orderRepository->expects($this->any())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getItemId', 'getQtyOrdered', 'getQtyShipped', 'getId', 'getQtyToShip', 'getIsVirtual', 'getMiraklShopId', 'getAdditionalData', 'setQtyShipped', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getQtyOrdered')->willReturn($itemIdQty);
        $item->method('setQtyShipped')->willReturn($itemIdQty);
        $item->method('getQtyShipped')->willReturnOnConsecutiveCalls(0, 0, 0, $itemIdQty, $itemIdQty);
        $item->method('getQtyToShip')->willReturn($itemIdQty);
        $item->method('getIsVirtual')->willReturn(false);
        $item->method('getId')->willReturn($itemId);
        $item->method('getMiraklShopId')->willReturn(12);
        $item->method('getAdditionalData')->willReturn($additionalData);
        $item->method('getItemId')->willReturn($itemId);
        $item->method('save')->willReturnSelf();

        $this->order->expects($this->atMost(4))
            ->method('getAllItems')
            ->willReturn([$item]);

        $this->order->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$item]);

        $this->orderConverter->expects($this->once())
            ->method('itemToShipmentItem')
            ->with($item)
            ->willReturn($this->shipmentItem);

        $this->shipment->expects($this->once())
            ->method('addItem')
            ->with($this->shipmentItem)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('register')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('setIsInProcess')
            ->with(true)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingId')
            ->with(null)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingReference')
            ->with($incrementIdSeller)
            ->willReturnSelf();

        $this->shipment->expects($this->atMost(3))
            ->method('setShipmentStatus')
            ->withConsecutive(['9'], ['shipped'])
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setTotalQty')
            ->with($itemIdQty)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->shipmentItem]);

        $this->shipment->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn([$this->shipmentItem]);

        $this->shipmentItem->expects($this->atMost(2))
            ->method('getOrderItemId')
            ->willReturn($itemId);

        $this->shipmentItem->expects($this->once())
            ->method('getOrderItem')
            ->willReturn($item);

        $this->shipmentItem->expects($this->once())
            ->method('setQty')
            ->with($itemIdQty)
            ->willReturnSelf();

        $this->helper->expects($this->atMost(2))
            ->method('getShipmentStatus')
            ->with('shipped')
            ->willReturn('shipped');

        $this->sendOrderEmailPublisher->expects($this->once())
            ->method('execute')
            ->with('shipped', $orderId, $shipmentId)
            ->willReturnSelf();

        $this->helper->expects($this->once())
            ->method('updateStatusOfOrder')
            ->with('complete', 'complete', $this->order)
            ->willReturnSelf();

        $this->orderWebhookEnhancement->execute($payload);
    }

    public function testExecuteHasShipmentNewShipmentUpdateModifyApiToggleOff()
    {
        $orderId = 5;
        $shipmentId = null;
        $incrementId = '12345';
        $incrementIdSeller = '12345-A';
        $additionalData = '{"mirakl_shipping_data":{"reference_id":"123456"}}';
        $itemId = 1000;
        $itemIdQty = 100;
        $trackingCode = 'TRACKING_CODE';
        $carrierCode = 'fedex_office';
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'SHIPMENTS',
                                'to' => [
                                    'status' => 'SHIPPED',
                                    'tracking' => [
                                        'carrier_code' => $carrierCode,
                                        'tracking_number' => $trackingCode,
                                        'carrier_name' => $carrierCode
                                    ],
                                    'shipment_lines' => [
                                        ['order_line_id' => $itemId, 'quantity' => $itemIdQty],
                                    ],
                                ],
                                'from' => [
                                    'status' => 'SHIPPING',
                                    'tracking' => [
                                        'carrier_code' => null,
                                        'tracking_number' => null
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'id' => $incrementIdSeller
                ]
            ]
        ]);

        $this->orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($incrementId)
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $this->miraklOrderHelper->expects($this->any())
            ->method('isFullMiraklOrder')
            ->willReturn(true);

        $this->searchCriteriaBuilder->expects($this->atMost(3))
            ->method('addFilter')
            ->withConsecutive(
                ['mirakl_shipping_reference', $incrementIdSeller],
                ['parent_id', $shipmentId],
                ['track_number', null]
            )
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->atMost(2))
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->shipmentRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentSearchResultInterface);

        $this->shipmentSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->shipmentInterface);

        $this->shipmentInterface->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->orderConverter->expects($this->once())
            ->method('toShipment')
            ->with($this->order)
            ->willReturn($this->shipment);

        $this->shipmentInterface->expects($this->atMost(3))
            ->method('getId')
            ->willReturn($shipmentId);

        $this->trackRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentTrackSearchResultInterface);

        $this->shipmentTrackSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->trackFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('setNumber')
            ->with($trackingCode)
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setCarrierCode')
            ->with($carrierCode)
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setTitle')
            ->with('Fedex Office')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('addTrack')
            ->with($this->trackModel)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->atMost(4))
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $this->orderRepository->expects($this->any())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getItemId', 'getQtyOrdered', 'getQtyShipped', 'getId', 'getQtyToShip', 'getIsVirtual', 'getMiraklShopId', 'getAdditionalData', 'setQtyShipped', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getQtyOrdered')->willReturn($itemIdQty);
        $item->method('setQtyShipped')->willReturn($itemIdQty);
        $item->method('getQtyShipped')->willReturnOnConsecutiveCalls(0, 0, 0, $itemIdQty, $itemIdQty);
        $item->method('getQtyToShip')->willReturn($itemIdQty);
        $item->method('getIsVirtual')->willReturn(false);
        $item->method('getId')->willReturn($itemId);
        $item->method('getMiraklShopId')->willReturn(12);
        $item->method('getAdditionalData')->willReturn($additionalData);
        $item->method('getItemId')->willReturn($itemId);
        $item->method('save')->willReturnSelf();

        $this->order->expects($this->atMost(4))
            ->method('getAllItems')
            ->willReturn([$item]);

        $this->order->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$item]);

        $this->orderConverter->expects($this->once())
            ->method('itemToShipmentItem')
            ->with($item)
            ->willReturn($this->shipmentItem);

        $this->shipment->expects($this->once())
            ->method('addItem')
            ->with($this->shipmentItem)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('register')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('setIsInProcess')
            ->with(true)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingId')
            ->with(null)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingReference')
            ->with($incrementIdSeller)
            ->willReturnSelf();

        $this->shipment->expects($this->atMost(3))
            ->method('setShipmentStatus')
            ->withConsecutive(['9'], ['shipping'])
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setTotalQty')
            ->with($itemIdQty)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->shipmentItem]);

        $this->shipmentItem->expects($this->atMost(2))
            ->method('getOrderItemId')
            ->willReturn($itemId);

        $this->shipmentItem->expects($this->once())
            ->method('getOrderItem')
            ->willReturn($item);

        $this->shipmentItem->expects($this->once())
            ->method('setQty')
            ->with($itemIdQty)
            ->willReturnSelf();

        $this->helper->expects($this->atMost(2))
            ->method('getShipmentStatus')
            ->with('shipping')
            ->willReturn('shipping');

        $this->helper->method('updateStatusOfOrder')
            ->with('complete', 'complete', $this->order)
            ->willReturnSelf();

        $this->orderWebhookEnhancement->execute($payload);
    }

    public function testExecuteHasAcceptanceStatusModifyApiToggleOn()
    {
        $orderId = 5;
        $incrementId = '12345';
        $incrementIdSeller = '12345-A';
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'STATE',
                                'to' => 'SHIPPING',
                                'order_line_id' => 1000
                            ]
                        ]
                    ],
                    'id' => '12345-A'
                ]
            ]
        ]);

        $this->orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($incrementId)
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $this->order->expects($this->once())
            ->method('hasInvoices')
            ->willReturn(false);

        $this->miraklOrderHelper->expects($this->any())
            ->method('isFullMiraklOrder')
            ->willReturn(true);

        $this->createInvoicePublisher->expects($this->any())
            ->method('execute')
            ->with($orderId)
            ->willReturnSelf();

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyOrdered', 'getQtyShipped', 'getId', 'getQtyToShip', 'getIsVirtual', 'getMiraklShopId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getQtyOrdered')->willReturn(25);
        $item->method('getQtyShipped')->willReturn(0);
        $item->method('getQtyToShip')->willReturn(25);
        $item->method('getIsVirtual')->willReturn(false);
        $item->method('getId')->willReturn(1000);
        $item->method('getMiraklShopId')->willReturn(12);

        $this->order->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$item]);

        $this->order->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$item]);
        $this->helper->expects($this->once())
            ->method('updateStatusOfOrder')
            ->with('confirmed', 'in_process', $this->order)
            ->willReturnSelf();

        $this->orderRepository->expects($this->any())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $this->orderConverter->expects($this->once())
            ->method('toShipment')
            ->with($this->order)
            ->willReturn($this->shipment);

        $this->orderConverter->expects($this->once())
            ->method('itemToShipmentItem')
            ->with($item)
            ->willReturn($this->shipmentItem);

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('mirakl_shipping_reference', $incrementIdSeller)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->shipmentRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentSearchResultInterface);

        $this->shipmentSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->shipmentInterface);

        $this->shipment->expects($this->once())
            ->method('addItem')
            ->with($this->shipmentItem)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('register')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('setIsInProcess')
            ->with(true)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingId')
            ->with(null)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingReference')
            ->with($incrementIdSeller)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setShipmentStatus')
            ->with('9')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setTotalQty')
            ->with(25)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $this->sendOrderEmailPublisher->expects($this->any())
            ->method('execute')
            ->with('confirmed', $orderId)
            ->willReturnSelf();

        $this->logger->expects($this->any())
            ->method('info')
            ->with('Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:194 Order accepted by seller: ' . $incrementId)
            ->willReturnSelf();

        $this->orderWebhookEnhancement->execute($payload);
    }

    public function testExecuteHasNoAcceptedItems()
    {
        $orderId = 5;
        $incrementId = '12345';
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'STATE',
                                'to' => 'SHIPPING',
                                'order_line_id' => 1000
                            ]
                        ]
                    ],
                    'id' => '12345-A'
                ]
            ]
        ]);

        $this->orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($incrementId)
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $this->order->expects($this->once())
            ->method('hasInvoices')
            ->willReturn(false);

        $this->miraklOrderHelper->expects($this->any())
            ->method('isFullMiraklOrder')
            ->willReturn(true);

        $this->createInvoicePublisher->expects($this->any())
            ->method('execute')
            ->with($orderId)
            ->willReturnSelf();

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyOrdered', 'getQtyShipped', 'getId', 'getQtyToShip', 'getIsVirtual'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getQtyOrdered')->willReturn(25);
        $item->method('getQtyShipped')->willReturn(0);
        $item->method('getQtyToShip')->willReturn(25);
        $item->method('getIsVirtual')->willReturn(false);
        $item->method('getId')->willReturn(10000);

        $this->order->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$item]);

        $this->order->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$item]);
        $this->helper->expects($this->once())
            ->method('updateStatusOfOrder')
            ->with('confirmed', 'in_process', $this->order)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $this->logger->expects($this->any())
            ->method('info')
            ->with(
                'Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:183 There\'s no order_line_id in the request Increment ID: 12345, content: ' . $payload,
            )
            ->willReturnSelf();

        $this->orderWebhookEnhancement->execute($payload);
    }

    public function testExecutePayloadHasStatusButOrderDoesntExist()
    {
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'STATE',
                                'to' => 'SHIPPING',
                                'order_line_id' => 1000
                            ]
                        ]
                    ],
                    'id' => '12345-A'
                ]
            ]
        ]);

        $this->orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('loadByIncrementId')
            ->with('12345')
            ->willReturnSelf();

        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->logger->expects($this->any())
            ->method('info')
            ->with(
                'Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:174 Order not found 12345',
            )
            ->willReturnSelf();

        $return = $this->orderWebhookEnhancement->execute($payload);
        $this->assertNull($return);
    }

    public function testExecutePayloadHasNotShipmentUpdateOrAcceptanceStatus()
    {
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'field' => 'NOT_SHIPMENTS'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->logger->expects($this->any())
            ->method('info')
            ->withConsecutive(
                ['Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:166 ' . $payload],
                ['Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:167There\'s no shipment/tracking or acceptance to update']
            )
            ->willReturnSelf();

        $return = $this->orderWebhookEnhancement->execute($payload);
        $this->assertNull($return);
    }

    public function testExecutePayloadWithNoChanges()
    {
        $payload = json_encode([
            'payload' => [
                [
                    'details' => [
                        'no-changes'
                    ]
                ]
            ]
        ]);

        $this->logger->expects($this->any())
            ->method('info')
            ->withConsecutive(
                ['Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:159 ' . $payload],
                ['Fedex\MarketplaceWebhook\Model\OrderWebhookEnhancement::execute:160 Content doesn\'t contains changes']
            )
            ->willReturnSelf();

        $return = $this->orderWebhookEnhancement->execute($payload);
        $this->assertNull($return);
    }

    public function testExecuteNoPayload()
    {
        $payload = json_encode([
            'no-payload'
        ]);
        $return = $this->orderWebhookEnhancement->execute($payload);
        $this->assertTrue($return);
    }

    /**
     * Test getAcceptedItemsFromWebhook function.
     *
     * @return void
     */
    public function testGetAcceptedItemsFromWebhook(): void
    {
        $content = [
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'order_line_id' => 1,
                                'quantity' => 2
                            ],
                            [
                                'order_line_id' => 2,
                                'quantity' => 2
                            ],
                            [
                                'quantity' => 5
                            ],

                        ],
                    ],
                ],
            ],
        ];

        $item = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $item->method('getId')->willReturn('2');
        $item->method('getQtyOrdered')->willReturn(50);


        $this->order->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([
                $item,
            ]);

        $this->orderWebhookEnhancement->getAcceptedItemsFromWebhook($content, $this->order);
    }

    /**
     * Test getIncrementId method.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetIncrementId()
    {
        $content = [
            'payload' => [
                [
                    'id' => '123-1',
                ],
            ],
        ];

        $expectedIncrementId = '123';

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getShipmentLinesMethod = $reflectionClass->getMethod('getIncrementId');
        $getShipmentLinesMethod->setAccessible(true);

        $result = $getShipmentLinesMethod->invokeArgs($this->orderWebhookEnhancement, [$content]);
        $this->assertEquals($expectedIncrementId, $result);
    }

    /**
     * Test getShipmentLines function.
     *
     * @return void
     */
    public function testGetShipmentLines(): void
    {
        $content = [
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'to' => [
                                    'status' => 'SHIPPED',
                                    'shipment_lines' => [
                                        'item1',
                                        'item2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = ['item1', 'item2'];
        $actual = $this->orderWebhookEnhancement->getShipmentLines($content);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test getShipmentLines function.
     *
     * @return void
     */
    public function testGetShipmentLinesEmpty(): void
    {
        $content = [
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'to' => [
                                    'status' => 'OTHER_STATUS',
                                    'shipment_lines' => [
                                        'item1',
                                        'item2',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [];
        $actual = $this->orderWebhookEnhancement->getShipmentLines($content);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test getAcceptedItemsLines function.
     *
     * @return void
     */
    public function testGetAcceptedItemsLines(): void
    {
        $content = [
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'to' => [
                                    'status' => 'WAITING_ACCEPTANCE',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            [
                'to' => [
                    'status' => 'WAITING_ACCEPTANCE',
                ],
            ],
        ];
        $actual = $this->orderWebhookEnhancement->getAcceptedItemsLines($content);

        $this->assertEquals($expected, $actual);
    }


    /**
     * Test getReferenceId function.
     *
     * @return void
     */
    public function testGetReferenceId(): void
    {
        $additionalData = '{"mirakl_shipping_data":{"reference_id":"123456"}}';

        $referenceId = $this->orderWebhookEnhancement->getReferenceId($additionalData);

        $this->assertEquals('123456', $referenceId);
    }


    /**
     * Test getReferenceId function.
     *
     * @return void
     */
    public function testGetReferenceIdEmpty(): void
    {
        $additionalData = '{"mirakl_shipping_data":{}}';

        $referenceId = $this->orderWebhookEnhancement->getReferenceId($additionalData);

        $this->assertEquals('', $referenceId);
    }

    /**
     * Test createDataForDeliveryNotification function.
     *
     * @return void
     */
    public function testCreateDataForDeliveryNotification()
    {
        $orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('getPayment')
            ->willReturn($payment);

        $payment->expects($this->once())
            ->method('getData')
            ->with('retail_transaction_id')
            ->willReturn('123456');

        $this->order->expects($this->once())
            ->method('getIncrementId')
            ->willReturn('100000001');

        $this->orderWebhookEnhancement->createDataForDeliveryNotification($this->order);
    }

    /**
     * Test hasAll3pShipmentCreated function.
     *
     * @return void
     */
    public function testHasAll3pShipmentCreatedReturnsTrueWhenAllItemsAreFullyShipped(): void
    {
        $orderId = 1;
        $additionalData = '{"mirakl_shipping_data":{"reference_id":"123456"}}';
        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $allItems = [
            $this->getMockBuilder(CartItemInterface::class)
                ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass()
        ];

        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn($allItems);

        $allItems[0]->expects($this->once())
            ->method('getMiraklShopId')
            ->willReturn(123);

        $allItems[0]->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(5);

        $allItems[0]->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(5);

        $allItems[0]->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn($additionalData);

        $result = $this->orderWebhookEnhancement->hasAll3pShipmentCreated($this->order);

        $this->assertTrue($result);
    }

    /**
     * Test hasAll3pShipmentCreated function.
     *
     * @return void
     */
    public function testHasAll3pShipmentCreatedReturnsTrueWhenAllItemsAreFullyShippedFalse(): void
    {
        $orderId = 1;
        $additionalData = '{"mirakl_shipping_data":{"reference_id":"123456"}}';
        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $allItems = [
            $this->getMockBuilder(CartItemInterface::class)
                ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass()
        ];

        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn($allItems);

        $allItems[0]->expects($this->once())
            ->method('getMiraklShopId')
            ->willReturn(123);

        $allItems[0]->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(0);

        $allItems[0]->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(5);

        $result = $this->orderWebhookEnhancement->hasAll3pShipmentCreated($this->order);

        $this->assertFalse($result);
    }

    /**
     * Test validateShipments function.
     *
     * @return void
     */
    public function testValidateShipmentsReturnsTrueWhenAllItemsAreShipped(): void
    {
        $orderId = 1;
        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $orderItem1 = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderItem1->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(5);
        $orderItem1->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(5);

        $orderItem2 = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $orderItem2->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(3);
        $orderItem2->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(3);

        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$orderItem1, $orderItem2]);

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $result = $this->orderWebhookEnhancement->validateShipments($this->order);
        $this->assertTrue($result);
    }

    /**
     * Test validateShipments function.
     *
     * @return void
     */
    public function testValidateShipmentsReturnsFalseWhenNotAllItemsAreShipped(): void
    {
        $orderId = 1;
        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn($orderId);

        $orderItem1 = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderItem1->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(5);
        $orderItem1->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(5);

        $orderItem2 = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderItem2->expects($this->once())
            ->method('getQtyOrdered')
            ->willReturn(3);
        $orderItem2->expects($this->once())
            ->method('getQtyShipped')
            ->willReturn(2);

        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$orderItem1, $orderItem2]);

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->order);

        $result = $this->orderWebhookEnhancement->validateShipments($this->order);
        $this->assertFalse($result);
    }

    /**
     * Test updateOrderStatus function.
     *
     * @return void
     */
    public function testUpdateOrderStatusWithConfirmedStatus()
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $item2 = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item, $item2]);
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->order);

        $this->orderWebhookEnhancement->updateOrderStatus($this->order);
    }

    /**
     * Test updateOrderStatus function.
     *
     * @return void
     */
    public function testUpdateOrderStatusWithInProcessStatus()
    {
        $item = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getQTyOrdered')->willReturn(1);
        $item->expects($this->once())->method('getQtyShipped')->willReturn(2);

        $this->order->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with(1)
            ->willReturn($this->order);

        $this->orderWebhookEnhancement->updateOrderStatus($this->order);
    }

    /**
     * Test createInvoice function.
     *
     * @return void
     */
    public function testCreateInvoiceWithFullMiraklOrderAndNoInvoices()
    {
        $this->miraklOrderHelper->expects($this->any())
            ->method('isFullMiraklOrder')
            ->with($this->order)
            ->willReturn(true);

        $this->order->expects($this->once())
            ->method('hasInvoices')
            ->willReturn(false);

        $this->createInvoicePublisher->expects($this->once())
            ->method('execute')
            ->with($this->equalTo((int)$this->order->getId()));

        $this->orderWebhookEnhancement->createInvoice($this->order);
    }

    public function testTrackingHandle()
    {
        $shipmentId = 123;
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'id' => 'mirakl-shipping-id-123',
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_code' => 'carrier',
                        'tracking_number' => '123456',
                        'carrier_name' => 'fedex_office',
                    ],
                ],
                'from' => [
                    'tracking' => [
                        'tracking_number' => '654321'
                    ],
                ]
            ],
        ];

        $this->shipment->expects($this->once())
            ->method('getId')
            ->willReturn($shipmentId);

        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')
            ->withConsecutive(['parent_id', $shipmentId], ['track_number', '654321'])
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->trackRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentTrackSearchResultInterface);

        $this->shipmentTrackSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->any())
            ->method('getData')
            ->willReturn(['id' => 1]);

        $this->trackModel->expects($this->once())
            ->method('setNumber')
            ->with('123456')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('addTrack')
            ->with($this->trackModel)
            ->willReturnSelf();

        $this->trackFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->trackModel);

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $this->orderWebhookEnhancement->trackingHandle($changesDetail, $this->shipment);
    }

    public function testTrackingHandleNew()
    {
        $shipmentId = 123;
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'id' => 'mirakl-shipping-id-123',
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_name' => 'fedex_office',
                        'carrier_code' => 'carrier',
                        'tracking_number' => '123456'
                    ],
                ],
                'from' => [
                    'tracking' => [
                        'tracking_number' => '654321'
                    ],
                ]
            ],
        ];

        $this->shipment->expects($this->once())
            ->method('getId')
            ->willReturn($shipmentId);

        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')
            ->withConsecutive(['parent_id', $shipmentId], ['track_number', '654321'])
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->trackRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentTrackSearchResultInterface);

        $this->shipmentTrackSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->trackFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('setNumber')
            ->with('123456')
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setCarrierCode')
            ->with('fedex_office')
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setTitle')
            ->with('Fedex Office')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('addTrack')
            ->with($this->trackModel)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $this->orderWebhookEnhancement->trackingHandle($changesDetail, $this->shipment);
    }

    public function testCreateNewShipmentItemsAcceptance()
    {
        $itemIds = [
            1 => 25
        ];
        $acceptanceOrderId = '123';
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'id' => 'mirakl-shipping-id-123',
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_code' => 'carrier'
                    ],
                ]
            ],
        ];
        $acceptanceOrderId = '2010137314604305-A';

        $this->orderConverter->expects($this->once())
            ->method('toShipment')
            ->with($this->order)
            ->willReturn($this->shipment);

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyToShip', 'getIsVirtual', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getId')->willReturn(1);
        $item->method('getQtyToShip')->willReturn(25);
        $item->method('getIsVirtual')->willReturn(false);

        $itemWithoutQtyToShip = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyToShip'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemWithoutQtyToShip->method('getQtyToShip')->willReturn(false);

        $this->order->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([
                $item,
                $itemWithoutQtyToShip
            ]);

        $this->orderConverter->expects($this->once())
            ->method('itemToShipmentItem')
            ->with($item)
            ->willReturn($this->shipmentItem);

        $this->shipment->expects($this->once())
            ->method('addItem')
            ->with($this->shipmentItem)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('register')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('setIsInProcess')
            ->with(true)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getMiraklWebhookShippingId = $reflectionClass->getMethod('getMiraklWebhookShippingId');
        $getMiraklWebhookShippingId->setAccessible(true);
        $miraklShippingId = $getMiraklWebhookShippingId->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingId')
            ->with($miraklShippingId)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingReference')
            ->with($acceptanceOrderId)
            ->willReturnSelf();

        $createNewShipmentItemsAcceptance = $reflectionClass->getMethod('createNewShipmentItemsAcceptance');
        $createNewShipmentItemsAcceptance->setAccessible(true);
        $createNewShipmentItemsAcceptance->invokeArgs($this->orderWebhookEnhancement, [
            $itemIds,
            $this->order,
            $acceptanceOrderId,
            $changesDetail
        ]);
    }

    public function testUpdateShipmentItemsQtyAcceptance()
    {
        $this->shipment->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->shipmentItem]);

        $this->shipmentItem->expects($this->once())
            ->method('getOrderItemId')
            ->willReturn(123);

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyShipped', 'setQtyShipped', 'getQtyOrdered', 'save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->any())->method('getQtyShipped')->willReturn(0);
        $item->expects($this->once())->method('setQtyShipped')->with(5)->willReturnSelf();
        $item->expects($this->any())->method('getQtyOrdered')->willReturn(5);
        $item->expects($this->once())->method('save')->willReturnSelf();

        $this->shipmentItem->expects($this->once())
            ->method('setQty')
            ->with(5)
            ->willReturnSelf();

        $this->shipmentItem->expects($this->once())
            ->method('getOrderItem')
            ->willReturn($item);

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $updateShipmentItemsQtyAcceptance = $reflectionClass->getMethod('updateShipmentItemsQtyAcceptance');
        $updateShipmentItemsQtyAcceptance->setAccessible(true);
        $updateShipmentItemsQtyAcceptance->invokeArgs($this->orderWebhookEnhancement, [$this->shipment, [123 => 5]]);
    }

    public function testHasTrackingChanged()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'tracking_number' => 123
                    ]
                ],
                'from' => [
                    'tracking' => [
                        'tracking_number' => null
                    ]
                ],
                'order_line_id' => 123,
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $hasTrackingChanged = $reflectionClass->getMethod('hasTrackingChanged');
        $hasTrackingChanged->setAccessible(true);
        $return = $hasTrackingChanged->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);

        $this->assertEquals(['tracking_number' => 123], $return);
    }

    public function testHasTrackingChangedFalse()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'OTHER',
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $hasTrackingChanged = $reflectionClass->getMethod('hasTrackingChanged');
        $hasTrackingChanged->setAccessible(true);
        $return = $hasTrackingChanged->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);

        $this->assertFalse($return);
    }

    public function testCreateShipmentForItem()
    {
        $miraklShippingId = '12345';

        $this->shipment->expects($this->once())
            ->method('register')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('setIsInProcess')
            ->with(true)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingId')
            ->with($miraklShippingId)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($this->order)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $createShipmentForItem = $reflectionClass->getMethod('createShipmentForItem');
        $createShipmentForItem->setAccessible(true);
        $return = $createShipmentForItem->invokeArgs($this->orderWebhookEnhancement, [
            $this->shipment,
            $this->order,
            $miraklShippingId
        ]);

        $this->assertInstanceOf(Shipment::class, $return);
    }

    public function testCreateShipmentAcceptanceForItem()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'id' => 'mirakl-shipping-id-123',
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_code' => 'carrier'
                    ],
                ]
            ],
        ];
        $acceptanceOrderId = '2010137314604305-A';
        $totalQty = 50;
        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);

        $item = $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getMiraklShopId', 'getQtyOrdered', 'getQtyShipped'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shipment->expects($this->once())
            ->method('register')
            ->willReturnSelf();

        $this->order->expects($this->once())
            ->method('setIsInProcess')
            ->with(true)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->order);

        $getMiraklWebhookShippingId = $reflectionClass->getMethod('getMiraklWebhookShippingId');
        $getMiraklWebhookShippingId->setAccessible(true);
        $miraklShippingId = $getMiraklWebhookShippingId->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingId')
            ->with($miraklShippingId)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setMiraklShippingReference')
            ->with($acceptanceOrderId)
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setShipmentStatus')
            ->with(9)
            ->willReturnSelf();

        $createShipmentAcceptanceForItem = $reflectionClass->getMethod('createShipmentAcceptanceForItem');
        $createShipmentAcceptanceForItem->setAccessible(true);
        $createShipmentAcceptanceForItem->invokeArgs($this->orderWebhookEnhancement, [
            $this->shipment,
            $this->order,
            $acceptanceOrderId,
            $changesDetail,
            $totalQty
        ]);
    }

    public function testUpdateShipmentStatusShipped()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_code' => 'carrier'
                    ],
                ]
            ],
        ];

        $this->helper->expects($this->once())
            ->method('getShipmentStatus')
            ->with('shipped')
            ->willReturn('shipped');

        $this->shipment->expects($this->once())
            ->method('setShipmentStatus')
            ->with('shipped')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setPickupAllowedUntilDate')
            ->with(null)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $updateShipmentStatusShipped = $reflectionClass->getMethod('updateShipmentStatusShipped');
        $updateShipmentStatusShipped->setAccessible(true);

        $updateShipmentStatusShipped->invokeArgs($this->orderWebhookEnhancement, [$this->shipment]);
    }

    public function testUpdateShipmentStatus()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_code' => 'carrier'
                    ],
                ]
            ],
        ];

        $this->helper->expects($this->once())
            ->method('getShipmentStatus')
            ->with('shipping')
            ->willReturn('shipping');

        $this->shipment->expects($this->once())
            ->method('setShipmentStatus')
            ->with('shipping')
            ->willReturnSelf();

        $this->shipment->expects($this->once())
            ->method('setPickupAllowedUntilDate')
            ->with(null)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipment)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $updateShipmentStatus = $reflectionClass->getMethod('updateShipmentStatus');
        $updateShipmentStatus->setAccessible(true);

        $updateShipmentStatus->invokeArgs($this->orderWebhookEnhancement, [$this->shipment]);
    }

    public function testHasShipmentUpdate()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'status' => 'SHIPPING',
                    'tracking' => [
                        'carrier_code' => 'carrier'
                    ],
                ]
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $hasShipmentUpdate = $reflectionClass->getMethod('hasShipmentUpdate');
        $hasShipmentUpdate->setAccessible(true);

        $return = $hasShipmentUpdate->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);
        $this->assertTrue($return);
    }

    public function testHasShipmentUpdateFalse()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'SHIPMENTS',
                'to' => [
                    'status' => 'OTHER_STATUS'
                ]
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $hasShipmentUpdate = $reflectionClass->getMethod('hasShipmentUpdate');
        $hasShipmentUpdate->setAccessible(true);

        $return = $hasShipmentUpdate->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);
        $this->assertFalse($return);
    }

    public function testHasAcceptanceStatus()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'STATE',
                'to' => 'SHIPPING',
                'order_line_id' => 123,
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $hasAcceptanceStatus = $reflectionClass->getMethod('hasAcceptanceStatus');
        $hasAcceptanceStatus->setAccessible(true);

        $return = $hasAcceptanceStatus->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);
        $this->assertTrue($return);
    }

    public function testHasAcceptanceStatusFalse()
    {
        $changesDetail = [
            'changes' => [
                'field' => 'OTHER_STATE',
                'to' => 'SHIPPING',
                'order_line_id' => 123,
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $hasAcceptanceStatus = $reflectionClass->getMethod('hasAcceptanceStatus');
        $hasAcceptanceStatus->setAccessible(true);

        $return = $hasAcceptanceStatus->invokeArgs($this->orderWebhookEnhancement, [$changesDetail]);
        $this->assertFalse($return);
    }

    public function testGetShipment()
    {
        $miraklShippingId = 123;

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('mirakl_shipping_id', $miraklShippingId)
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->shipmentRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentSearchResultInterface);

        $this->shipmentSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->shipmentInterface);

        $this->shipmentInterface->expects($this->once())
            ->method('getData')
            ->willReturn(['id' => 1]);

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getShipment = $reflectionClass->getMethod('getShipment');
        $getShipment->setAccessible(true);

        $getShipment->invokeArgs($this->orderWebhookEnhancement, [$miraklShippingId]);
    }

    public function testGetTrackingByNumber()
    {
        $shipmentId = 123;
        $this->searchCriteriaBuilder->expects($this->any())
            ->method('addFilter')
            ->withConsecutive(['parent_id', $shipmentId], ['track_number', 123])
            ->willReturnSelf();

        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->trackRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentTrackSearchResultInterface);

        $this->shipmentTrackSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->shipmentTrackInterface);

        $this->shipmentTrackInterface->expects($this->once())
            ->method('getData')
            ->willReturn(['id' => 1]);

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);

        $trackingNumberTo = $reflectionClass->getProperty('trackingNumberTo');
        $trackingNumberTo->setAccessible(true);
        $trackingNumberTo->setValue($this->orderWebhookEnhancement, true);

        $trackingNumberFrom = $reflectionClass->getProperty('trackingNumberFrom');
        $trackingNumberFrom->setAccessible(true);
        $trackingNumberFrom->setValue($this->orderWebhookEnhancement, 123);

        $getTrackingByNumber = $reflectionClass->getMethod('getTrackingByNumber');
        $getTrackingByNumber->setAccessible(true);

        $getTrackingByNumber->invokeArgs($this->orderWebhookEnhancement, [$shipmentId]);
    }

    public function testCreateTrackingData()
    {
        $newTrackingData = [
            'carrier_name' => 'fedex_office',
            'tracking_number' => 123
        ];

        $this->trackFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('setNumber')
            ->with(123)
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setCarrierCode')
            ->with('fedex_office')
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setTitle')
            ->with('Fedex Office')
            ->willReturnSelf();

        $this->shipmentInterface->expects($this->once())
            ->method('addTrack')
            ->with($this->trackModel)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipmentInterface)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $createTrackingData = $reflectionClass->getMethod('createTrackingData');
        $createTrackingData->setAccessible(true);

        $createTrackingData->invokeArgs($this->orderWebhookEnhancement, [$this->shipmentInterface, $newTrackingData]);
    }

    public function testCreateTrackingDataAnotherCarrier()
    {
        $newTrackingData = [
            'carrier_name' => 'other_carrier',
            'tracking_number' => 123
        ];

        $this->trackFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->trackModel);

        $this->trackModel->expects($this->once())
            ->method('setNumber')
            ->with(123)
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setCarrierCode')
            ->with('other_carrier')
            ->willReturnSelf();

        $this->trackModel->expects($this->once())
            ->method('setTitle')
            ->with('Federal Express')
            ->willReturnSelf();

        $this->shipmentInterface->expects($this->once())
            ->method('addTrack')
            ->with($this->trackModel)
            ->willReturnSelf();

        $this->shipmentRepository->expects($this->once())
            ->method('save')
            ->with($this->shipmentInterface)
            ->willReturnSelf();

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $createTrackingData = $reflectionClass->getMethod('createTrackingData');
        $createTrackingData->setAccessible(true);

        $createTrackingData->invokeArgs($this->orderWebhookEnhancement, [$this->shipmentInterface, $newTrackingData]);
    }

    public function testGetShipmentByMiraklReference()
    {
        $miraklShippingId = 123;
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('mirakl_shipping_reference', $miraklShippingId)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteria);

        $this->shipmentRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($this->shipmentSearchResultInterface);

        $this->shipmentSearchResultInterface->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->shipmentInterface);

        $this->shipmentInterface->expects($this->once())
            ->method('getData')
            ->willReturn(['id' => 1]);

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getShipmentByMiraklReference = $reflectionClass->getMethod('getShipmentByMiraklReference');
        $getShipmentByMiraklReference->setAccessible(true);

        $getShipmentByMiraklReference->invokeArgs($this->orderWebhookEnhancement, [$miraklShippingId]);
    }

    /**
     * Test getItemsFromWebhook function.
     *
     * @return void
     */
    public function testGetItemsFromWebhook()
    {
        $content = [
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'to' => [
                                    'status' => 'SHIPPED',
                                    'shipment_lines' => [
                                        ['order_line_id' => 1, 'quantity' => 2],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getShipmentLinesMethod = $reflectionClass->getMethod('getItemsFromWebhook');
        $getShipmentLinesMethod->setAccessible(true);

        $getShipmentLinesMethod->invokeArgs($this->orderWebhookEnhancement, [$content]);
    }

    /**
     * Test getItemsFromWebhook function.
     *
     * @return void
     */
    public function testGetItemsFromWebhookEmptyShipmentLines()
    {
        $content = [
            'payload' => [
                [
                    'details' => [
                        'changes' => [
                            [
                                'to' => [
                                    'status' => 'OTHER_STATUS',
                                    'shipment_lines' => [
                                        ['order_line_id' => 1, 'quantity' => 2],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getShipmentLinesMethod = $reflectionClass->getMethod('getItemsFromWebhook');
        $getShipmentLinesMethod->setAccessible(true);

        $getShipmentLinesMethod->invokeArgs($this->orderWebhookEnhancement, [$content]);
    }

    public function testGetMiraklWebhookShippingId()
    {
        $content = [
            'changes' => [
                [
                    'to' => [
                        'id' => '123',
                    ],
                ],
            ]
        ];

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $getShipmentLinesMethod = $reflectionClass->getMethod('getMiraklWebhookShippingId');
        $getShipmentLinesMethod->setAccessible(true);

        $getShipmentLinesMethod->invokeArgs($this->orderWebhookEnhancement, [$content]);
    }

    public function testSendOrderConfirmationAndFujitsuReceiptEmails()
    {
        $this->miraklOrderHelper->expects($this->once())
            ->method('isFullMiraklOrder')
            ->with($this->order)
            ->willReturn(true);

        $this->sendOrderEmailPublisher->expects($this->once())
            ->method('execute')
            ->with('confirmed')
            ->willReturnSelf();

        $this->orderWebhookEnhancement->sendOrderConfirmationAndFujitsuReceiptEmails($this->order);
    }

    public function testSendOrderConfirmationAndFujitsuReceiptEmailsException()
    {
        $exceptionMessage = 'This is an exception message';

        $this->miraklOrderHelper->expects($this->once())
            ->method('isFullMiraklOrder')
            ->with($this->order)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exceptionMessage)
            ->willReturnSelf();

        $this->orderWebhookEnhancement->sendOrderConfirmationAndFujitsuReceiptEmails($this->order);
    }

    public function testCheckShippedQty()
    {
        $orderItemId = 123;
        $this->shipment->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn([$this->shipmentItem]);

        $this->shipmentItem->expects($this->once())
            ->method('getOrderItemId')
            ->willReturn($orderItemId);

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyShipped', 'getItemId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getItemId')->willReturn(123);
        $item->method('getQtyShipped')->willReturn(100);

        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $trackingNumberTo = $reflectionClass->getProperty('existingShipment');
        $trackingNumberTo->setAccessible(true);
        $trackingNumberTo->setValue($this->orderWebhookEnhancement, $this->shipment);

        $order = $reflectionClass->getProperty('order');
        $order->setAccessible(true);
        $order->setValue($this->orderWebhookEnhancement, $this->order);

        $checkShippedQty = $reflectionClass->getMethod('checkShippedQty');
        $checkShippedQty->setAccessible(true);
        $return = $checkShippedQty->invokeArgs($this->orderWebhookEnhancement, []);

        $this->assertTrue($return);
    }

    public function testCheckShippedQtyNotFullyShipped()
    {
        $orderItemId = 123;
        $this->shipment->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn([$this->shipmentItem]);

        $this->shipmentItem->expects($this->once())
            ->method('getOrderItemId')
            ->willReturn($orderItemId);

        $item = $this->getMockBuilder(Item::class)
            ->setMethods(['getQtyShipped', 'getItemId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->method('getItemId')->willReturn(123);
        $item->method('getQtyShipped')->willReturn(0);

        $this->order->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item]);

        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $trackingNumberTo = $reflectionClass->getProperty('existingShipment');
        $trackingNumberTo->setAccessible(true);
        $trackingNumberTo->setValue($this->orderWebhookEnhancement, $this->shipment);

        $order = $reflectionClass->getProperty('order');
        $order->setAccessible(true);
        $order->setValue($this->orderWebhookEnhancement, $this->order);

        $checkShippedQty = $reflectionClass->getMethod('checkShippedQty');
        $checkShippedQty->setAccessible(true);
        $return = $checkShippedQty->invokeArgs($this->orderWebhookEnhancement, []);

        $this->assertFalse($return);
    }

    public function testGetItemsForCurrentShipmentEmpty()
    {
        $reflectionClass = new \ReflectionClass(OrderWebhookEnhancement::class);
        $trackingNumberTo = $reflectionClass->getProperty('existingShipment');
        $trackingNumberTo->setAccessible(true);
        $trackingNumberTo->setValue($this->orderWebhookEnhancement, false);

        $getItemsForCurrentShipment = $reflectionClass->getMethod('getItemsForCurrentShipment');
        $getItemsForCurrentShipment->setAccessible(true);
        $return = $getItemsForCurrentShipment->invokeArgs($this->orderWebhookEnhancement, []);

        $this->assertEmpty($return);
    }
}
