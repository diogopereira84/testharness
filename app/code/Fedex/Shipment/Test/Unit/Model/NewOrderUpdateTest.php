<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;
use Fedex\Shipment\Helper\ShipmentEmail;
use Fedex\Shipment\Helper\Data;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\Shipment\Model\NewOrderUpdate;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\App\State;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderAPI;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Shipment\Helper\StatusOption as ShipmentHelper;
use Fedex\MarketplaceCheckout\Model\CancelOrder as MarketPlaceOrder;

/**
 * Test class for Fedex\Shipment\Model\NewOrderUpdate
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class NewOrderUpdateTest extends TestCase
{
    protected $submitOrderModelAPI;
    protected $toggleConfig;
    protected $_newOrderUpdateObj;
    public const EXCEPTION_MESSAGE = 'Exception Message';
    public const TRANSACTION_ID = "12345678";
    public const PICKUP_ALLOWED_UNITLL_DATE = "2018-01-25T09:50:35Z";

    /** @var ObjectManager|MockObject */
    protected $objectManager;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var Order|MockObject */
    protected $order;

    /** @var PublisherInterface|MockObject */
    protected $publisher;

    /** @var Data|MockObject */
    protected $helperMock;

    /** @var ShipmentEmail|MockObject */
    protected $shipmentEmailMock;

    /** @var LoggerInterface|MockObject */
    protected $loggerMock;

    /** @var State|MockObject */
    protected $stateMock;

    /** @var Shipment|MockObject */
    protected $shipment;

    /** @var ShipmentHelper|MockObject */
    protected $shipmentHelper;

    /** @var MarketPlaceOrder|MockObject */
    protected $marketPlaceOrder;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publisher = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMock();

        $this->shipmentEmailMock = $this->getMockBuilder(ShipmentEmail::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendEmail'])
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentIdByFxoShipmentId','createOrderbyGTN', 'getShipmentById'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->submitOrderModelAPI = $this->getMockBuilder(SubmitOrderAPI::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTransactionApiResponseData', 'updateOrderWithNewStatus', 'finalizeOrder'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->setMethods(['emulateAreaCode'])
            ->getMock();

        $this->shipment = $this->getMockBuilder(Shipment::class)
            ->addMethods(['getMiraklShippingReference'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentHelper = $this->getMockBuilder(ShipmentHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketPlaceOrder = $this->getMockBuilder(MarketPlaceOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_newOrderUpdateObj = $this->objectManager->getObject(
            NewOrderUpdate::class,
            [
                'request' => $this->request,
                'order' => $this->order,
                'publisher' => $this->publisher,
                'logger' => $this->loggerMock,
                'shipmentEmail' => $this->shipmentEmailMock,
                'helper' => $this->helperMock,
                'submitOrderModelAPI' => $this->submitOrderModelAPI,
                'toggleConfig' => $this->toggleConfig,
                'state' => $this->stateMock,
                'shipmentHelper' => $this->shipmentHelper,
                'marketplaceOrder' => $this->marketPlaceOrder
            ]
        );
    }

    /**
     * Test testUpdateOrderStatus
     */
    public function testUpdateOrderStatus()
    {
        $id = "101";
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "IN_PROGRESS";
        $orderStatusUpdateRequest = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "orderStatus" => "RECEIVED",
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
            "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->order);
        $this->order->expects($this->any())->method('getId')->willReturn($id);
        $this->publisher->expects($this->any())->method('publish')
        ->willReturn('updateOrderInformation', $orderStatusUpdateRequestString);
        $status = $this->_newOrderUpdateObj->updateOrderStatus($id);
        $exceptedResponse = ['message' => 'success'];
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderStatus
     */
    public function testUpdateOrderStatuswithCreateOrder()
    {
        $id = "101";
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "IN_PROGRESS";
        $orderStatusUpdateRequest = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
            "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->order);
        $this->helperMock->expects($this->any())->method('createOrderbyGTN')->willReturn($id);
        $this->order->expects($this->any())->method('getId')->willReturn(null);
        $this->publisher->expects($this->any())->method('publish')
        ->willReturn('updateOrderInformation', $orderStatusUpdateRequestString);
        $status = $this->_newOrderUpdateObj->updateOrderStatus($id);
        $exceptedResponse = ['message' => 'Order not exist.'];
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderStatusWithOrderId
     */
    public function testUpdateOrderStatusWithOrderId()
    {
        $id = null;
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "canceled";
        $orderStatusUpdateRequest = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
            "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);
        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->order);
        $this->order->expects($this->any())->method('getId')->willReturn($id);
        $this->publisher->expects($this->any())->method('publish')
        ->willReturn('updateOrderInformation', $orderStatusUpdateRequestString);
        $status = $this->_newOrderUpdateObj->updateOrderStatus($id);
        $exceptedResponse = ['message' => 'Order not exist.'];
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderStatusWithCanceled
     */
    public function testUpdateOrderStatusWithCanceled()
    {
        $id = "101";
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "IN_PROGRESS";
        $orderStatusUpdateRequest = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
            "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);
        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->order);
        $this->order->expects($this->any())->method('getId')->willReturn($id);
        $this->order->expects($this->any())->method('getState')->willReturn('canceled');
        $this->publisher->expects($this->any())->method('publish')
        ->willReturn('updateOrderInformation', $orderStatusUpdateRequestString);
        $status = $this->_newOrderUpdateObj->updateOrderStatus($id);
        $exceptedResponse = ['message' => 'Order is already cancelled. Not able to change status'];
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderStatusWithException
     */
    public function testUpdateOrderStatusWithException()
    {
        $id = "101";
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "IN_PROGRESS";
        $orderStatusUpdateRequest = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
            "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);
        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $phrase = new Phrase(__(self::EXCEPTION_MESSAGE));
        $exception = new LocalizedException($phrase);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willThrowException($exception);
        $exceptedResponse = ['code' => '400', 'message' => self::EXCEPTION_MESSAGE];
        $status = $this->_newOrderUpdateObj->updateOrderStatus($id);
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test case for sentStatusEmail function
     */
    public function testSentStatusEmail()
    {
        $orderId = "104";
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];
        $status = "IN_PROGRESS";
        $requestSchema = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string"
                ],
            ],
            "order_increment_id" => $orderId
        ];
        $this->helperMock->expects($this->any())->method('getShipmentIdByFxoShipmentId')->willReturn('104');
        $this->shipmentEmailMock->expects($this->any())->method('sendEmail')->willReturnSelf();
        $result = $this->_newOrderUpdateObj->sentStatusEmail($orderId, $requestSchema);
        $expectedResult = ['message' => 'success'];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test case for sentStatusEmail with exception
     */
    public function testSentStatusEmailWithException()
    {
        $orderId = "104";
        $orderReference = [
            'key' => "1",
            'value' => 'value'
        ];
        $status = "IN_PROGRESS";
        $requestSchema = [
            "fxoWorkOrderNumber" => "367",
            "customerOrderNumber" => "333",
            "orderCreatedBySystem" => "121",
            "transactionId" => self::TRANSACTION_ID,
            "orderReferences" => [$orderReference],
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => $status,
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string"
                ],
            ],
            "order_increment_id" => $orderId
        ];
        $phrase = new Phrase(__(self::EXCEPTION_MESSAGE));
        $exception = new LocalizedException($phrase);
        $this->helperMock->expects($this->any())->method('getShipmentIdByFxoShipmentId')
        ->willThrowException($exception);
        $exceptedResult = ['code' => '400', 'message' => self::EXCEPTION_MESSAGE];
        $result = $this->_newOrderUpdateObj->sentStatusEmail($orderId, $requestSchema);

        $this->assertEquals($exceptedResult, $result);
    }

    /**
     * Test case for orderUpdateFromOMSRequest
     */
    public function testOrderUpdateFromOMSRequest()
    {
        $requestData = array(
            'orderNumber' => '12234',
            'addressInformation' => array(
                'pickup_location_state' => 'pickup_location_state',
                'pickup_location_country' => 'pickup_location_country',
                'pickup_location_street' => 'pickup_location_street',
                'pickup_location_zipcode' => 'pickup_location_zipcode',
                'pickup_location_city' => 'pickup_location_city',
                'shipping_detail' => [
                    'carrier_code' => 'fedexshipping',
                    'method_code' => 'PICKUP',
                    'carrier_title' => 'Ground US',
                    'method_title' => '1 Business Day(s)',
                    'amount' => 1,
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                ],
            ),
            'orderStatus' => 'RECEIVED',
            'contactInformation' => array(
                'isAlternatePerson' => true,
                'contact_fname' => 'Test',
                'contact_lname' => 'Test',
                'contact_email' => 'Test',
                'contact_number' => 'Test',
                'alternate_fname' => 'Test',
                'alternate_lname' => 'Test',
                'alternate_email' => 'Test',
                'alternate_number' => 'Test',
            ),
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => "cancelled",
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
        );
        $transactionData = [
            'errors' => [],
            'output' => [
                'rate' => [
                    'test' => 'Ayush'
                ]
            ]
        ];
        $this->shipment->expects($this->any())->method('getMiraklShippingReference')->willReturn('111');
        $this->helperMock->expects($this->any())->method('getShipmentById')->willReturn($this->shipment);
        $this->order->expects($this->any())->method('getId')->willReturn(12234);
        $this->stateMock->expects($this->any())->method('emulateAreaCode')->willReturnSelf();
        $this->order->expects($this->any())->method('hasInvoices')->willReturn(false);
        $this->order->expects($this->any())->method('hasShipments')->willReturn(false);
        $this->order->expects($this->any())->method('getStatus')->willReturn('pending');
        $this->submitOrderModelAPI->expects($this->any())->method('getTransactionApiResponseData')
        ->willReturn($transactionData);
        $this->submitOrderModelAPI->expects($this->any())->method('updateOrderWithNewStatus')
        ->willReturn('new');
        $this->submitOrderModelAPI->expects($this->any())->method('finalizeOrder')
        ->willReturnSelf();
        $this->assertNotNull($this->_newOrderUpdateObj->orderUpdateFromOMSRequest($this->order, $requestData));
    }

    /**
     * Test case for orderUpdateFromOMSRequestEnhancement
     */
    public function testOrderUpdateFromOMSRequestEnhancement()
    {
        $requestData = array(
            'orderNumber' => '12234',
            'addressInformation' => array(
                'pickup_location_state' => 'pickup_location_state',
                'pickup_location_country' => 'pickup_location_country',
                'pickup_location_street' => 'pickup_location_street',
                'pickup_location_zipcode' => 'pickup_location_zipcode',
                'pickup_location_city' => 'pickup_location_city',
                'shipping_detail' => [
                    'carrier_code' => 'fedexshipping',
                    'method_code' => 'PICKUP',
                    'carrier_title' => 'Ground US',
                    'method_title' => '1 Business Day(s)',
                    'amount' => 1,
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                ],
            ),
            'orderStatus' => 'RECEIVED',
            'contactInformation' => array(
                'isAlternatePerson' => true,
                'contact_fname' => 'Test',
                'contact_lname' => 'Test',
                'contact_email' => 'Test',
                'contact_number' => 'Test',
                'alternate_fname' => 'Test',
                'alternate_lname' => 'Test',
                'alternate_email' => 'Test',
                'alternate_number' => 'Test',
            ),
            "shipmentItems" => [
                0 => [
                    "shipmentId" => "111",
                    "pickupAllowedUntilDate" => self::PICKUP_ALLOWED_UNITLL_DATE,
                    "status" => "cancelled",
                    "trackingNumber" => "111",
                    "courier" => "fedex_office",
                    "exceptionReason" => "string",
                ],
            ],
        );
        $transactionData = [
            'errors' => [],
            'output' => [
                'rate' => [
                    'test' => 'Ayush'
                ]
            ]
        ];
        $this->shipment->expects($this->any())->method('getMiraklShippingReference')->willReturn('111');
        $this->helperMock->expects($this->any())->method('getShipmentById')->willReturn($this->shipment);
        $this->order->expects($this->any())->method('getId')->willReturn(12234);
        $this->stateMock->expects($this->any())->method('emulateAreaCode')->willReturnSelf();
        $this->order->expects($this->any())->method('hasInvoices')->willReturn(false);
        $this->order->expects($this->any())->method('hasShipments')->willReturn(false);
        $this->order->expects($this->any())->method('getStatus')->willReturn('pending');
        $this->submitOrderModelAPI->expects($this->any())->method('getTransactionApiResponseData')
            ->willReturn($transactionData);
        $this->submitOrderModelAPI->expects($this->any())->method('updateOrderWithNewStatus')
            ->willReturn('new');
        $this->submitOrderModelAPI->expects($this->any())->method('finalizeOrder')
            ->willReturnSelf();
        $this->assertNotNull($this->_newOrderUpdateObj->orderUpdateFromOMSRequestEnhancement($this->order, $requestData));
    }

    /**
     * Test case for orderUpdateFromOMSRequest if received status is not received.
     */
    public function testOrderUpdateFromOMSRequestWhenStatusIsNotReceived()
    {
        $requestData = [
            'orderNumber' => '12234',
            'addressInformation' => [
                'pickup_location_state' => 'pickup_location_state',
                'pickup_location_country' => 'pickup_location_country',
                'pickup_location_street' => 'pickup_location_street',
                'pickup_location_zipcode' => 'pickup_location_zipcode',
                'pickup_location_city' => 'pickup_location_city',
                'shipping_detail' => [
                    'carrier_code' => 'fedexshipping',
                    'method_code' => 'PICKUP',
                    'carrier_title' => 'Ground US',
                    'method_title' => '1 Business Day(s)',
                    'amount' => 1,
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                ],
            ],
            'orderStatus' => 'APPROVED',
            'contactInformation' => [
                'isAlternatePerson' => true,
                'contact_fname' => 'Test',
                'contact_lname' => 'Test',
                'contact_email' => 'Test',
                'contact_number' => 'Test',
                'alternate_fname' => 'Test',
                'alternate_lname' => 'Test',
                'alternate_email' => 'Test',
                'alternate_number' => 'Test',
            ],
            'shipmentItems' => [
                [
                    'shipmentId' => '111',
                    'pickupAllowedUntilDate' => self::PICKUP_ALLOWED_UNITLL_DATE,
                    'status' => 'cancelled',
                    'trackingNumber' => '111',
                    'courier' => 'fedex_office',
                    'exceptionReason' => 'string',
                ],
            ],
        ];

        $transactionData = [
            'errors' => [],
            'output' => [
                'rate' => [
                    'test' => 'Ayush',
                ],
            ],
        ];

        $this->shipment->expects($this->any())
            ->method('getMiraklShippingReference')
            ->willReturn('111');

        $this->helperMock->expects($this->any())
            ->method('getShipmentById')
            ->willReturn($this->shipment);

        $this->order->expects($this->any())
            ->method('getId')
            ->willReturn(12234);

        $this->stateMock->expects($this->any())
            ->method('emulateAreaCode')
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('hasInvoices')
            ->willReturn(false);

        $this->order->expects($this->any())
            ->method('hasShipments')
            ->willReturn(false);

        $this->order->expects($this->any())
            ->method('getStatus')
            ->willReturn('pending');

        $this->submitOrderModelAPI->expects($this->any())
            ->method('getTransactionApiResponseData')
            ->willReturn($transactionData);

        $this->submitOrderModelAPI->expects($this->any())
            ->method('updateOrderWithNewStatus')
            ->willReturn('new');

        $this->submitOrderModelAPI->expects($this->never())
            ->method('finalizeOrder');

        $this->assertNotNull(
            $this->_newOrderUpdateObj->orderUpdateFromOMSRequest($this->order, $requestData)
        );
    }
}
