<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Shipment\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Helper\Data as Helper;
use Fedex\Shipment\Model\NewOrderUpdate;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Fedex\Shipment\Helper\ShipmentEmail;
use Psr\Log\LoggerInterface;
use Mirakl\Connector\Helper\Order as MiraklOrderHelper;
use Fedex\MarketplaceCheckout\Model\CancelOrder as MarketPlaceOrder;
use Magento\Sales\Api\OrderItemRepositoryInterface;

/**
 * Test class for Fedex\Shipment\Model\NewOrderUpdate
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class UpdateOrderInformationTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $handleMktCheckout;
    public const ID = "8000000566";
    public const TRANSACTION_ID = "12345678";
    public const PICKUP_TIME = "2018-01-25T09:50:35Z";

    /** @var Data |MockObject */
    protected $helper;

    /** @var Collection |MockObject */
    protected $statusCollection;

    /** @var RequestInterface |MockObject */
    protected $request;

    /** @var Order |MockObject */
    protected $orderObject;

    /** @var Order |MockObject */
    protected $order;

    /** @var NewOrderUpdate |MockObject */
    protected $helperData;

    /** @var DataObject |MockObject */
    protected $statusMock;

    /** @var ShipmentInterface |MockObject */
    protected $shipmentMock;

    /** @var ShipmentEmail |MockObject */
    protected $shipmentEmail;

    /** @var LoggerInterface |MockObject */
    protected $logger;

    /** @var MiraklOrderHelper |MockObject */
    protected $miraklOrderHelper;

    /** @var OrderItemRepositoryInterface |MockObject */
    protected $orderItemRepository;

    /** @var UpdateOrderInformation |MockObject */
    protected $_updateOrderInformationObj;

    /** @var MarketPlaceOrder |MockObject */
    protected $marketplaceOrder;

    /** @var OrderItem |MockObject */
    protected $orderItem;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->helper = $this->getMockBuilder(Helper::class)
                ->disableOriginalConstructor()
                ->setMethods(['insertOrderReference','insertFxoWorkOrderNumber','getShipmentIdByFxoShipmentId',
                'getShipmentById','updateShipmentStatus','getShipmentStatusByValue','getOrderItemIdByShipmentId',
                'detetermineOrderStatus','generateRefund','insertShipmentWorkOrderNumber','updateStatusOfOrder', 'isMixedOrder', 'isAllItemsCancelled'])
                ->getMock();

        $this->helper->expects($this->any())->method('isMixedOrder')->willReturn(true);
        $this->helper->expects($this->any())->method('isAllItemsCancelled')->willReturn(true);

        $this->statusCollection = $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->setMethods(['toOptionArray', 'joinStates', 'getData'])
                ->getMock();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['getContent'])
                ->getMockForAbstractClass();

        $this->orderObject = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
                ->setMethods(['load', 'getIncrementId', 'getId', 'setExtOrderId', 'save', 'getAllVisibleItems', 'getAllItems'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->statusMock = $this->getMockBuilder(DataObject::class)
                ->disableOriginalConstructor()
                ->setMethods(['getData'])
                ->getMock();

        $this->shipmentMock = $this->getMockBuilder(\Magento\Sales\Api\Data\ShipmentInterface::class)
                ->setMethods(['getId', 'addTrack', 'setPickupAllowedUntilDate', 'setFxoWorkOrderNumber', 'save', 'getMiraklShippingReference'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->shipmentEmail = $this->getMockBuilder(\Fedex\Shipment\Helper\ShipmentEmail::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->miraklOrderHelper = $this->getMockBuilder(MiraklOrderHelper::class)
            ->setMethods(['isMiraklOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceOrder = $this->getMockBuilder(MarketPlaceOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handleMktCheckout = $this->getMockBuilder(HandleMktCheckout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemRepository = $this->getMockBuilder(OrderItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItem = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Item::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getQtyOrdered',
                'getQtyShipped'
            ])->getMock();

        $this->_updateOrderInformationObj = new \Fedex\Shipment\Model\UpdateOrderInformation(
            $this->helper,
            $this->order,
            $this->shipmentEmail,
            $this->logger,
            $this->miraklOrderHelper,
            $this->orderItemRepository,
            $this->handleMktCheckout
        );
    }

    /**
     * Test testUpdateOrderInformationWithInProcess method with IN_PROGRESS status.
     */
    public function testUpdateOrderInformationWithInProcess()
    {
        $id = self::ID;
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "in_process";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string",
                    ],
                ],
                "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status,
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())
        ->method('toOptionArray')->willReturn([['value' => 'value', 'label' => 'label']]);
        $this->statusCollection->expects($this->any())->method('joinStates')
        ->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn('cancelled');

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithCancelled method with cancelled status.
     */
    public function testUpdateOrderInformationWithCancelled()
    {
        $id = self::ID;
        $orderReference = [
            'key' => "1",
            'value' => 'value',
        ];

        $status = "cancelled";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string",
                    ],
                ],
                "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status,
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);
        $this->statusCollection->expects($this->any())->method('joinStates')
        ->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);
        $this->marketplaceOrder->expects($this->any())->method('cancelOrder')->willReturnSelf();
        $this->shipmentMock->expects($this->any())->method('setFxoWorkOrderNumber')->willReturn("123456789");
        $this->shipmentMock->expects($this->any())->method('save')->willReturnSelf();
        $this->shipmentMock->expects($this->any())->method('getMiraklShippingReference')->willReturn('reference');
        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithComplete method with complete status.
     */
    public function testUpdateOrderInformationWithComplete()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "complete";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->orderItem->expects($this->once())->method('getQtyOrdered')->willReturn(2);
        $this->orderItem->expects($this->once())->method('getQtyShipped')->willReturn(2);
        $this->orderObject->expects($this->any())->method('getAllItems')->willReturn([$this->orderItem]);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);
        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithCanceled method with canceled status.
     */
    public function testUpdateOrderInformationWithCanceled()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value',
        ];

        $status = "canceled";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);
        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithConfirmed method with confirmed status.
     */
    public function testUpdateOrderInformationWithConfirmed()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value',
        ];

        $status = "confirmed";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')
        ->will($this->returnValue($statuses));

        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithReadyforpickup method with ready_for_pickup status.
     */
    public function testUpdateOrderInformationWithReadyforpickup()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "ready_for_pickup";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );


        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);

        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);



        $this->miraklOrderHelper->expects($this->any())->method('isMiraklOrder')->with($this->orderObject)->willReturn(false);


        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithShipped method with shipped status.
     */
    public function testUpdateOrderInformationWithShipped()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "shipped";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id,
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithDelivered method with delivered status.
     */
    public function testUpdateOrderInformationWithDelivered()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "delivered";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ]
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithInProgress method with in_progress status.
     */
    public function testUpdateOrderInformationWithInProgress()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value',
        ];

        $status = "in_progress";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "IN_PROGRESS",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn($status);

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testtrackingnumbermiss method with in_process status.
     */
    public function testtrackingnumbermiss()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "in_process";
        $orderStatusUpdateRequest =
               [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "status" => "IN_PROGRESS",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
        ];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('detetermineOrderStatus')->willReturn('cancelled');

        $this->orderObject->expects($this->any())->method('setExtOrderId')->willReturn("123456789");
        $this->orderObject->expects($this->any())->method('save')->willReturnSelf();

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }
    /**
     * Test testUpdateOrderStatusWithCanceled method with canceled status.
     */
    public function testUpdateOrderStatusWithCanceled()
    {
        $id = self::ID;
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "canceled";
        $orderStatusUpdateRequest = [
            [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "shipmentId" => "111",
                        "pickupAllowedUntilDate" => self::PICKUP_TIME,
                        "status" => "CANCELED",
                        "trackingNumber" => "111",
                        "courier" => "fedex_office",
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
            ]];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationEmptyShipmentItems with empty SHIPPED status.
     */
    public function testUpdateOrderInformationEmptyShipmentItems()
    {
        $id = "1";
        $orderReference = [
            'key' => '1',
            'value' => 'value'
        ];

        $status = "SHIPPED";
        $orderStatusUpdateRequest = [
            [
                "fxoWorkOrderNumber" => "367",
                "customerOrderNumber" => "333",
                "orderCreatedBySystem" => "121",
                "transactionId" => self::TRANSACTION_ID,
                "orderReferences" => [$orderReference],
                "shipmentItems" => [
                    0 => [
                        "exceptionReason" => "string"
                    ],
                ],
                "order_increment_id" => $id
            ]];

        $orderStatusUpdateRequestString = json_encode($orderStatusUpdateRequest);

        $statuses = new DataObject(
            [
                [
                    'key' => '1',
                    'status' => $status,
                    'state' => $status
                ],
            ]
        );

        $this->request->expects($this->any())->method('getContent')->willReturn($orderStatusUpdateRequestString);
        $this->order->expects($this->any())->method('loadByIncrementId')->with($id)->willReturn($this->orderObject);
        $this->orderObject->expects($this->any())->method('getId')->willReturn($id);
        $this->statusCollection->expects($this->any())->method('toOptionArray')
        ->willReturn([['value' => 'value', 'label' => 'label']]);

        $this->statusCollection->expects($this->any())->method('joinStates')->will($this->returnValue($statuses));
        $this->statusCollection->expects($this->any())->method('getData')
        ->will($this->returnValue($this->statusMock));

        $this->helper->expects($this->any())->method('getShipmentById')->willReturn($this->shipmentMock);
        $this->shipmentMock->expects($this->any())->method('getShipmentStatus')->willReturn($status);
        $this->helper->expects($this->any())->method('getShipmentStatusByValue')->willReturn($status);
        $responseMessage = ['code' => '200', 'message' => 'success'];
        $this->helper->expects($this->any())->method('updateStatusOfOrder')->willReturn($responseMessage);
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = true;
        $this->assertEquals($exceptedResponse, $status);
    }

    /**
     * Test testUpdateOrderInformationWithException with exception handling.
     */
    public function testUpdateOrderInformationWithException()
    {
        $orderStatusUpdateRequestString = [];
        $status = $this->_updateOrderInformationObj->processOrderUpdateInformation($orderStatusUpdateRequestString);
        $exceptedResponse = '';
        $this->assertEquals($exceptedResponse, $status);
    }
}
