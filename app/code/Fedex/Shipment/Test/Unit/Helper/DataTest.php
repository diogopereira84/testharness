<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Shipment\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Shipment\Track;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Helper\Data;
use Fedex\Shipment\Model\OrderReference;
use Fedex\Shipment\Model\OrderReferenceFactory;
use Fedex\Shipment\Model\OrderValue;
use Fedex\Shipment\Model\OrderValueFactory;
use Fedex\Shipment\Model\ShipmentFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\RateQuoteAndTransactionApiHandler;
/**
 * Test class for Fedex\Shipment\Helper\Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class DataTest extends TestCase
{
    protected $registry;
    protected $quoteFactoryMock;
    protected $quoteMock;
    protected $orderPaymentInterface;
    protected $submitOrderApiMock;
    protected $quoteManagementMock;
    protected $rateQuoteAndTransactionApiHandlerMock;
    protected $storeManagerMock;
    protected $storeMock;
    protected $quoteAddressMock;
    protected $scopeConfig;
    protected $curlMock;
    protected $searchCriteriaBuilderMock;
    protected $productRepositoryMock;
    protected $itemFactoryMock;
    protected $itemMock;
    protected $shopManagement;
    protected $item;
    /** @var ObjectManager|MockObject */
    protected $objectManager;

    /** @var ShipmentFactory|MockObject */
    protected $shipmentStatusFactory;

    /** @var Data|MockObject */
    protected $helperData;

    /** @var OrderReferenceFactory|MockObject */
    protected $orderReferenceFactory;

    /** @var OrderValueFactory|MockObject */
    protected $orderValueFactory;

    /** @var Logger|MockObject */
    protected $logger;

    /** @var Order|MockObject */
    protected $order;

    /** @var OrderFactory|MockObject */
    protected $orderFactory;

    /** @var OrderRepository|MockObject */
    protected $orderRepository;

    /** @var TrackFactory|MockObject */
    protected $trackFactory;

    /** @var ShipmentRepository|MockObject */
    protected $shipmentRepository;

    /** @var Context|MockObject */
    protected $context;

    /** @var CreditmemoSender|MockObject */
    protected $creditmemoSender;

    /** @var CreditmemoLoader|MockObject */
    protected $creditmemoLoader;

    /** @var CreditmemoManagementInterface|MockObject */
    protected $creditmemoManagementInterface;

    /** @var Shipment|MockObject */
    protected $shipment;

    /** @var Order|MockObject */
    protected $orderObject;

    /** @var Order|MockObject */
    protected $orderItem;

    /** @var OrderValue|MockObject */
    protected $orderValue;

    /** @var OrderReference|MockObject */
    protected $orderReference;

    /** @var Track|MockObject */
    protected $track;

    /** @var Track|MockObject */
    protected $trackItem;

    /** @var ToggleConfig|MockObject */
    protected $toggleConfigMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->shipmentStatusFactory = $this->getMockBuilder(ShipmentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderReferenceFactory = $this->getMockBuilder(OrderReferenceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderValueFactory = $this->getMockBuilder(OrderValueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->trackFactory = $this->getMockBuilder(TrackFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentRepository = $this->getMockBuilder(ShipmentRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoLoader = $this->getMockBuilder(CreditmemoLoader::class)
            ->addMethods(['setOrderId', 'setCreditmemo'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoSender = $this->getMockBuilder(CreditmemoSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoManagementInterface = $this->getMockBuilder(CreditmemoManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder(\Magento\Framework\Registry::class)
        ->setMethods(['registry','unregister'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->quoteFactoryMock = $this->getMockBuilder(QuoteFactory::class)
                            ->setMethods(['create','getCollection','addFieldToFilter','getFirstItem'])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
                            ->setMethods([
                                'create',
                                'getCollection',
                                'addFieldToFilter',
                                'getFirstItem',
                                'getId',
                                'setData',
                                'setStore',
                                'setReservedOrderId',
                                'setCustomerIsGuest',
                                'addItem',
                                'getBillingAddress',
                                'getShippingAddress',
                                'setInventoryProcessed',
                                'save',
                                'setPaymentMethod',
                                'getPayment',
                                'importData',
                                'collectTotals'
                            ])
                            ->disableOriginalConstructor()
                            ->getMock();


        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->setMethods([
                'setFedexAccountNumber',
                'save',
                'setMethod',
                'setLastTransId',
                'setTransactionId',
                'setRetailTransactionId',
                'setProductLineDetails',
                'setSiteConfiguredPaymentUsed',
                'setCcLast4'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->submitOrderApiMock = $this->getMockBuilder(SubmitOrderAPI::class)
                            ->setMethods(['getRetailTransactionIdByGtnNumber'])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->quoteManagementMock = $this->getMockBuilder(QuoteManagement::class)
                            ->setMethods(['submit','getIncrementId'])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->rateQuoteAndTransactionApiHandlerMock = $this->getMockBuilder(RateQuoteAndTransactionApiHandler::class)
                            ->setMethods(['getTransactionResponse'])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(['getStore'])
                                    ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->quoteAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['addData','setCollectShippingRates','collectShippingRates','setShippingMethod'])
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList','get','getId'])
            ->getMockForAbstractClass();

        $this->itemFactoryMock = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['setBasePrice','setPrice','setBasePriceInclTax','setPriceInclTax','setDiscount','setRowTotal','setBaseRowTotal','setQty','addOption','setProduct','create'])
            ->getMockForAbstractClass();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['setBasePrice','setPrice','setBasePriceInclTax','setPriceInclTax','setDiscount','setRowTotal','setBaseRowTotal','setQty','addOption','setProduct'])
            ->getMockForAbstractClass();

        $this->shopManagement = $this->createMock(\Fedex\MarketplaceProduct\Model\ShopManagement::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->helperData = $this->objectManager->getObject(
            \Fedex\Shipment\Helper\Data::class,
            [
                'context' => $this->context,
                'shipmentStatusFactory' => $this->shipmentStatusFactory,
                'orderReferenceFactory' => $this->orderReferenceFactory,
                'orderValueFactory' => $this->orderValueFactory,
                'logger' => $this->logger,
                'order' => $this->order,
                'orderFactory' => $this->orderFactory,
                'orderRepository' => $this->orderRepository,
                'trackFactory' => $this->trackFactory,
                'shipmentRepository' => $this->shipmentRepository,
                'creditmemoSender' => $this->creditmemoSender,
                'creditmemoLoader' => $this->creditmemoLoader,
                'creditmemoManagementInterface' => $this->creditmemoManagementInterface,
                'registry' => $this->registry,
                'quoteFactory' => $this->quoteFactoryMock,
                'submitOrderApi' => $this->submitOrderApiMock,
                'quoteManagement' => $this->quoteManagementMock,
                'toggleConfig' => $this->toggleConfigMock,
                'storeManager' => $this->storeManagerMock,
                'rateQuoteTransactionApiHalder' => $this->rateQuoteAndTransactionApiHandlerMock,
                'scopeConfig' => $this->scopeConfig,
                'curl' => $this->curlMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'productRepository' => $this->productRepositoryMock,
                'itemFactory' => $this->itemFactoryMock,
                'shopManagement' => $this->shopManagement
            ]
        );

        $this->shipment = $this->getMockBuilder(\Fedex\Shipment\Model\Shipment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getShipmentStatus',
                    'getTracks',
                    'getTrackNumber',
                    'addTrack',
                    'getCollection',
                    'getOrder',
                    'getId',
                    'getItemsCollection',
                    'setReadyForPickupEmailSent',
                    'setReadyForPickupEmailSentDate',
                    'save',
                    'getItems',
                    'getMiraklShippingReference'
                ]
            )
            ->getMock();

        $this->orderObject = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods([
                'load',
                'getShipmentsCollection',
                'getPayment',
                'getIncrementId',
                'getShippingAmount',
                'getStatus',
                'getGrandTotal',
                'getId',
                'setStatus',
                'save',
                'getAllItems',
                'getItems',
                'addStatusHistoryComment'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this->getMockBuilder(OrderItem::class)
            ->setMethods([
                'setBasePrice',
                'setPrice',
                'setBaseOriginalPrice',
                'setOriginalPrice',
                'setBasePriceInclTax',
                'setPriceInclTax',
                'setDiscount',
                'setRowTotal',
                'setBaseRowTotal',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentsCollection', 'getQtyOrdered'])
            ->getMock();

        $this->orderValue = $this->getMockBuilder(\Fedex\Shipment\Model\OrderValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();

        $this->orderReference = $this->getMockBuilder(\Fedex\Shipment\Model\OrderReference::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToFilter', 'load'])
            ->getMock();

        $this->track = $this->getMockBuilder(\Magento\Sales\Model\Order\Shipment\Track::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->trackItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setOrderId', 'setNumber', 'setCarrierCode', 'setTitle'])
            ->getMock();
    }

    /**
     * Test case for getShipmentById
     */
    public function testGetShipmentById()
    {
        $shipmentId = '1';
        $this->shipmentRepository->expects($this->any())->method('get')->willReturn($this->shipment);
        $this->assertEquals($this->shipment, $this->helperData->getShipmentById($shipmentId));
    }

    /**
     * Test case for getShipmentByIdWithException
     */
    public function testGetShipmentByIdWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $shipmentId = '1';
        $this->shipmentRepository->expects($this->any())->method('get')->willThrowException($exception);
        $this->assertEquals(null, $this->helperData->getShipmentById($shipmentId));
    }

    /**
     * Test getShipmentIdByFxoShipmentId method.
     */
    public function testGetShipmentIdByFxoShipmentId()
    {
        $orderId = '1604';
        $shipmentId = [null];
        $fxoShipmentId = "12345678";
        $this->orderFactory->expects($this->once())->method('create')->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('load')->with($orderId)->willReturn($this->orderItem);

        $datavalue = ["fxo_shipment_id"=>$fxoShipmentId];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($datavalue);
        $this->orderItem->expects($this->any())->method('getShipmentsCollection')->willReturn([$varienObject]);

        $this->assertEquals($shipmentId, $this->helperData->getShipmentIdByFxoShipmentId($orderId, $fxoShipmentId));
    }

    /**
     * Test getShipmentIdByFxoShipmentIdWithException method.
     */
    public function testGetShipmentIdByFxoShipmentIdWithException()
    {
        $orderId = '1604';
        $fxoShipmentId = "12345678";
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->orderFactory->expects($this->once())->method('create')->willThrowException($exception);

        $this->assertEquals(null, $this->helperData->getShipmentIdByFxoShipmentId($orderId, $fxoShipmentId));
    }

    /**
     * Test testSetShipmentEmail method.
     */
    public function testSetShipmentEmail()
    {
        $shipmentId = '1';
        $date = "11:07:2021";
        $this->shipmentRepository->expects($this->any())->method('get')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('setReadyForPickupEmailSent')->willReturn("true");
        $this->shipment->expects($this->any())->method('setReadyForPickupEmailSentDate')->willReturn($date);
        $this->shipment->expects($this->any())->method('save')->willReturn([$this->shipment]);
        $expectedResult = ['message' => 'success'];
        $this->assertEquals($expectedResult, $this->helperData->setShipmentEmail($shipmentId, $date));
    }

    /**
     * Test testSetShipmentEmailWithException method.
     */
    public function testSetShipmentEmailWithException()
    {
        $shipmentId = '1';
        $date = "11:07:2021";
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->shipmentRepository->expects($this->any())->method('get')->willReturn($this->shipment);
        $this->shipment->expects($this->any())
                            ->method('setReadyForPickupEmailSent')
                            ->willThrowException($exception);
        $this->assertEquals($response, $this->helperData->setShipmentEmail($shipmentId, $date));
    }

    /**
     * Test testGetOrderById method.
     */
    public function testGetOrderById()
    {
        $incrementId = '1';
        $orderId = '1';
        $this->orderRepository->expects($this->once())->method('get')->willReturn($this->orderObject);
        $this->orderObject->method('getIncrementId')->willReturn($incrementId);
        $this->order->expects($this->any())->method('loadByIncrementId')
                            ->with($incrementId)
                            ->willReturn($this->orderObject);
        $this->assertEquals($this->orderObject, $this->helperData->getOrderById($orderId));
    }

    /**
     * Test testGetOrderByIdWithException method.
     */
    public function testGetOrderByIdWithException()
    {
        $orderId = '1';
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->orderRepository->expects($this->once())->method('get')->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())
                            ->method('getIncrementId')
                            ->willThrowException($exception);
        $this->assertEquals($response, $this->helperData->getOrderById($orderId));
    }

    /**
     * Test testGetShipmentCollection method.
     */
    public function testGetShipmentCollection()
    {
        $incrementId = '8000000566';
        $response = ['message' => "success"];
        $this->order->expects($this->any())->method('loadByIncrementId')->with($incrementId)->willReturnSelf();
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturn([$this->order]);
        $this->shipmentRepository->expects($this->any())->method('get')->willReturn($this->shipment);
        $this->assertEquals($response, $this->helperData->getShipmentCollection($this->order));
    }

    /**
     * Test testGetShipmentCollection method.
     */
    public function testGetShipmentCollectionWithException()
    {
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->order->expects($this->any())
                            ->method('getShipmentsCollection')
                            ->willThrowException($exception);
        $this->assertEquals($response, $this->helperData->getShipmentCollection($this->order));
    }

    /**
     * Test testUpdateStatusOfOrder method.
     */
    public function testUpdateStatusOfOrder()
    {
        $status = 'new';
        $state = 'new';
        $incrementId = '8000000566';
        $response = ['message' => "success"];
        $this->order->expects($this->any())->method('loadByIncrementId')->with($incrementId)->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->order->expects($this->any())->method('getShipmentsCollection')->willReturnSelf();
        $this->assertEquals(
            $response,
            $this->helperData->updateStatusOfOrder($status, $state, $this->order)
        );
    }

    /**
     * Test testUpdateStatusOfOrderWithException method.
     */
    public function testUpdateStatusOfOrderWithException()
    {
        $status = 'new';
        $state = 'new';
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->order->expects($this->any())
                            ->method('setStatus')
                            ->willThrowException($exception);
        $this->assertEquals(
            $response,
            $this->helperData->updateStatusOfOrder($status, $state, $this->order)
        );
    }

    /**
     * Test testGetShipmentStatus method.
     */
    public function testGetShipmentStatus()
    {
        $shipmentStatus = 'new';
        $datavalue = ["value"=>"new", "key"=>"new"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($datavalue);
        $this->shipmentStatusFactory->expects($this->any())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$varienObject]);
        $this->assertNotNull($this->helperData->getShipmentStatus($shipmentStatus));
    }

    /**
     * Test testGetShipmentStatus method.
     */
    public function testGetShipmentStatusEmpty()
    {
        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->once())->method('getCollection')->willReturnSelf();
        $this->assertNull($this->helperData->getShipmentStatus("new"));
    }

    /**
     * Test testGetShipmentStatusWithException method.
     */
    public function testGetShipmentStatusWithException()
    {
        $shipmentStatus = 'new';
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->shipmentStatusFactory->expects($this->any())
                            ->method('create')
                            ->willThrowException($exception);
        $this->helperData->getShipmentStatus($shipmentStatus);
    }

    /**
     * Test testGetShipmentStatusByValue method.
     */
    public function testGetShipmentStatusByValue()
    {
        $shipmentStatus = 'new';
        $shipmentItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();
        $datavalue = ["value"=>"new", "key"=>"new"];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($datavalue);
        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$varienObject]);
        $this->assertEquals('new', $this->helperData->getShipmentStatusByValue($shipmentStatus));
    }

    /**
     * Test testGetShipmentStatusByValueEmpty method.
     */
    public function testGetShipmentStatusByValueEmpty()
    {
        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->once())->method('getCollection')->willReturnSelf();
        $this->assertNull($this->helperData->getShipmentStatusByValue("new"));
    }

    /**
     * Test testGetShipmentStatusByValueWithException method.
     */
    public function testGetShipmentStatusByValueWithException()
    {
        $shipmentStatus = 'new';
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->shipmentStatusFactory->expects($this->once())
                            ->method('create')
                            ->willThrowException($exception);
        $this->helperData->getShipmentStatusByValue($shipmentStatus);
    }

    /**
     * Test testGetTracking method.
     */
    public function testGetTracking()
    {
        $shipmentId = '1';
        $this->shipment->method('getTracks')->willReturn([$this->shipment]);
        $this->testGetShipmentById();
        $this->assertEquals(null, $this->helperData->getTracking($shipmentId));
    }

    /**
     * Test testGetTrackingWithNoShipment method.
     */
    public function testGetTrackingWithNoShipment()
    {
        $shipmentId = '1';
        $this->testGetShipmentByIdWithException();
        $this->assertEquals(null, $this->helperData->getTracking($shipmentId));
    }

    /**
     * Test testGetFirstTrackingNumber method.
     */
    public function testGetFirstTrackingNumber()
    {
        $shipmentId = '1';
        $this->shipment->expects($this->once())->method('getTrackNumber')->willReturn(1234);
        $this->shipment->expects($this->once())->method('getTracks')->willReturn([$this->shipment]);
        $this->testGetShipmentById();
        $this->assertEquals(1234, $this->helperData->getFirstTrackingNumber($shipmentId));
    }

    /**
     * Test testGetFirstTrackingNumberException method.
     */
    public function testGetFirstTrackingNumberException()
    {
        $shipmentId = '1';
        $this->shipmentRepository->expects($this->once())->method('get')->willThrowException(
            new \Exception('some exception')
        );
        $this->assertEquals(null, $this->helperData->getFirstTrackingNumber($shipmentId));
    }

    public function testIsMultipleShipmentNotMarketPlaceOrder(): void
    {
        $result = $this->helperData->isMultipleShipment($this->orderObject, 1, false);
        $this->assertFalse($result);
    }

    public function testIsMultipleShipmentToggleEnabled(): void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->orderObject->method('getItems')->willReturn([
            $this->createOrderItemMock(10, 5, false),
            $this->createOrderItemMock(10, 5, true)
        ]);

        $result = $this->helperData->isMultipleShipment($this->orderObject, 1, true);
        $this->assertTrue($result);
    }

    /**
     * Test testIsMultiShipmentException method.
     */
    public function testIsMultiShipmentException()
    {
        $shipmentId = '1';
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $this->shipmentRepository->expects($this->any())->method('get')->willThrowException(
            new \Exception('some exception')
        );

        $this->assertEquals(false, $this->helperData->isMultipleShipment($this->orderObject, $shipmentId, true));
    }

    private function createOrderItemMock($qtyOrdered, $qtyShipped, $isMiraklOffer): Order\Item
    {
        $itemMock = $this->getMockBuilder(Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQtyOrdered', 'getQtyShipped'])
            ->addMethods(['getMiraklOfferId'])
            ->getMock();
        $itemMock->method('getQtyOrdered')->willReturn($qtyOrdered);
        $itemMock->method('getQtyShipped')->willReturn($qtyShipped);
        $itemMock->method('getMiraklOfferId')->willReturn($isMiraklOffer ? 1 : null);
        return $itemMock;
    }

    /**
     * Test testGetShipmentIds method.
     */
    public function testGetShipmentIds()
    {
        $orderId = 1234;
        $shipmentIds = [0 => null];
        $this->shipment->setIds($shipmentIds);
        $this->orderFactory->expects($this->once())->method('create')->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('load')->with($orderId)->willReturn($this->orderItem);
        $this->orderItem->expects($this->any())->method('getShipmentsCollection')->willReturn([$this->shipment]);
        $this->assertEquals($shipmentIds, $this->helperData->getShipmentIds($orderId));
    }

    /**
     * Test testGetShipmentIdsWithException method.
     */
    public function testGetShipmentIdsWithException()
    {
        $orderId = 1234;
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->orderFactory->expects($this->once())
                            ->method('create')
                            ->willThrowException($exception);
        $this->assertEquals($response, $this->helperData->getShipmentIds($orderId));
    }

    /**
     * Test testUpdateShipmentStatus method.
     */
    public function testUpdateShipmentStatus()
    {
        $pickupAllowedUntilDate = 'DD-MM-YYYY';
        $courier = 'fedex_office';
        $trackingNumber = '11';
        $shipmentStatus = 'new';

        $shipmentIds = [0 => 1];
        $this->shipment->setIds($shipmentIds);
        $shipmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentInterface::class)
            ->setMethods(['getId', 'addTrack', 'setPickupAllowedUntilDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $shipmentItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$shipmentItem]);

        $this->shipmentRepository->expects($this->once())->method('save')->will($this->returnValue($shipmentMock));
        $this->trackFactory->expects($this->once())->method('create')->willReturn($this->track);
        $this->track->expects($this->any())->method('load')->willReturn($this->trackItem);
        $this->helperData->updateShipmentStatus(
            $shipmentMock,
            $trackingNumber,
            $shipmentStatus,
            $courier,
            $pickupAllowedUntilDate
        );
    }

    /**
     * Test testUpdateShipmentStatusWithFedexExpress method.
     */
    public function testUpdateShipmentStatusWithFedexExpress()
    {
        $pickupAllowedUntilDate = 'DD-MM-YYYY';
        $courierOption = 'fedex_express';
        $trackingNumber = '11';
        $shipmentStatus = 'new';

        $shipmentIds = [0 => 1];
        $this->shipment->setIds($shipmentIds);
        $shipmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentInterface::class)
            ->setMethods(['getId', 'addTrack', 'setPickupAllowedUntilDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $shipmentItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$shipmentItem]);

        $this->shipmentRepository->expects($this->once())->method('save')->will($this->returnValue($shipmentMock));
        $this->trackFactory->expects($this->once())->method('create')->willReturn($this->track);
        $this->track->expects($this->any())->method('load')->willReturn($this->trackItem);
        $this->helperData->updateShipmentStatus(
            $shipmentMock,
            $trackingNumber,
            $shipmentStatus,
            $courierOption,
            $pickupAllowedUntilDate
        );
    }

    /**
     * Test testUpdateShipmentStatusWithShipmentTrackId method.
     */
    public function testUpdateShipmentStatusWithShipmentTrackId()
    {
        $pickupAllowedUntilDate = 'DD-MM-YYYY';
        $courierOption = 'fedex_express';
        $trackingNumber = '11';
        $shipmentStatus = 'new';
        $shipmentIds = [0 => 1];
        $this->shipment->setIds($shipmentIds);
        $shipmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentInterface::class)
            ->setMethods(['getId', 'addTrack', 'setPickupAllowedUntilDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $shipmentItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$shipmentItem]);
        $this->shipmentRepository->expects($this->once())->method('get')->will($this->returnValue($shipmentMock));
        $this->shipmentRepository->expects($this->once())->method('save')->will($this->returnValue($shipmentMock));
        $this->trackFactory->expects($this->once())->method('create')->willReturn($this->track);
        $this->track->expects($this->any())->method('load')->willReturn($this->trackItem);
        $this->helperData->updateShipmentStatus(
            $shipmentMock,
            $trackingNumber,
            $shipmentStatus,
            $courierOption,
            $pickupAllowedUntilDate
        );
    }

    /**
     * Test testGetShipmentCollectionWithException method.
     */
    public function testUpdateShipmentStatusExceptionWithTrackId()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $pickupAllowedUntilDate = 'DD-MM-YYYY';
        $courierOption = 'fedex_express';
        $trackingNumber = '11';
        $shipmentStatus = 'new';
        $shipmentIds = [0 => 1];
        $this->shipment->setIds($shipmentIds);
        $shipmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentInterface::class)
            ->setMethods(['getId', 'addTrack', 'setPickupAllowedUntilDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->trackFactory->expects($this->once())->method('create')->willThrowException($exception);
        $actualResponse = $this->helperData->updateShipmentStatus(
            $shipmentMock,
            $trackingNumber,
            $shipmentStatus,
            $courierOption,
            $pickupAllowedUntilDate
        );
        $this->assertNull($actualResponse);
    }

    /**
     * Test testUpdateShipmentStatusExceptionWithTrackNumber method.
     */
    public function testUpdateShipmentStatusExceptionWithTrackNumber()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $pickupAllowedUntilDate = 'DD-MM-YYYY';
        $courierOption = 'fedex_express';
        $trackingNumber = '11';
        $shipmentStatus = 'new';
        $shipmentIds = [0 => 1];
        $this->shipment->setIds($shipmentIds);
        $shipmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentInterface::class)
            ->setMethods(['getId', 'addTrack', 'setPickupAllowedUntilDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $shipmentItem = $this->getMockBuilder(
            DataObject::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMock();

        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$shipmentItem]);
        $this->shipmentRepository->expects($this->once())->method('get')->will($this->returnValue($shipmentMock));
        $this->shipmentRepository->expects($this->once())->method('save')->willThrowException($exception);
        $this->trackFactory->expects($this->once())->method('create')->willReturn($this->track);
        $this->track->expects($this->any())->method('load')->willReturn($this->trackItem);
        $this->helperData->updateShipmentStatus(
            $shipmentMock,
            $trackingNumber,
            $shipmentStatus,
            $courierOption,
            $pickupAllowedUntilDate
        );
    }

    /**
     * Test testGetGrandTotalByOrder method.
     */
    public function testGetGrandTotalByOrderWithException()
    {
        $orderId = '10';
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->orderRepository->expects($this->once())
                            ->method('get')
                            ->willThrowException($exception);
        $this->assertEquals($response, $this->helperData->getGrandTotalByOrder($orderId));
    }

    /**
     * Test testGetGrandTotalByOrder method.
     */
    public function testGetGrandTotalByOrder()
    {
        $incrementId = '1';
        $grandTotal = '20';
        $orderId = '10';
        $this->orderRepository->expects($this->once())->method('get')->willReturn($this->orderObject);
        $this->orderObject->method('getIncrementId')->willReturn($incrementId);
        $this->order->expects($this->once())->method('loadByIncrementId')
                                ->with($incrementId)
                                ->willReturn($this->orderObject);
        $this->orderObject->method('getGrandTotal')->willReturn($grandTotal);
        $this->assertEquals($grandTotal, $this->helperData->getGrandTotalByOrder($orderId));
    }

    /**
     * Test testGetShippingAmountByOrder method.
     */
    public function testGetShippingAmountByOrder()
    {
        $shippingAmount = '20';
        $this->orderObject->method('getShippingAmount')->willReturn($shippingAmount);
        $this->shipment->method('getMiraklShippingReference')->willReturn(false);
        $this->assertEquals($shippingAmount, $this->helperData->getShippingAmountByOrder($this->orderObject, $this->shipment));
    }

    /**
     * Test testGetShippingAmountByOrderWithException method.
     */
    public function testGetShippingAmountByOrderWithException()
    {
        $response = ['code' => '400', 'message' => 'Exception message'];
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->orderObject->expects($this->once())
                            ->method('getShippingAmount')
                            ->willThrowException($exception);
        $this->shipment->method('getMiraklShippingReference')->willReturn(false);
        $this->assertEquals($response, $this->helperData->getShippingAmountByOrder($this->orderObject, $this->shipment));
    }

    /**
     * Test case for setOrderValueNumber
     */
    public function testsetOrderValueNumber()
    {
        $orderReference = [
            'key' => 1,
            'value' => 'value',
        ];

        $customerOrderNumber = '1234';

        $orderStatusUpdateRequest = [
            'fxoWorkOrderNumber' => 12,
            'customerOrderNumber' => $customerOrderNumber,
            'orderCreatedBySystem' => 1,
            'transactionId' => 1,
            'orderReferences' => [$orderReference],
            'shipmentItems' => [
                'shipmentId' => 112,
                'pickupAllowedUntilDate' => '2018-01-25T09:50:35Z',
                'status' => 'new',
                'trackingNumber' => 11,
                'courier' => 'FEDEX',
                'exceptionReason' => 'no',
            ],
        ];

        $orderValueNumber = $this->getMockBuilder(DataObject::class)
        ->disableOriginalConstructor()
        ->setMethods(['getId', 'setId', 'setFxoWorkOrderNumber', 'setTransactionId',
            'setCustomerOrderNumber', 'setOrderCreatedSystem', 'save'])
        ->getMock();
        $orderValueNumber->expects($this->any())->method('getId')->willReturn('1');
        $this->orderValueFactory->expects($this->once())->method('create')->willReturn($this->orderValue);
        $this->orderValue->expects($this->any())->method('load')
        ->with($customerOrderNumber, "customer_order_number")
        ->willReturn($orderValueNumber);
        $this->assertEquals(true, $this->helperData->insertOrderReference($orderStatusUpdateRequest));
    }
    /**
     * Test case for setOrderValueNumber
     */
    public function testsetOrderValueNumberWithoutId()
    {
        $orderReference = [
            'key' => 1,
            'value' => 'value',
        ];

        $customerOrderNumber = '1234';

        $orderStatusUpdateRequest = [
            'fxoWorkOrderNumber' => 12,
            'customerOrderNumber' => $customerOrderNumber,
            'orderCreatedBySystem' => 1,
            'transactionId' => 1,
            'orderReferences' => [$orderReference],
            'shipmentItems' => [
                'shipmentId' => 112,
                'pickupAllowedUntilDate' => '2018-01-25T09:50:35Z',
                'status' => 'new',
                'trackingNumber' => 11,
                'courier' => 'FEDEX',
                'exceptionReason' => 'no',
            ],
        ];

        $orderValueNumber = $this->getMockBuilder(DataObject::class)
        ->disableOriginalConstructor()
        ->setMethods(['getId', 'setId', 'setFxoWorkOrderNumber', 'setTransactionId',
            'setCustomerOrderNumber', 'setOrderCreatedSystem', 'save'])
        ->getMock();
        $this->orderValueFactory->expects($this->once())->method('create')->willReturn($this->orderValue);
        $this->orderValue->expects($this->any())->method('load')
        ->with($customerOrderNumber, "customer_order_number")
        ->willReturn($orderValueNumber);
        $this->assertEquals(true, $this->helperData->insertOrderReference($orderStatusUpdateRequest));
    }

    /**
     * Test case for insertOrderReference
     */
    public function testinsertOrderReference()
    {
        $orderReference = [
            'key' => 1,
            'value' => 'value',
        ];

        $customerOrderNumber = '1234';

        $orderStatusUpdateRequest = [
            'shipmentItems' => [
                'shipmentId' => 112,
                'pickupAllowedUntilDate' => '2018-01-25T09:50:35Z',
                'status' => 'new',
                'trackingNumber' => 11,
                'courier' => 'FEDEX',
                'exceptionReason' => 'no',
            ]
        ];

        $orderValueNumber = $this->getMockBuilder(DataObject::class)
        ->disableOriginalConstructor()
        ->setMethods(['getId', 'setId', 'setFxoWorkOrderNumber', 'setTransactionId',
            'setCustomerOrderNumber', 'setOrderCreatedSystem', 'save'])
        ->getMock();
        $this->orderValueFactory->expects($this->any())->method('create')->willReturn($this->orderValue);
        $this->orderValue->expects($this->any())->method('load')
        ->with($customerOrderNumber, "customer_order_number")
        ->willReturn($orderValueNumber);
        $this->assertEquals(true, $this->helperData->insertOrderReference($orderStatusUpdateRequest));
    }

    /**
     * Test for getOrderIdByShipmentId
     */
    public function testgetOrderIdByShipmentId()
    {
        $this->shipmentRepository->expects($this->any())->method('get')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getOrder')->will($this->returnValue($this->orderObject));
        $this->orderObject->expects($this->any())->method('getId')->will($this->returnValue(1));
        $this->assertNotNull($this->helperData->getOrderIdByShipmentId(1));
    }

    public function testGetOrderItems()
    {
        $itemId = 23;
        $orderItem = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Item::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getName',
                'getQtyOrdered',
                'getItemId',
                'getMiraklOfferId',
                'getMiraklShopName',
                'getMiraklShippingTypeLabel',
                'getAdditionalData',
                'getProduct',
                'getRowTotal',
                'getOrderItemId',
                'getParentItemId'
            ])->getMock();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemsCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $orderMock->expects($this->once())->method('getItemsCollection')->willReturn([$orderItem]);
        $orderItem->expects($this->once())->method('getName')->willReturn('Product Name');
        $orderItem->expects($this->once())->method('getQtyOrdered')->willReturn(2);
        $orderItem->expects($this->exactly(7))->method('getItemId')->willReturn($itemId);
        $orderItem->expects($this->once())->method('getMiraklOfferId')->willReturn(1505);
        $orderItem->expects($this->once())->method('getMiraklShippingTypeLabel')->willReturn('Type Label');
        $orderItem->expects($this->once())->method('getProduct')->willReturn($productMock);
        $orderItem->expects($this->once())->method('getRowTotal')->willReturn(21);
        $orderItem->expects($this->once())->method('getParentItemId')->willReturn(null);

        $shopInterface = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
            ->disableOriginalCOnstructor()
            ->addMethods(['getSellerAltName'])
            ->getMockForAbstractClass();
        $shopInterface->expects($this->once())->method('getSellerAltName')->willReturn('Shop Name');
        $this->shopManagement->expects($this->once())->method('getShopByProduct')->willReturn($shopInterface);
        $result = $this->helperData->getOrderItems($orderMock, [$itemId => 15.56]);

        $expectedResult[$itemId] = [
            'name' => 'Product Name',
            'qty' => 2,
            'row_total' => 21,
            'is_child' => false,
            'mirakl_shop_name' => 'Shop Name',
            'mirakl_shipping_label' =>  'Type Label',
            'mirakl_shipping_expected_delivery' =>  FALSE,
            'mirakl_shipping_total' => 15.56,
            'surcharge' => 0
        ];
        $this->assertEquals($result, $expectedResult);
    }

    /**
     * Test testGenerateRefund method.
     */
    public function testGenerateRefund()
    {
        $orderId = '2';
        $itemIds = ['1', '2'];
        $status = 'delivered';
        $orderItemIds  = [];
        $orderItemIds['1']['qty'] = 2;
        $shipmentId = '1';
        $creditMemoData = [];
        $creditMemoData['do_offline'] = 1;
        $creditMemoData['adjustment_positive'] = 0;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['items'] = $orderItemIds;
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($creditMemoData);
        $this->creditmemoLoader->expects($this->any())
            ->method('setOrderId')
            ->with(2)->willReturnSelf();
        $this->creditmemoLoader->expects($this->any())
            ->method('setCreditmemo')
            ->willReturn($varienObject);
        $creditMemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())->method('registry')->willReturn(true);
        $this->registry->expects($this->any())->method('unregister')->willReturn(true);
        $creditMemo->method('isValidGrandTotal')->willReturn(true);
        $this->creditmemoLoader->expects($this->any())->method('load')->willReturn($creditMemo);

        $this->shipment->expects($this->once())->method('getShipmentStatus')->willReturn($status);

        $datavalue = ["value"=>$status, "key"=>$status];
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($datavalue);
        $this->shipmentStatusFactory->expects($this->once())->method('create')->willReturn($this->shipment);
        $this->shipment->expects($this->any())->method('getCollection')->willReturn([$varienObject]);

        $this->creditmemoManagementInterface->expects($this->any())->method('refund')
            ->willReturn($this->creditmemoLoader, $creditMemoData['do_offline']);
        $this->helperData->generateRefund($orderId, $status, $orderItemIds, $this->orderObject, $this->shipment);
    }

    /**
     * Test testGenerateRefund method.
     */
    public function testGenerateRefundWithCancelStatus()
    {
        $orderId = '2';
        $itemIds = ['1', '2'];
        $status = 'canceled';
        $orderItemIds  = [];
        $orderItemIds['1']['qty'] = 2;
        $shipmentId = '1';
        $creditMemoData = [];
        $creditMemoData['do_offline'] = 1;
        $creditMemoData['adjustment_positive'] = 0;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['items'] = $orderItemIds;
        $varienObject = new \Magento\Framework\DataObject();
        $varienObject->setData($creditMemoData);
        $this->creditmemoLoader->expects($this->any())
            ->method('setOrderId')
            ->with(2)->willReturnSelf();
        $this->creditmemoLoader->expects($this->any())
            ->method('setCreditmemo')
            ->willReturn($varienObject);
        $creditMemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $creditMemo->method('isValidGrandTotal')->willReturn(true);
        $this->creditmemoLoader->expects($this->any())->method('load')->willReturn($creditMemo);
        $incrementId = '1';
        $shippingAmount = '20';
        $orderId = '10';
        $this->orderObject->method('getIncrementId')->willReturn($incrementId);

        $this->orderObject->method('getShippingAmount')->willReturn($shippingAmount);

        $this->creditmemoManagementInterface->expects($this->any())->method('refund')
            ->willReturn($this->creditmemoLoader, $creditMemoData['do_offline']);
        $this->helperData->generateRefund($orderId, $status, $orderItemIds, $this->orderObject, $this->shipment);
    }

    /**
     * Test testGenerateRefundWithException method.
     */
    public function testGenerateRefundWithException()
    {
        $orderId = '2';
        $itemIds = ['1', '2'];
        $status = 'delivered';
        $orderItemIds = [];
        $orderItemIds['1']['qty'] = 2;
        $shipmentId = '1';

        $creditMemoData = [];
        $creditMemoData['do_offline'] = 1;
        $creditMemoData['adjustment_positive'] = 0;
        $creditMemoData['adjustment_negative'] = 0;
        $creditMemoData['items'] = $orderItemIds;

        $varientObject = new \Magento\Framework\DataObject();
        $varientObject->setData($creditMemoData);

        $this->creditmemoLoader->expects($this->any())->method('setOrderId')->with(2)->willReturnSelf();
        $this->creditmemoLoader->expects($this->any())->method('setCreditmemo')->willReturn($varientObject);
        $creditMemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemoLoader->expects($this->any())->method('load')->willReturn($creditMemo);
        $this->creditmemoManagementInterface->expects($this->any())
                                        ->method('refund')
                                        ->willReturn($this->creditmemoLoader, $creditMemoData['do_offline']);
        $this->helperData->generateRefund($orderId, $status, $orderItemIds, $this->orderObject, $this->shipment);
    }

    /**
     * Test testDetetermineOrderStatusForDelivered method.
     */
    public function testDetetermineOrderStatusForDelivered()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "delivered"];
        $shipmentStatusKey = 'delivered';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $expectedResult = 'complete';
        $this->assertEquals($expectedResult, $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForCanceled method.
     */
    public function testDetetermineOrderStatusForCancelled()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "cancelled"];
        $shipmentStatusKey = 'cancelled';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals('canceled', $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForConfirmed method.
     */
    public function testDetetermineOrderStatusForConfirmed()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "confirmed"];
        $shipmentStatusKey = 'confirmed';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals('confirmed', $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForNew method.
     */
    public function testDetetermineOrderStatusForNew()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "new"];
        $shipmentStatusKey = 'new';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals('new', $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForReadyForPickup method.
     */
    public function testDetetermineOrderStatusForReadyForPickup()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "ready_for_pickup"];
        $shipmentStatusKey = 'ready_for_pickup';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals('ready_for_pickup', $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForShipped method.
     */
    public function testDetetermineOrderStatusForShipped()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "shipped"];
        $shipmentStatusKey = 'shipped';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals('shipped', $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForInProcess method.
     */
    public function testDetetermineOrderStatusForInProcess()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => "in_progress"];
        $shipmentStatusKey = 'in_progress';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals('in_progress', $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusForEmpty method.
     */
    public function testDetetermineOrderStatusForEmpty()
    {
        $orderId = 1001;
        $shipmentIds = ['12', '13'];
        $shipmentStatus = [0 => ""];
        $shipmentStatusKey = '';
        $this->helperData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentById', 'getShipmentIds', 'getShipmentStatusByValue'])
            ->getMock();
        $this->helperData->method('getShipmentIds')->will($this->returnValue($shipmentIds));
        $this->helperData->method('getShipmentById')->will($this->returnValue($this->shipment));
        $this->shipment->method('getShipmentStatus')->will($this->returnValue($shipmentStatus));
        $this->helperData->method('getShipmentStatusByValue')->will($this->returnValue($shipmentStatusKey));
        $this->assertEquals(null, $this->helperData->detetermineOrderStatus($orderId));
    }

    /**
     * Test testDetetermineOrderStatusWithException method.
     */
    public function testDetetermineOrderStatusWithException()
    {
        $orderId = '1001';
        $this->expectException("Error");
        $this->helperData->detetermineOrderStatus($orderId);
    }

    /**
     * testCreateOrderbyGTN
     */
    public function testCreateOrderbyGTN()
    {
        $incrementId = 12345;
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(1);
        $this->submitOrderApiMock->expects($this->any())->method('getRetailTransactionIdByGtnNumber')
        ->willReturn('123');
        $this->quoteManagementMock->expects($this->any())->method('submit')->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getIncrementId')->willReturn($incrementId);
        $this->orderObject->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->orderObject->expects($this->any())->method('save')->willReturnSelf();
        $requestData = ['transactionId'=>'559782864C2442C03X'];
        $this->assertEquals($incrementId, $this->helperData->createOrderbyGTN('new', $requestData));
    }

    /**
     * testCreateOrderbyGTNWithNoTransactionId
     */
    public function testCreateOrderbyGTNWithNoTransactionId()
    {
        $incrementId = 12345;
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(1);
        $this->submitOrderApiMock->expects($this->any())->method('getRetailTransactionIdByGtnNumber')
        ->willReturn('123');
        $this->quoteManagementMock->expects($this->any())->method('submit')->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getIncrementId')->willReturn($incrementId);
        $this->orderObject->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->orderObject->expects($this->any())->method('save')->willReturnSelf();
        $requestData = ['transactionId'=>''];
        $this->assertEquals($incrementId, $this->helperData->createOrderbyGTN('new', $requestData));
    }

    /**
     * [testCreateOrderbyGTNwithException]
     */
    public function testCreateOrderbyGTNwithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->quoteFactoryMock->expects($this->once())->method('create')->willThrowException($exception);
        $requestData = ['transactionId'=>'559782864C2442C03X'];
        $this->assertEquals(false, $this->helperData->createOrderbyGTN('new', $requestData));
    }

    /**
     * testCreateMissedOrders
     *
     */
    public function testCreateMissedOrders()
    {
        $transactionId = "ASADA12312ASAS";
        $gtn = '123120000123';
        $jsonUrl = 'https://wwwtest.fedex.com/templates/components/apps/easyprint/content/staticProducts/product-menuHierarchy.json';
        $transactionResponse = $this->getTransactionResponsewithShipping();

        $this->rateQuoteAndTransactionApiHandlerMock->expects($this->any())->method('getTransactionResponse')->willReturn($transactionResponse);

        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setStore')->with($this->storeMock)->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setReservedOrderId')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setCustomerIsGuest')->willReturnSelf();
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn($jsonUrl);

        $response = '{"productMenuDetails": [{"id": "1580755825569","productId": "1559886500133"
        },{"id": "1614105200640","productId": "1559886500133"}]}';

        $this->curlMock->expects($this->any())->method('getBody')->willreturn($response);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();

        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteriaMock);

        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $searchResults = $this->getMockBuilder(ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productRepositoryMock->expects($this->once())->method('getList')->with($searchCriteriaMock)->willReturn($searchResults);

        $searchResults->method('getItems')->willReturn([$product]);

        $product->expects($this->once())->method('getSku')->wilLReturn('SKU');

        $this->productRepositoryMock->method('get')->willReturnSelf();

        $this->itemFactoryMock->expects($this->any())->method('create')->willReturn($this->itemMock);

        $this->itemMock->expects($this->any())->method('setBasePrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setBasePriceInclTax')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setPriceInclTax')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setDiscount')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setQty')->willReturnSelf();

        $this->productRepositoryMock->method('getId')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('addItem')->with($this->itemMock)->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->expects($this->any())->method('addData')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setInventoryProcessed')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('save')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('setPaymentMethod')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('getPayment')->willReturnSelf();

        $importData = ['method' => 'fedexccpay','cc_last_4' => '4105'];

        $this->quoteMock->expects($this->any())->method('importData')->with($importData)->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('collectTotals')->willReturnSelf();

        $this->quoteManagementMock->expects($this->any())->method('submit')->willReturn($this->orderObject);

        $this->orderObject->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);

        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();

        $this->orderObject->expects($this->any())->method("getAllItems")->willReturn([$this->item]);

        $this->item->expects($this->any())->method("setBasePrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setPrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setBaseOriginalPrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setOriginalPrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setBasePriceInclTax")->willReturnSelf();
        $this->item->expects($this->any())->method("setPriceInclTax")->willReturnSelf();
        $this->item->expects($this->any())->method("setDiscount")->willReturnSelf();
        $this->item->expects($this->any())->method("setRowTotal")->willReturnSelf();
        $this->item->expects($this->any())->method("setBaseRowTotal")->willReturnSelf();

        $this->orderObject->expects($this->any())->method("addStatusHistoryComment")->willReturnSelf();

        $this->helperData->createMissedOrders($this->quoteMock, $transactionId, $gtn);
    }

    /**
     * testCreateMissedOrderswithPickup
     *
     */
    public function testCreateMissedOrderswithPickup()
    {
        $transactionId = "ASADA12312ASAS";
        $gtn = '123120000123';
        $jsonUrl = 'https://wwwtest.fedex.com/templates/components/apps/easyprint/content/staticProducts/product-menuHierarchy.json';
        $transactionResponse = $this->getTransactionResponsewithPickup();

        $this->rateQuoteAndTransactionApiHandlerMock->expects($this->any())->method('getTransactionResponse')->willReturn($transactionResponse);

        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setStore')->with($this->storeMock)->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setReservedOrderId')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setCustomerIsGuest')->willReturnSelf();
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn($jsonUrl);

        $response = '{"productMenuDetails": [{"id": "1580755825569","productId": "1559886500133"
        },{"id": "1614105200640","productId": "1559886500133"}]}';

        $this->curlMock->expects($this->any())->method('getBody')->willreturn($response);

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();

        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteriaMock);

        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $searchResults = $this->getMockBuilder(ProductSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productRepositoryMock->expects($this->any())->method('getList')->with($searchCriteriaMock)->willReturn($searchResults);

        $searchResults->method('getItems')->willReturn([$product]);

        $product->expects($this->any())->method('getSku')->wilLReturn('SKU');

        $this->productRepositoryMock->method('get')->willReturnSelf();

        $this->itemFactoryMock->expects($this->any())->method('create')->willReturn($this->itemMock);

        $this->itemMock->expects($this->any())->method('setBasePrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setBasePriceInclTax')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setPriceInclTax')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setDiscount')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setQty')->willReturnSelf();

        $this->productRepositoryMock->method('getId')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('addItem')->with($this->itemMock)->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->expects($this->any())->method('addData')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('collectShippingRates')->willReturnSelf();
        $this->quoteAddressMock->expects($this->any())->method('setShippingMethod')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setInventoryProcessed')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('save')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('setPaymentMethod')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('getPayment')->willReturnSelf();

        $importData = ['method' => 'fedexaccount'];

        $this->quoteMock->expects($this->any())->method('importData')->with($importData)->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('collectTotals')->willReturnSelf();

        $this->quoteManagementMock->expects($this->any())->method('submit')->willReturn($this->orderObject);

        $this->orderObject->expects($this->any())->method('getPayment')->willReturn($this->orderPaymentInterface);

        $this->orderPaymentInterface->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();

        $this->orderObject->expects($this->any())->method("getAllItems")->willReturn([$this->item]);

        $this->item->expects($this->any())->method("setBasePrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setPrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setBaseOriginalPrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setOriginalPrice")->willReturnSelf();
        $this->item->expects($this->any())->method("setBasePriceInclTax")->willReturnSelf();
        $this->item->expects($this->any())->method("setPriceInclTax")->willReturnSelf();
        $this->item->expects($this->any())->method("setDiscount")->willReturnSelf();
        $this->item->expects($this->any())->method("setRowTotal")->willReturnSelf();
        $this->item->expects($this->any())->method("setBaseRowTotal")->willReturnSelf();

        $this->orderObject->expects($this->any())->method("addStatusHistoryComment")->willReturnSelf();

        $this->helperData->createMissedOrders($this->quoteMock, $transactionId, $gtn);
    }

    public function getTransactionResponsewithPickup()
    {
        return array (
                  'error' => 0,
                  'msg' => 'Success',
                  'response' =>
                  array (
                    'transactionId' => '0dd47a1d-cc28-420b-b48b-99e0a00f7ad8',
                    'output' =>
                    array (
                      'checkout' =>
                      array (
                        'lineItems' =>
                        array (
                          0 =>
                          array (
                            'retailPrintOrderDetails' =>
                            array (
                              0 =>
                              array (
                                'productLines' =>
                                array (
                                  0 =>
                                  array (
                                    'instanceId' => 9,
                                    'productId' => 1559886500133,
                                    'unitQuantity' => 50,
                                    'unitOfMeasurement' => 'EACH',
                                    'productRetailPrice' => 30.99,
                                    'productDiscountAmount' => 10.85,
                                    'productLinePrice' => 20.14,
                                    'productLineDiscounts' =>
                                    array (
                                      0 =>
                                      array (
                                        'type' => 'ACCOUNT',
                                        'amount' => 10.85,
                                      ),
                                    ),
                                    'productLineDetails' =>
                                    array (
                                      0 =>
                                      array (
                                        'instanceId' => 10,
                                        'detailCode' => 51103,
                                        'hasTermsAndConditions' => 1,
                                        'description' => '50 Qk Pstcd 5.5x8.5',
                                        'priceRequired' => NULL,
                                        'priceOverridable' => NULL,
                                        'unitQuantity' => 1,
                                        'quantity' => 1,
                                        'detailPrice' => 20.14,
                                        'detailDiscountPrice' => 10.85,
                                        'detailUnitPrice' => '30.990000',
                                        'detailDiscountedUnitPrice' => 20.1435,
                                        'detailDiscounts' =>
                                        array (
                                          0 =>
                                          array (
                                            'type' => 'ACCOUNT',
                                            'amount' => 10.8465,
                                          ),
                                        ),
                                        'detailRetailPrice' => 30.99,
                                      ),
                                    ),
                                    'name' => 'Postcards',
                                    'userProductName' => 'MicrosoftTeams-image',
                                    'type' => 'PRINT_PRODUCT',
                                    'priceable' => 1,
                                    'orderAssociationRefId' => 2,
                                    'productUnitPrice' => 0.6198,
                                    'productDiscountedUnitPrice' => 0.4028,
                                  ),
                                  1 =>
                                  array (
                                    'instanceId' => 11,
                                    'productId' => 1456773326927,
                                    'unitQuantity' => 1,
                                    'unitOfMeasurement' => 'EACH',
                                    'productRetailPrice' => 0.68,
                                    'productDiscountAmount' => 0.24,
                                    'productLinePrice' => 0.44,
                                    'productLineDiscounts' =>
                                    array (
                                      0 =>
                                      array (
                                        'type' => 'ACCOUNT',
                                        'amount' => 0.24,
                                      ),
                                    ),
                                    'productLineDetails' =>
                                    array (
                                      0 =>
                                      array (
                                        'instanceId' => 12,
                                        'detailCode' => '0224',
                                        'hasTermsAndConditions' => 1,
                                        'description' => 'CLR 1S on 32# Wht',
                                        'priceRequired' => NULL,
                                        'priceOverridable' => NULL,
                                        'unitQuantity' => 1,
                                        'quantity' => 1,
                                        'detailPrice' => 0.44,
                                        'detailDiscountPrice' => 0.24,
                                        'detailUnitPrice' => '0.680000',
                                        'detailDiscountedUnitPrice' => '0.4420',
                                        'detailDiscounts' =>
                                        array (
                                          0 =>
                                          array (
                                            'type' => 'ACCOUNT',
                                            'amount' => '0.2380',
                                          ),
                                        ),
                                        'detailRetailPrice' => 0.68,
                                      ),
                                    ),
                                    'name' => 'Custom Multi Sheet',
                                    'userProductName' => 'MicrosoftTeams-image',
                                    'type' => 'PRINT_PRODUCT',
                                    'priceable' => 1,
                                    'orderAssociationRefId' => 2,
                                    'productUnitPrice' => '0.6800',
                                    'productDiscountedUnitPrice' => '0.4400',
                                  ),
                                  2 =>
                                  array (
                                    'instanceId' => 13,
                                    'productId' => 1559886500133,
                                    'unitQuantity' => 100,
                                    'unitOfMeasurement' => 'EACH',
                                    'productRetailPrice' => 43.99,
                                    'productDiscountAmount' => '15.40',
                                    'productLinePrice' => 28.59,
                                    'productLineDiscounts' =>
                                    array (
                                      0 =>
                                      array (
                                        'type' => 'ACCOUNT',
                                        'amount' => '15.40',
                                      ),
                                    ),
                                    'productLineDetails' =>
                                    array (
                                      0 =>
                                      array (
                                        'instanceId' => 14,
                                        'detailCode' => 51104,
                                        'hasTermsAndConditions' => 1,
                                        'description' => '100 Qk Pstcd 5.5x8.5',
                                        'priceRequired' => NULL,
                                        'priceOverridable' => NULL,
                                        'unitQuantity' => 1,
                                        'quantity' => 1,
                                        'detailPrice' => 28.59,
                                        'detailDiscountPrice' => '15.40',
                                        'detailUnitPrice' => '43.990000',
                                        'detailDiscountedUnitPrice' => 28.5935,
                                        'detailDiscounts' =>
                                        array (
                                          0 =>
                                          array (
                                            'type' => 'ACCOUNT',
                                            'amount' => 15.3965,
                                          ),
                                        ),
                                        'detailRetailPrice' => 43.99,
                                      ),
                                    ),
                                    'name' => 'Postcards',
                                    'userProductName' => 'Screenshot from 2023-08-09 21-17-09',
                                    'type' => 'PRINT_PRODUCT',
                                    'priceable' => 1,
                                    'orderAssociationRefId' => 2,
                                    'productUnitPrice' => 0.4399,
                                    'productDiscountedUnitPrice' => 0.2859,
                                  ),
                                ),
                                'deliveryLines' =>
                                array (
                                  0 =>
                                  array (
                                    'deliveryLineId' => 3581,
                                    'recipientReference' => 3581,
                                    'estimatedDeliveryLocalTime' => '2023-08-10T12:00:00',
                                    'deliveryLineType' => 'PICKUP',
                                    'recipientContact' =>
                                    array (
                                      'personName' =>
                                      array (
                                        'firstName' => 'Swapnil',
                                        'lastName' => 'Shinde',
                                      ),
                                      'company' =>
                                      array (
                                        'name' => 'FXO',
                                      ),
                                      'emailDetail' =>
                                      array (
                                        'emailAddress' => 'swapnil1.shinde@infogain.com',
                                      ),
                                      'phoneNumberDetails' =>
                                      array (
                                        0 =>
                                        array (
                                          'phoneNumber' =>
                                          array (
                                            'number' => 9960082929,
                                          ),
                                          'usage' => 'PRIMARY',
                                        ),
                                      ),
                                    ),
                                    'pickupDetails' =>
                                    array (
                                      'locationName' => 4474,
                                      'address' =>
                                      array (
                                        'streetLines' =>
                                        array (
                                          0 => '367 Seventh Ave',
                                        ),
                                        'city' => 'New York',
                                        'stateOrProvinceCode' => 'NY',
                                        'postalCode' => 10001,
                                        'countryCode' => 'US',
                                      ),
                                      'requestedPickupLocalTime' => '2023-08-10T12:00:00',
                                    ),
                                    'productAssociation' =>
                                    array (
                                      0 =>
                                      array (
                                        'productRef' => 9,
                                        'quantity' => '50.0',
                                      ),
                                      1 =>
                                      array (
                                        'productRef' => 11,
                                        'quantity' => '1.0',
                                      ),
                                      2 =>
                                      array (
                                        'productRef' => 13,
                                        'quantity' => '100.0',
                                      ),
                                    ),
                                    'orderAssociationRefId' => 2,
                                  ),
                                ),
                                'orderTotalDiscountAmount' => 26.49,
                                'orderGrossAmount' => 75.66,
                                'orderNonTaxableAmount' => '0.00',
                                'orderTaxExemptableAmount' => '0.00',
                                'orderNetAmount' => 49.17,
                                'orderTaxableAmount' => 49.17,
                                'orderTaxAmount' => 4.36,
                                'orderTotalAmount' => 53.53,
                                'origin' =>
                                array (
                                  'orderNumber' => 2010215860184796,
                                  'orderClient' => 'MAGENTO',
                                  'apiCustomer' => 'POD2',
                                  'orderReferences' =>
                                  array (
                                    0 =>
                                    array (
                                      'name' => 'MAGENTO',
                                      'value' => 2010215860184796,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        'contact' =>
                        array (
                          'personName' =>
                          array (
                            'firstName' => 'Swapnil',
                            'lastName' => 'Shinde',
                          ),
                          'company' =>
                          array (
                            'name' => 'FXO',
                          ),
                          'emailDetail' =>
                          array (
                            'emailAddress' => 'swapnil1.shinde@infogain.com',
                          ),
                          'phoneNumberDetails' =>
                          array (
                            0 =>
                            array (
                              'phoneNumber' =>
                              array (
                                'number' => 9960082929,
                              ),
                              'usage' => 'PRIMARY',
                            ),
                          ),
                        ),
                        'tenders' =>
                        array (
                          0 =>
                          array (
                            'id' => 1,
                            'paymentType' => 'ACCOUNT',
                            'tenderedAmount' => 53.53,
                            'account' =>
                            array (
                              'accountNumber' => 653243324,
                              'maskedAccountNumber' => '*3324',
                              'masterAccountNumber' => '0653243324',
                              'accountType' => 'FXK',
                            ),
                            'currency' => 'USD',
                          ),
                        ),
                        'transactionTotals' =>
                        array (
                          'currency' => 'USD',
                          'grossAmount' => 75.66,
                          'totalDiscountAmount' => 26.49,
                          'netAmount' => 49.17,
                          'taxAmount' => 4.36,
                          'totalAmount' => 53.53,
                        ),
                      ),
                    ),
                  ),
                  'rateQuoteResponse' =>
                  array (
                    'output' =>
                    array (
                      'rateQuote' =>
                      array (
                        'rateQuoteDetails' =>
                        array (
                          0 =>
                          array (
                            'estimatedVsActual' => 'ACTUAL',
                          ),
                        ),
                      ),
                    ),
                  ),
                );
    }

    /**
     * getTransactionResponseArray
     *
     */
    public function getTransactionResponsewithShipping()
    {
        return array (
          'error' => 0,
          'msg' => 'Success',
          'response' =>
          array (
            'transactionId' => '310283b0-f06c-4f3a-aed2-6d2bc901977b',
            'output' =>
            array (
              'checkout' =>
              array (
                'lineItems' =>
                array (
                  0 =>
                  array (
                    'retailPrintOrderDetails' =>
                    array (
                      0 =>
                      array (
                        'productLines' =>
                        array (
                          0 =>
                          array (
                            'instanceId' => 10,
                            'productId' => 1559886500133,
                            'unitQuantity' => 50,
                            'unitOfMeasurement' => 'EACH',
                            'productRetailPrice' => 30.99,
                            'productDiscountAmount' => 10.85,
                            'productLinePrice' => 20.14,
                            'productLineDiscounts' =>
                            array (
                              0 =>
                              array (
                                'type' => 'ACCOUNT',
                                'amount' => 10.85,
                              ),
                            ),
                            'productLineDetails' =>
                            array (
                              0 =>
                              array (
                                'instanceId' => 11,
                                'detailCode' => 51103,
                                'hasTermsAndConditions' => 1,
                                'description' => '50 Qk Pstcd 5.5x8.5',
                                'priceRequired' => NULL,
                                'priceOverridable' => NULL,
                                'unitQuantity' => 1,
                                'quantity' => 1,
                                'detailPrice' => 20.14,
                                'detailDiscountPrice' => 10.85,
                                'detailUnitPrice' => '30.990000',
                                'detailDiscountedUnitPrice' => 20.1435,
                                'detailDiscounts' =>
                                array (
                                  0 =>
                                  array (
                                    'type' => 'ACCOUNT',
                                    'amount' => 10.8465,
                                  ),
                                ),
                                'detailRetailPrice' => 30.99,
                              ),
                            ),
                            'name' => 'Postcards',
                            'userProductName' => 'Screenshot from 2023-07-18 09-51-53',
                            'type' => 'PRINT_PRODUCT',
                            'priceable' => 1,
                            'orderAssociationRefId' => 3,
                            'productUnitPrice' => 0.6198,
                            'productDiscountedUnitPrice' => 0.4028,
                          ),
                          1 =>
                          array (
                            'instanceId' => 12,
                            'productId' => 1456773326927,
                            'unitQuantity' => 15,
                            'unitOfMeasurement' => 'EACH',
                            'productRetailPrice' => '20.40',
                            'productDiscountAmount' => 7.14,
                            'productLinePrice' => 13.26,
                            'productLineDiscounts' =>
                            array (
                              0 =>
                              array (
                                'type' => 'ACCOUNT',
                                'amount' => 7.14,
                              ),
                            ),
                            'productLineDetails' =>
                            array (
                              0 =>
                              array (
                                'instanceId' => 13,
                                'detailCode' => '0224',
                                'hasTermsAndConditions' => 1,
                                'description' => 'CLR 1S on 32# Wht',
                                'priceRequired' => NULL,
                                'priceOverridable' => NULL,
                                'unitQuantity' => 30,
                                'quantity' => 30,
                                'detailPrice' => 13.26,
                                'detailDiscountPrice' => 7.14,
                                'detailUnitPrice' => '0.680000',
                                'detailDiscountedUnitPrice' => '0.4420',
                                'detailDiscounts' =>
                                array (
                                  0 =>
                                  array (
                                    'type' => 'ACCOUNT',
                                    'amount' => '7.1400',
                                  ),
                                ),
                                'detailRetailPrice' => '20.40',
                              ),
                            ),
                            'name' => 'Custom Multi Sheet',
                            'userProductName' => 'Screenshot from 2023-07-18 09-51-35',
                            'type' => 'PRINT_PRODUCT',
                            'priceable' => 1,
                            'orderAssociationRefId' => 3,
                            'productUnitPrice' => '1.3600',
                            'productDiscountedUnitPrice' => '0.8840',
                          ),
                        ),
                        'deliveryLines' =>
                        array (
                          0 =>
                          array (
                            'deliveryLineId' => 3903,
                            'recipientReference' => 3903,
                            'estimatedDeliveryLocalTime' => '2023-08-10T12:00:00',
                            'estimatedShipDate' => '2023-08-09',
                            'deliveryLinePrice' => 44.98,
                            'deliveryRetailPrice' => 44.98,
                            'deliveryLineType' => 'SHIPPING',
                            'deliveryDiscountAmount' => '0.0',
                            'recipientContact' =>
                            array (
                              'personName' =>
                              array (
                                'firstName' => 'swapnil',
                                'lastName' => 'shinde',
                              ),
                              'company' =>
                              array (
                                'name' => 'FXO',
                              ),
                              'emailDetail' =>
                              array (
                                'emailAddress' => 'swapnil1.shinde@infogain.com',
                              ),
                              'phoneNumberDetails' =>
                              array (
                                0 =>
                                array (
                                  'phoneNumber' =>
                                  array (
                                    'number' => 3453535333,
                                  ),
                                  'usage' => 'PRIMARY',
                                ),
                              ),
                            ),
                            'shipmentDetails' =>
                            array (
                              'address' =>
                              array (
                                'streetLines' =>
                                array (
                                  0 => 'Plano',
                                ),
                                'city' => 'Plano',
                                'stateOrProvinceCode' => 'TX',
                                'postalCode' => 75024,
                                'countryCode' => 'US',
                              ),
                              'serviceType' => 'PRIORITY_OVERNIGHT',
                            ),
                            'productAssociation' =>
                            array (
                              0 =>
                              array (
                                'productRef' => 10,
                                'quantity' => '50.0',
                              ),
                              1 =>
                              array (
                                'productRef' => 12,
                                'quantity' => '15.0',
                              ),
                            ),
                            'deliveryLineDetails' =>
                            array (
                              0 =>
                              array (
                                'instanceId' => 20,
                                'detailCode' => 4543,
                                'unitQuantity' => 1,
                              ),
                            ),
                            'orderAssociationRefId' => 3,
                          ),
                        ),
                        'orderTotalDiscountAmount' => 17.99,
                        'orderGrossAmount' => 96.37,
                        'orderNonTaxableAmount' => '0.00',
                        'orderTaxExemptableAmount' => 44.98,
                        'orderNetAmount' => 78.38,
                        'orderTaxableAmount' => '33.40',
                        'orderTaxAmount' => 2.76,
                        'orderTotalAmount' => 81.14,
                        'origin' =>
                        array (
                          'orderNumber' => 2010250333434900,
                          'orderClient' => 'MAGENTO',
                          'apiCustomer' => 'POD2',
                          'orderReferences' =>
                          array (
                            0 =>
                            array (
                              'name' => 'MAGENTO',
                              'value' => 2010250333434900,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
                'tenders' =>
                array (
                  0 =>
                  array (
                    'id' => 1,
                    'paymentType' => 'ACCOUNT',
                    'tenderedAmount' => 44.98,
                    'account' =>
                    array (
                      'accountNumber' => 397466647,
                      'maskedAccountNumber' => '*6647',
                      'masterAccountNumber' => 397466647,
                      'accountHolder' => 'SENDER',
                      'accountType' => 'FX',
                    ),
                    'currency' => 'USD',
                  ),
                  1 =>
                  array (
                    'id' => 2,
                    'paymentType' => 'CREDIT_CARD',
                    'tenderedAmount' => 36.16,
                    'creditCard' =>
                    array (
                      'type' => 'MASTERCARD',
                      'maskedAccountNumber' => '555304xxxxxx4105',
                      'accountLast4Digits' => 'xxxxxxxxxxxx4105',
                    ),
                    'currency' => 'USD',
                  ),
                ),
                'transactionTotals' =>
                array (
                  'currency' => 'USD',
                  'grossAmount' => 96.37,
                  'totalDiscountAmount' => 17.99,
                  'netAmount' => 78.38,
                  'taxAmount' => 2.76,
                  'totalAmount' => 81.14,
                ),
              ),
            ),
          )
        );

    }
}
