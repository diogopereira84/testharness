<?php

declare(strict_types=1);

namespace Fedex\Purchaseorder\Test\Unit\Model;

use Fedex\Purchaseorder\Model\OrderUpdate;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Purchaseorder\Api\OrderUpdateInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\Shipping\Helper\Data;
use Magento\Shipping\Model\Info;
use Magento\Shipping\Model\Order\TrackFactory;
use Magento\Shipping\Model\ResourceModel\Order\Track\CollectionFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Convert\OrderFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Api\Data\OrderItemInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderUpdateTest extends TestCase
{
    protected $request;
    /**
     * @var (\Magento\Sales\Model\Order\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderCollection;
    protected $order;
    protected $orderRepository;
    protected $statusCollection;
    protected $trackFactory;
    protected $shipmentRepository;
    protected $convertOrder;
    protected $orderFactory;
    protected $orderMock;
    protected $orderInterface;
    protected $shipmentInterface;
    protected $trackInterface;
    protected $ShipmentMock;
    protected $Shipmenttrack;
    /**
     * @var (\Fedex\Purchaseorder\Test\Unit\Model\QuoteItem & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_quoteItemMock;
    protected $orderItemMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $HelperData;
    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(
            \Magento\Framework\App\RequestInterface::class
        )
            ->setMethods(["getContent"])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderCollection = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Collection::class
        )
            ->setMethods(["getIncrementId", "loadByIncrementId"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods([
                "getIncrementId",
                "loadByIncrementId",
                "load",
                "getShipmentsCollection",
                "getAllItems",
                "setState",
                "setStatus",
                "save",
                "setIsInProcess",
                "getQtyToShip",
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepository = $this->getMockBuilder(
            \Magento\Sales\Api\OrderRepositoryInterface::class
        )
            ->setMethods(["get", "getAllItems"])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->statusCollection = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Status\Collection::class
        )
            ->setMethods(["toOptionArray", "joinStates"])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->trackFactory = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Shipment\TrackFactory::class
        )
            ->setmethods(["create"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->shipmentRepository = $this->getMockBuilder(
            \Magento\Sales\Model\Order\ShipmentRepository::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->convertOrder = $this->getMockBuilder(
            \Magento\Sales\Model\Convert\Order::class
        )
            ->setMethods(["toShipment", "itemToShipmentItem"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFactory = $this->getMockBuilder(
            \Magento\Sales\Model\OrderFactory::class
        )
            ->setMethods(["create", "getShipmentsCollection"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(
            \Magento\Framework\DataObject::class
        )
            ->setMethods(["getData"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderInterface = $this->getMockBuilder(
            \Magento\Sales\Api\Data\OrderInterface::class
        )
            ->setMethods([
                "getIncrementId",
                "loadByIncrementId",
                "setState",
                "setStatus",
                "save",
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shipmentInterface = $this->getMockBuilder(
            \Magento\Sales\Api\Data\ShipmentInterface::class
        )
            ->setmethods([
                "setNumber",
                "setCarrierCode",
                "setTitle",
                "getTracks",
                "addTrack",
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->trackInterface = $this->getMockBuilder(
            ShipmentTrackInterface::class
        )
            ->setMethods(["getData"])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->ShipmentMock = $this->getMockBuilder(
            \Magento\Sales\Model\Shipment::class
        )
            ->setMethods([
                "getId",
                "addTrack",
                "getTracks",
                "loadByIncrementId",
                "register",
                "getOrder",
                "setIsInProcess",
                "save",
                "addItem",
                "setQty",
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->Shipmenttrack = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Shipment\Track::class
        )
            ->setMethods(["load", "save", "setNumber"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(["getAllItems"])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemMock = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Item::class
        )
            ->disableOriginalConstructor()
            ->setMethods(["getQtyToShip", "getLockedDoShip", "getIsVirtual"])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->HelperData = $this->objectManager->getObject(
            OrderUpdate::class,
            [
                "request" => $this->request,
                "order" => $this->order,
                "orderRepository" => $this->orderRepository,
                "statusCollection" => $this->statusCollection,
                "trackFactory" => $this->trackFactory,
                "shipmentRepository" => $this->shipmentRepository,
                "convertOrder" => $this->convertOrder,
                "orderFactory" => $this->orderFactory,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test Case for updateOrderStatus method.
     *
     * @return array
     */

    public function testUpdateOrderStatus()
    {
        $orderId = "1012476114449785";
        $shipmentId = [0 => "312352436453", 1 => "312334436453"];
        $responseMessage = ["code" => "400", "message" => "Not a valid status"];
        $requestData = [
            "orderNumber" => "400",
            "orderStatus" => "valid",
            "trackingNumbers" => ["124241242", "2535252", "32435235"],
        ];
        $jason_data = '[
            {
                status:"400",
                state:"Not a valid status"
            }
        ]';
        $status = "IN_PROGRESS";
        $state = "new";
        $trackingId = "794618958630";
        $tempOrder = [
            "currency_id" => "USD",
            "email" => "test@yueh.com",
            "shipping_address" => [
                "firstname" => "jhon",
                "lastname" => "Deo",
                "street" => "xxxxx",
                "city" => "xxxxx",
                "country_id" => "IN",
                "region" => "xxx",
                "postcode" => "43244",
                "telephone" => "52332",
                "fax" => "32423",
                "save_in_address_book" => 1,
            ],
            "items" => [
                ["product_id" => "1", "qty" => 1],
                ["product_id" => "2", "qty" => 2],
            ],
        ];
        $items = [
            new DataObject([
                "parent_item" => "parent item 1",
                "name" => "name 1",
                "qty_ordered" => 1,
                "base_price" => 0.1,
            ]),
            new DataObject([
                "parent_item" => "parent item 2",
                "name" => "name 2",
                "qty_ordered" => 2,
                "base_price" => 1.2,
            ]),
            new DataObject([
                "parent_item" => "parent item 3",
                "name" => "name 3",
                "qty_ordered" => 3,
                "base_price" => 2.3,
            ]),
        ];
        $orderStatusList = [["status" => "valid", "state" => "active"]];
        $orderStateStatusList = [["status" => "valid", "state" => "active"]];
        $this->request
            ->expects($this->any())
            ->method("getContent")
            ->willReturn(json_encode($requestData));
        $this->statusCollection
            ->expects($this->any())
            ->method("toOptionArray")
            ->willReturn($orderStatusList);
        $this->statusCollection
            ->expects($this->any())
            ->method("joinStates")
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->any())
            ->method("getData")
            ->willReturn($orderStatusList);
        $this->orderRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->orderInterface);
        $this->orderInterface
            ->expects($this->any())
            ->method("getIncrementId")
            ->willReturn("101001");
        $this->order
            ->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);
        $this->orderInterface
            ->expects($this->any())
            ->method("setState")
            ->willReturnSelf();
        $this->orderInterface
            ->expects($this->any())
            ->method("setStatus")
            ->willReturnSelf();
        $this->orderInterface->expects($this->any())->method("save");
        $this->orderFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("load")
            ->with($orderId)
            ->willReturnSelf();
        $shipmentCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(["getIterator"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order
            ->expects($this->any())
            ->method("getShipmentsCollection")
            ->willReturn($shipmentCollection);
        $shipmentCollection
            ->expects($this->any())
            ->method("getIterator")
            ->willReturn(new \ArrayIterator([1 => $this->ShipmentMock]));
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getId")
            ->willReturn($shipmentId);
        $this->shipmentRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->shipmentInterface);
        $this->trackFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->shipmentInterface);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("getTracks")
            ->willReturnSelf();
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setNumber")
            ->willReturn(2312313);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setCarrierCode")
            ->willReturn("fedex");
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setTitle")
            ->willReturn("Federal Express");
        $this->shipmentInterface
            ->expects($this->any())
            ->method("addTrack")
            ->willReturn($this->Shipmenttrack);
        $this->convertOrder
            ->expects($this->any())
            ->method("toShipment")
            ->willReturn($this->ShipmentMock);
        $this->order
            ->expects($this->any())
            ->method("getAllItems")
            ->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getQtyToShip")
            ->willReturn(3);
        $this->HelperData->UpdateOrderStatus($orderId);
    }
    /**
     * Test Case for updatesOrderStatus method.
     *
     * @return array
     */

    public function testUpdatesOrderStatus()
    {
        $orderId = "1012476114449785";
        $shipmentId = [0 => "312352436453", 1 => "312334436453"];
        $responseMessage = ["code" => "400", "message" => "Not a valid status"];
        $requestData = [
            "orderNumber" => "400",
            "orderStatus" => "cancelled",
            "trackingNumbers" => ["124241242", "2535252", "32435235"],
        ];
        $jason_data = '[
            {
                status:"400",
                state:"Not a valid status"
            }
        ]';
        $status = "IN_PROGRESS";
        $state = "new";
        $trackingId = "794618958630";
        $tempOrder = [
            "currency_id" => "USD",
            "email" => "test@yueh.com",
            "shipping_address" => [
                "firstname" => "jhon",
                "lastname" => "Deo",
                "street" => "xxxxx",
                "city" => "xxxxx",
                "country_id" => "IN",
                "region" => "xxx",
                "postcode" => "43244",
                "telephone" => "52332",
                "fax" => "32423",
                "save_in_address_book" => 1,
            ],
            "items" => [
                ["product_id" => "1", "qty" => 1],
                ["product_id" => "2", "qty" => 2],
            ],
        ];
        $items = [
            new DataObject([
                "parent_item" => "parent item 1",
                "name" => "name 1",
                "qty_ordered" => 1,
                "base_price" => 0.1,
            ]),
            new DataObject([
                "parent_item" => "parent item 2",
                "name" => "name 2",
                "qty_ordered" => 2,
                "base_price" => 1.2,
            ]),
            new DataObject([
                "parent_item" => "parent item 3",
                "name" => "name 3",
                "qty_ordered" => 3,
                "base_price" => 2.3,
            ]),
        ];
        $orderStatusList = [["status" => "canceled", "state" => "active"]];
        $orderStateStatusList = [["status" => "valid", "state" => "active"]];
        $this->request
            ->expects($this->any())
            ->method("getContent")
            ->willReturn(json_encode($requestData));
        $this->statusCollection
            ->expects($this->any())
            ->method("toOptionArray")
            ->willReturn($orderStatusList);
        $this->statusCollection
            ->expects($this->any())
            ->method("joinStates")
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->any())
            ->method("getData")
            ->willReturn($orderStatusList);
        $this->orderRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->order);
        $this->orderInterface
            ->expects($this->any())
            ->method("getIncrementId")
            ->willReturn("101001");
        $this->order
            ->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);
        $this->orderInterface
            ->expects($this->any())
            ->method("setState")
            ->willReturnSelf();
        $this->orderInterface
            ->expects($this->any())
            ->method("setStatus")
            ->willReturnSelf();
        $this->orderInterface->expects($this->any())->method("save");
        $this->orderFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("load")
            ->with($orderId)
            ->willReturnSelf();
        $shipmentCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(["getIterator"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order
            ->expects($this->any())
            ->method("getShipmentsCollection")
            ->willReturn([]);
        $shipmentCollection
            ->expects($this->any())
            ->method("getIterator")
            ->willReturn(new \ArrayIterator([1 => $this->ShipmentMock]));
        $this->ShipmentMock->expects($this->any())->method("getId"); //->willReturn(false);
        $this->convertOrder
            ->expects($this->any())
            ->method("toShipment")
            ->willReturn($this->ShipmentMock);
        $this->convertOrder
            ->expects($this->any())
            ->method("itemToShipmentItem")
            ->willReturn($this->ShipmentMock);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("setQty")
            ->willReturn(true);
        $this->order
            ->expects($this->any())
            ->method("getAllItems")
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getQtyToShip")
            ->willReturn(true);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getIsVirtual")
            ->willReturn(false);
        $this->shipmentRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->shipmentInterface);
        $this->trackFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->shipmentInterface);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("getTracks")
            ->willReturn([$this->trackInterface]);
        $this->trackInterface
            ->expects($this->any())
            ->method("getData")
            ->with(["entity_id"])
            ->willReturn(4);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setNumber")
            ->willReturn(32324);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setCarrierCode")
            ->willReturn("fedex");
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setTitle")
            ->willReturn("Federal Express");
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addTrack")
            ->willReturn($this->Shipmenttrack);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addItem")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("register")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getOrder")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("setIsInProcess")
            ->willReturn(true);
        $this->ShipmentMock->expects($this->any())->method("save");

        $this->HelperData->UpdateOrderStatus($orderId);
    }

    /**
     * Test Case for updatesOrderStatus method.
     *
     * @return array
     */

    public function testUpdatesOrderStatuswithQtyToShipFalse()
    {
        $orderId = "1012476114449785";
        $shipmentId = [0 => "312352436453", 1 => "312334436453"];
        $responseMessage = ["code" => "400", "message" => "Not a valid status"];
        $requestData = [
            "orderNumber" => "400",
            "orderStatus" => "cancelled",
            "trackingNumbers" => ["124241242", "2535252", "32435235"],
        ];
        $jason_data = '[
            {
                status:"400",
                state:"Not a valid status"
            }
        ]';
        $status = "IN_PROGRESS";
        $state = "new";
        $trackingId = "794618958630";
        $tempOrder = [
            "currency_id" => "USD",
            "email" => "test@yueh.com",
            "shipping_address" => [
                "firstname" => "jhon",
                "lastname" => "Deo",
                "street" => "xxxxx",
                "city" => "xxxxx",
                "country_id" => "IN",
                "region" => "xxx",
                "postcode" => "43244",
                "telephone" => "52332",
                "fax" => "32423",
                "save_in_address_book" => 1,
            ],
            "items" => [
                ["product_id" => "1", "qty" => 1],
                ["product_id" => "2", "qty" => 2],
            ],
        ];
        $items = [
            new DataObject([
                "parent_item" => "parent item 1",
                "name" => "name 1",
                "qty_ordered" => 1,
                "base_price" => 0.1,
            ]),
            new DataObject([
                "parent_item" => "parent item 2",
                "name" => "name 2",
                "qty_ordered" => 2,
                "base_price" => 1.2,
            ]),
            new DataObject([
                "parent_item" => "parent item 3",
                "name" => "name 3",
                "qty_ordered" => 3,
                "base_price" => 2.3,
            ]),
        ];
        $orderStatusList = [["status" => "canceled", "state" => "active"]];
        $orderStateStatusList = [["status" => "valid", "state" => "active"]];
        $this->request
            ->expects($this->any())
            ->method("getContent")
            ->willReturn(json_encode($requestData));
        $this->statusCollection
            ->expects($this->any())
            ->method("toOptionArray")
            ->willReturn($orderStatusList);
        $this->statusCollection
            ->expects($this->any())
            ->method("joinStates")
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->any())
            ->method("getData")
            ->willReturn($orderStatusList);
        $this->orderRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->order);
        $this->orderInterface
            ->expects($this->any())
            ->method("getIncrementId")
            ->willReturn("101001");
        $this->order
            ->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);
        $this->orderInterface
            ->expects($this->any())
            ->method("setState")
            ->willReturnSelf();
        $this->orderInterface
            ->expects($this->any())
            ->method("setStatus")
            ->willReturnSelf();
        $this->orderInterface->expects($this->any())->method("save");
        $this->orderFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("load")
            ->with($orderId)
            ->willReturnSelf();
        $shipmentCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(["getIterator"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order
            ->expects($this->any())
            ->method("getShipmentsCollection")
            ->willReturn([]);
        $shipmentCollection
            ->expects($this->any())
            ->method("getIterator")
            ->willReturn(new \ArrayIterator([1 => $this->ShipmentMock]));
        $this->ShipmentMock->expects($this->any())->method("getId"); //->willReturn(false);
        $this->convertOrder
            ->expects($this->any())
            ->method("toShipment")
            ->willReturn($this->ShipmentMock);
        $this->convertOrder
            ->expects($this->any())
            ->method("itemToShipmentItem")
            ->willReturn($this->ShipmentMock);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("setQty")
            ->willReturn(true);
        $this->order
            ->expects($this->any())
            ->method("getAllItems")
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getQtyToShip")
            ->willReturn(false);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getIsVirtual")
            ->willReturn(false);
        $this->shipmentRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->shipmentInterface);
        $this->trackFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->shipmentInterface);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("getTracks")
            ->willReturn([$this->trackInterface]);
        $this->trackInterface
            ->expects($this->any())
            ->method("getData")
            ->with(["entity_id"])
            ->willReturn(4);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setNumber")
            ->willReturn(32324);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setCarrierCode")
            ->willReturn("fedex");
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setTitle")
            ->willReturn("Federal Express");
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addTrack")
            ->willReturn($this->Shipmenttrack);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addItem")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("register")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getOrder")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("setIsInProcess")
            ->willReturn(true);
        $this->ShipmentMock->expects($this->any())->method("save");

        $this->HelperData->UpdateOrderStatus($orderId);
    }
    /**
     * Test Case for updatesOrderStatus method.
     *
     * @return array
     */

    public function testUpdatedOrderStatus()
    {
        $orderId = "1012476114449785";
        $shipmentId = [0 => "312352436453", 1 => "312334436453"];
        $responseMessage = ["code" => "400", "message" => "Not a valid status"];
        $requestData = [
            "orderNumber" => "400",
            "orderStatus" => "cancel",
            "trackingNumbers" => ["124241242", "2535252", "32435235"],
        ];
        $jason_data = '[
            {
                status:"400",
                state:"Not a valid status"
            }
        ]';
        $status = "IN_PROGRESS";
        $state = "new";
        $trackingId = "794618958630";
        $tempOrder = [
            "currency_id" => "USD",
            "email" => "test@yueh.com",
            "shipping_address" => [
                "firstname" => "jhon",
                "lastname" => "Deo",
                "street" => "xxxxx",
                "city" => "xxxxx",
                "country_id" => "IN",
                "region" => "xxx",
                "postcode" => "43244",
                "telephone" => "52332",
                "fax" => "32423",
                "save_in_address_book" => 1,
            ],
            "items" => [
                ["product_id" => "1", "qty" => 1],
                ["product_id" => "2", "qty" => 2],
            ],
        ];
        $items = [
            new DataObject([
                "parent_item" => "parent item 1",
                "name" => "name 1",
                "qty_ordered" => 1,
                "base_price" => 0.1,
            ]),
            new DataObject([
                "parent_item" => "parent item 2",
                "name" => "name 2",
                "qty_ordered" => 2,
                "base_price" => 1.2,
            ]),
            new DataObject([
                "parent_item" => "parent item 3",
                "name" => "name 3",
                "qty_ordered" => 3,
                "base_price" => 2.3,
            ]),
        ];
        $orderStatusList = [["status" => "delete", "state" => "active"]];
        $orderStateStatusList = [["status" => "valid", "state" => "active"]];
        $this->request
            ->expects($this->any())
            ->method("getContent")
            ->willReturn(json_encode($requestData));
        $this->statusCollection
            ->expects($this->any())
            ->method("toOptionArray")
            ->willReturn($orderStatusList);
        $this->statusCollection
            ->expects($this->any())
            ->method("joinStates")
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->any())
            ->method("getData")
            ->willReturn($orderStatusList);
        $this->orderRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->order);
        $this->orderInterface
            ->expects($this->any())
            ->method("getIncrementId")
            ->willReturn("101001");
        $this->order
            ->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);
        $this->orderInterface
            ->expects($this->any())
            ->method("setState")
            ->willReturnSelf();
        $this->orderInterface
            ->expects($this->any())
            ->method("setStatus")
            ->willReturnSelf();

        $this->orderInterface->expects($this->any())->method("save");
        $this->orderFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("load")
            ->with($orderId)
            ->willReturnSelf();
        $shipmentCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(["getIterator"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order
            ->expects($this->any())
            ->method("getShipmentsCollection")
            ->willReturn([]);
        $shipmentCollection
            ->expects($this->any())
            ->method("getIterator")
            ->willReturn(new \ArrayIterator([1 => $this->ShipmentMock]));
        $this->ShipmentMock->expects($this->any())->method("getId");
        $this->convertOrder
            ->expects($this->any())
            ->method("toShipment")
            ->willReturn($this->ShipmentMock);
        $this->convertOrder
            ->expects($this->any())
            ->method("itemToShipmentItem")
            ->willReturn($this->ShipmentMock);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("setQty")
            ->willReturn(true);
        $this->order
            ->expects($this->any())
            ->method("getAllItems")
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getQtyToShip")
            ->willReturn(3);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getIsVirtual")
            ->willReturn(3);
        $this->shipmentRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->shipmentInterface);
        $this->trackFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->shipmentInterface);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getTracks")
            ->willReturn($this->trackInterface);
        $this->trackInterface
            ->expects($this->any())
            ->method("getData")
            ->with(["entity_id"])
            ->willReturn(4);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setNumber")
            ->willReturn(32324);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setCarrierCode")
            ->willReturn("fedex");
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setTitle")
            ->willReturn("Federal Express");
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addTrack")
            ->willReturn($this->Shipmenttrack);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addItem")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("register")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getOrder")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("setIsInProcess")
            ->willReturn(true);
        $this->ShipmentMock->expects($this->any())->method("save");
        $this->HelperData->UpdateOrderStatus($orderId);
    }

    /**
     * Test Case for updatesOrderStatus method.
     *
     * @return array
     */

    public function testUpdatedOrderStatuswithException()
    {
        $orderId = "1012476114449785";
        $shipmentId = [0 => "312352436453", 1 => "312334436453"];
        $responseMessage = ["code" => "400", "message" => "Not a valid status"];
        $requestData = [
            "orderNumber" => "400",
            "orderStatus" => "cancel",
            "trackingNumbers" => ["124241242", "2535252", "32435235"],
        ];
        $jason_data = '[
            {
                status:"400",
                state:"Not a valid status"
            }
        ]';
        $status = "IN_PROGRESS";
        $state = "new";
        $trackingId = "794618958630";
        $tempOrder = [
            "currency_id" => "USD",
            "email" => "test@yueh.com",
            "shipping_address" => [
                "firstname" => "jhon",
                "lastname" => "Deo",
                "street" => "xxxxx",
                "city" => "xxxxx",
                "country_id" => "IN",
                "region" => "xxx",
                "postcode" => "43244",
                "telephone" => "52332",
                "fax" => "32423",
                "save_in_address_book" => 1,
            ],
            "items" => [
                ["product_id" => "1", "qty" => 1],
                ["product_id" => "2", "qty" => 2],
            ],
        ];
        $items = [
            new DataObject([
                "parent_item" => "parent item 1",
                "name" => "name 1",
                "qty_ordered" => 1,
                "base_price" => 0.1,
            ]),
            new DataObject([
                "parent_item" => "parent item 2",
                "name" => "name 2",
                "qty_ordered" => 2,
                "base_price" => 1.2,
            ]),
            new DataObject([
                "parent_item" => "parent item 3",
                "name" => "name 3",
                "qty_ordered" => 3,
                "base_price" => 2.3,
            ]),
        ];
        $orderStatusList = [["status" => "delete", "state" => "active"]];
        $orderStateStatusList = [["status" => "valid", "state" => "active"]];
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->request
            ->expects($this->any())
            ->method("getContent")
            ->willReturn(json_encode($requestData));
        $this->statusCollection
            ->expects($this->any())
            ->method("toOptionArray")
            ->willReturn($orderStatusList);
        $this->statusCollection
            ->expects($this->any())
            ->method("joinStates")
            ->willReturn($this->orderMock);
        $this->orderMock
            ->expects($this->any())
            ->method("getData")
            ->willReturn($orderStatusList);
        $this->orderRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->order);
        $this->orderInterface
            ->expects($this->any())
            ->method("getIncrementId")
            ->willReturn("101001");
        $this->order
            ->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);
        $this->orderInterface
            ->expects($this->any())
            ->method("setState")
            ->willReturnSelf();
        $this->orderInterface
            ->expects($this->any())
            ->method("setStatus")
            ->willReturnSelf();

        $this->orderInterface->expects($this->any())->method("save");
        $this->orderFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("load")
            ->with($orderId)
            ->willReturnSelf();
        $shipmentCollection = $this->getMockBuilder(Collection::class)
            ->setMethods(["getIterator"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->order
            ->expects($this->any())
            ->method("getShipmentsCollection")
            ->willReturn([]);
        $shipmentCollection
            ->expects($this->any())
            ->method("getIterator")
            ->willReturn(new \ArrayIterator([1 => $this->ShipmentMock]));
        $this->ShipmentMock->expects($this->any())->method("getId");
        $this->convertOrder
            ->expects($this->any())
            ->method("toShipment")
            ->willReturn($this->ShipmentMock);
        $this->convertOrder
            ->expects($this->any())
            ->method("itemToShipmentItem")
            ->willReturn($this->ShipmentMock);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("setQty")
            ->willReturn(true);
        $this->order
            ->expects($this->any())
            ->method("getAllItems")
            ->willReturn([$this->orderItemMock]);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getQtyToShip")
            ->willReturn(3);
        $this->orderItemMock
            ->expects($this->any())
            ->method("getIsVirtual")
            ->willReturn(3);
        $this->shipmentRepository
            ->expects($this->any())
            ->method("get")
            ->willThrowException($exception);
        $this->trackFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->shipmentInterface);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getTracks")
            ->willReturn($this->trackInterface);
        $this->trackInterface
            ->expects($this->any())
            ->method("getData")
            ->with(["entity_id"])
            ->willReturn(4);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setNumber")
            ->willReturn(32324);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setCarrierCode")
            ->willReturn("fedex");
        $this->shipmentInterface
            ->expects($this->any())
            ->method("setTitle")
            ->willReturn("Federal Express");
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addTrack")
            ->willReturn($this->Shipmenttrack);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("addItem")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("register")
            ->willReturn(true);
        $this->ShipmentMock
            ->expects($this->any())
            ->method("getOrder")
            ->willReturn($this->order);
        $this->order
            ->expects($this->any())
            ->method("setIsInProcess")
            ->willReturn(true);
        $this->ShipmentMock->expects($this->any())->method("save");
        $this->HelperData->UpdateOrderStatus($orderId);
    }

    /**
     * Test Case for addTrackId method.
     *
     * @return void
     */

    public function testAddTrackId()
    {
        $trackingId = "23152537657";
        $shipmentId = [0 => "312352436453"];
        $this->shipmentRepository
            ->expects($this->any())
            ->method("get")
            ->willReturn($this->shipmentInterface);
        $this->shipmentInterface
            ->expects($this->any())
            ->method("getTracks")
            ->willReturn([$this->trackInterface]);
        $this->trackInterface
            ->expects($this->any())
            ->method("getData")
            ->with("entity_id")
            ->willReturn(4);
        $this->trackFactory
            ->expects($this->any())
            ->method("create")
            ->willReturn($this->Shipmenttrack);
        $this->Shipmenttrack
            ->expects($this->any())
            ->method("load")
            ->willReturnSelf();
        $this->Shipmenttrack
            ->expects($this->any())
            ->method("setNumber")
            ->willReturnSelf();
        $this->Shipmenttrack->expects($this->any())->method("save");
        $this->HelperData->addTrackId($shipmentId, $trackingId);
    }

}
