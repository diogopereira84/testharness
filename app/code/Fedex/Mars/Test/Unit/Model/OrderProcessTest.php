<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Test\Unit\Model;

use Fedex\Mars\Model\OrderProcess;
use Fedex\Shipment\Model\ProducingAddress;
use Fedex\Shipment\Model\ProducingAddressFactory;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as OrderInvoiceCollection;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection as OrderInvoiceItemCollection;
use Magento\Sales\Model\Order\Invoice\Item;
use Magento\Sales\Model\ResourceModel\Order\Address\Collection as AddressCollection;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection as PaymentCollection;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Status\History\Collection as HistoryCollection;
use Magento\Sales\Model\Order\Status\History;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item as ShipmentItem;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection as CreditmemoItemCollection;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class OrderProcessTest extends TestCase
{

    protected $orderInterfaceMock;
    protected $orderRepositoryMock;
    protected $producingAddressFactoryMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $salesInvoiceCollectionMock;
    protected $salesInvoiceMock;
    protected $salesInvoiceItemCollectionMock;
    protected $salesInvoiceItemMock;
    protected $addressCollectionMock;
    protected $addressMock;
    protected $orderItemCollectionMock;
    protected $orderItemMock;
    protected $orderPaymentCollectionMock;
    protected $orderPaymentMock;
    protected $orderHistoryCollectionMock;
    protected $orderHistoryMock;
    protected $orderShipmentCollectionMock;
    protected $orderShipmentMock;
    protected $orderShipmentItemMock;
    protected $shipmentTrackCollectionMock;
    protected $shipmentTrackMock;
    protected $creditMemoCollectionMock;
    protected $creditMemoMock;
    protected $creditMemoItemCollectionMock;
    protected $creditMemoItemMock;
    protected $configMock;
    protected $companyHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $orderProcessMock;
    protected function setUp(): void
    {
        $this->orderInterfaceMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getInvoiceCollection', 'getAddressesCollection', 'getItemsCollection',
                'getPaymentsCollection', 'getStatusHistoryCollection', 'getShipmentsCollection',
                'getCreditmemosCollection', 'getData'])
            ->getMockForAbstractClass();

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();

        $this->producingAddressFactoryMock = $this->getMockBuilder(ProducingAddressFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['getCollection','addFieldToFilter','addFieldToSelect','load','getFirstItem',
                'getAddress','getPhoneNumber','getEmailAddress','getLocationId','getAdditionalData'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['critical'])
            ->getMockForAbstractClass();

        $this->salesInvoiceCollectionMock = $this->getMockBuilder(OrderInvoiceCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();
        
        $this->salesInvoiceMock = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getItemsCollection'])
            ->getMock();

        $this->salesInvoiceItemCollectionMock = $this->getMockBuilder(OrderInvoiceItemCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->salesInvoiceItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
            
        $this->addressCollectionMock = $this->getMockBuilder(AddressCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->orderItemCollectionMock = $this->getMockBuilder(OrderItemCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->orderPaymentCollectionMock = $this->getMockBuilder(PaymentCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->orderPaymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->orderHistoryCollectionMock = $this->getMockBuilder(HistoryCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->orderHistoryMock = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->orderShipmentCollectionMock = $this->getMockBuilder(ShipmentCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->orderShipmentMock = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getItemsCollection', 'getTracksCollection'])
            ->getMock();

        $this->orderShipmentItemMock = $this->getMockBuilder(ShipmentItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->shipmentTrackCollectionMock = $this->getMockBuilder(TrackCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->shipmentTrackMock = $this->getMockBuilder(Track::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->creditMemoCollectionMock = $this->getMockBuilder(CreditmemoCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->creditMemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getItemsCollection'])
            ->getMock();

        $this->creditMemoItemCollectionMock = $this->getMockBuilder(CreditmemoItemCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMock();

        $this->creditMemoItemMock = $this->getMockBuilder(CreditmemoItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $this->configMock = $this->createMock(\Fedex\ProductBundle\Model\Config::class);
        $this->companyHelperMock = $this->createMock(\Fedex\Company\Helper\Data::class);

        $this->objectManager = new ObjectManager($this);
        $this->orderProcessMock = $this->objectManager->getObject(
            OrderProcess::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'producingAddressFactory' => $this->producingAddressFactoryMock,
                'logger' => $this->loggerMock,
                'config' => $this->configMock,
                'companyHelper' => $this->companyHelperMock
            ]
        );
    }

    /**
     * Test getOrderJson
     *
     * @param int $id
     * @param array $expectedOrderData
     *
     * @dataProvider getSimpleOrderTableDataDataProvider
     * @return void
     */
    public function testGetOrderJson($id, $expectedOrderData)
    {
        $this->orderRepositoryMock->method('get')->willReturn($this->orderInterfaceMock);
        $this->orderInterfaceMock->method('getInvoiceCollection')->willReturn($this->salesInvoiceCollectionMock);
        $this->orderInterfaceMock->method('getAddressesCollection')->willReturn($this->addressCollectionMock);
        $this->orderInterfaceMock->method('getItemsCollection')->willReturn($this->orderItemCollectionMock);
        $this->orderInterfaceMock->method('getPaymentsCollection')->willReturn($this->orderPaymentCollectionMock);
        $this->orderInterfaceMock->method('getStatusHistoryCollection')->willReturn($this->orderHistoryCollectionMock);
        $this->orderInterfaceMock->method('getShipmentsCollection')->willReturn($this->orderShipmentCollectionMock);
        $this->orderInterfaceMock->method('getCreditmemosCollection')->willReturn($this->creditMemoCollectionMock);
        $this->orderInterfaceMock->method('getCustomerId')->willReturn(1);

        $this->testGetSalesOrderData();
        $this->testGetSalesInvoiceTableData();
        $this->testGetSimpleOrderTableDataAddress();
        $this->testGetSimpleOrderTableDataOrderItem();
        $this->testGetSimpleOrderTableDataOrderPayment();
        $this->testGetSimpleOrderTableDataOrderHistory();
        $this->testGetSalesShipmentTableData();
        $this->testGetCreditMemoTableData();
        $this->testGetProducingAddress();

        $this->assertIsArray($this->orderProcessMock->getOrderJson($id));
        $this->assertEquals($this->orderProcessMock->getOrderJson($id), $expectedOrderData);
    }

    /**
     * Test GetProducingAddress
     *
     * @return void
     */
    public function testGetProducingAddress(): void
    {
        $this->producingAddressFactoryMock->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();

        $this->producingAddressFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->producingAddressFactoryMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->producingAddressFactoryMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->producingAddressFactoryMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->producingAddressFactoryMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf();
    }

    /**
     * Test getSimpleOrderTableData for sales_invoice_item table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataSalesInvoice()
    {
        $this->salesInvoiceItemCollectionMock->method('getItems')->willReturn([$this->salesInvoiceItemMock]);
        $this->salesInvoiceItemMock->method('getData')->willReturn(['entity_id' => '3']);

        $this->assertIsArray($this->orderProcessMock->getSimpleOrderTableData($this->salesInvoiceItemCollectionMock));
    }

    /**
     * Test getSimpleOrderTableData for sales_order_address table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataAddress()
    {
        $this->addressCollectionMock->method('getItems')->willReturn([$this->addressMock]);
        $this->addressMock->method('getData')->willReturn(['entity_id' => '4']);

        $this->assertIsArray($this->orderProcessMock->getSimpleOrderTableData($this->addressCollectionMock));
    }

    /**
     * Test getSimpleOrderTableData for sales_order_item table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataOrderItem()
    {
        $this->orderItemCollectionMock->method('getItems')->willReturn([$this->orderItemMock]);
        $this->orderItemMock->method('getData')->willReturn(['item_id' => '1', 'product_options' => []]);

        $this->assertIsArray(
            $this->orderProcessMock->getSimpleOrderTableData(
                $this->orderItemCollectionMock,
                needsEncoding: true,
                encodedValue: 'product_options'
            )
        );
    }

    /**
     * Test getSimpleOrderTableData for sales_order_payment table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataOrderPayment()
    {
        $this->orderPaymentCollectionMock->method('getItems')->willReturn([$this->orderPaymentMock]);
        $this->orderPaymentMock->method('getData')->willReturn(['entity_id' => '5', 'additional_information' => []]);

        $this->assertIsArray(
            $this->orderProcessMock->getSimpleOrderTableData(
                $this->orderPaymentCollectionMock,
                needsEncoding: true,
                encodedValue: 'additional_information'
            )
        );
    }

    /**
     * Test getSimpleOrderTableData for sales_order_status_history table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataOrderHistory()
    {
        $this->orderHistoryCollectionMock->method('getItems')->willReturn([$this->orderHistoryMock]);
        $this->orderHistoryMock->method('getData')->willReturn(['entity_id' => '6']);

        $this->assertIsArray($this->orderProcessMock->getSimpleOrderTableData($this->orderHistoryCollectionMock));
    }

    /**
     * Test getSimpleOrderTableData for sales_shipment_track table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataShipmentTracks()
    {
        $this->shipmentTrackCollectionMock->method('getItems')->willReturn([$this->shipmentTrackMock]);
        $this->shipmentTrackMock->method('getData')->willReturn(['entity_id' => '9']);
 
        $this->assertIsArray($this->orderProcessMock->getSimpleOrderTableData($this->shipmentTrackCollectionMock));
    }

    /**
     * Test getSimpleOrderTableData for sales_creditmemo_item table
     *
     * @return void
     */
    public function testGetSimpleOrderTableDataCreditMemo()
    {
        $this->creditMemoItemCollectionMock->method('getItems')->willReturn([$this->creditMemoItemMock]);
        $this->creditMemoItemMock->method('getData')->willReturn(['entity_id' => '11']);
 
        $this->assertIsArray($this->orderProcessMock->getSimpleOrderTableData($this->creditMemoItemCollectionMock));
    }

    /**
     * Test getSalesOrderData
     *
     * @return void
     */
    public function testGetSalesOrderData()
    {
        $this->orderInterfaceMock->method('getData')->willReturn(['entity_id' => '1']);

        $this->assertIsArray($this->orderProcessMock->getSalesOrderData($this->orderInterfaceMock));
    }

    /**
     * Test getSalesInvoiceTableData
     *
     * @return void
     */
    public function testGetSalesInvoiceTableData()
    {
        $this->salesInvoiceCollectionMock->method('getItems')->willReturn([$this->salesInvoiceMock]);
        $this->salesInvoiceMock->method('getData')->willReturn(['entity_id' => '2']);
        $this->salesInvoiceMock->method('getItemsCollection')->willReturn($this->salesInvoiceItemCollectionMock);

        $this->testGetSimpleOrderTableDataSalesInvoice();

        $this->assertIsArray($this->orderProcessMock->getSalesInvoiceTableData($this->salesInvoiceCollectionMock));
    }

    /**
     * Test getSalesShipmentTableData
     *
     * @return void
     */
    public function testGetSalesShipmentTableData()
    {
        $this->orderShipmentCollectionMock->method('getItems')->willReturn([$this->orderShipmentMock]);
        $this->orderShipmentMock->method('getData')->willReturn(['entity_id' => '7', 'packages' => []]);
        $this->orderShipmentMock->method('getItemsCollection')->willReturn([$this->orderShipmentItemMock]);
        $this->orderShipmentItemMock->method('getData')->willReturn(['entity_id' => '8']);
        $this->orderShipmentMock->method('getTracksCollection')->willReturn($this->shipmentTrackCollectionMock);
        $this->testGetSimpleOrderTableDataShipmentTracks();

        $this->assertIsArray(
            $this->orderProcessMock->getSalesShipmentTableData(
                $this->orderShipmentCollectionMock,
                'packages'
            )
        );
    }

    /**
     * Test getCreditMemoTableData
     *
     * @return void
     */
    public function testGetCreditMemoTableData()
    {
        $this->creditMemoCollectionMock->method('getItems')->willReturn([$this->creditMemoMock]);
        $this->creditMemoMock->method('getData')->willReturn(['entity_id' => '10']);
        $this->creditMemoMock->method('getItemsCollection')->willReturn($this->creditMemoItemCollectionMock);
        $this->testGetSimpleOrderTableDataCreditMemo();

        $this->assertIsArray($this->orderProcessMock->getCreditMemoTableData($this->creditMemoCollectionMock));
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getSimpleOrderTableDataDataProvider(): array
    {
        $expectedOrderResult = [
            [
                'entity_id' => '1',
                'company' => null,
                'content_type' => 'ORDER',
                'order_producing_address' => [
                    'address' => null,
                    'phone_number' => null,
                    'email_address' => null,
                    'location_id' => null,
                    'additional_data' => null,
                ],
                'sales_invoices' => [
                    [
                        'entity_id' => '2',
                        'sales_invoice_items' => [
                            [
                                'entity_id' => '3'
                            ]
                        ]
                    ]
                ],
                'sales_order_addresses' => [
                    [
                        'entity_id' => '4'
                    ]
                ],
                'sales_order_items' => [
                    [
                        'item_id' => '1',
                        'product_options' => '[]'
                    ]
                ],
                'sales_order_payments' => [
                    [
                        'entity_id' => '5',
                        'additional_information' => '[]'
                    ]
                ],
                'sales_order_status_history' => [
                    [
                        'entity_id' => '6'
                    ]
                ],
                'sales_shipments' => [
                    [
                        'entity_id' => '7',
                        'packages' => '[]',
                        'sales_shipment_items' => [
                            [
                                'entity_id' => '8'
                            ]
                        ],
                        'sales_shipment_tracks' => [
                            [
                                'entity_id' => '9'
                            ]
                        ]
                    ]
                ],
                'sales_credit_memos' => [
                    [
                        'entity_id' => '10',
                        'sales_credit_memo_items' => [
                            [
                                'entity_id' => '11'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return [
            [1, $expectedOrderResult]
        ];
    }

    /**
     * Data provider for testing parent and child item logic.
     *
     * @return array
     */
    public function parentChildDataProvider(): array
    {
        $parentItemData = [
            'item_id' => '120',
            'parent_item_id' => null, // This key will be removed by array_filter in the SUT
            'name' => 'Test Bundle Product',
            'product_type' => 'bundle',
            'product_options' => ['info_buyRequest' => ['qty' => 1]]
        ];

        $childItemData = [
            'item_id' => '121',
            'parent_item_id' => '120',
            'name' => 'Test Simple Product',
            'product_type' => 'simple',
            'product_options' => ['info_buyRequest' => ['qty' => 1]]
        ];

        return [
            'toggle enabled, parent info is added to child' => [
                'isToggleEnabled' => true,
                'itemsData' => [$parentItemData, $childItemData],
                'expected' => [
                    // Expected for Parent Item: Note the absence of 'parent_item_id'
                    [
                        'item_id' => '120',
                        'name' => 'Test Bundle Product',
                        'product_type' => 'bundle',
                        'product_options' => '{"info_buyRequest":{"qty":1}}'
                    ],
                    // Expected for Child Item: Now includes the parent info
                    [
                        'item_id' => '121',
                        'parent_item_id' => '120',
                        'name' => 'Test Simple Product',
                        'product_type' => 'simple',
                        'product_options' => '{"info_buyRequest":{"qty":1}}',
                        'parent_product_name' => 'Test Bundle Product',
                        'parent_product_type' => 'bundle'
                    ]
                ]
            ],
            'toggle disabled, parent info is not added' => [
                'isToggleEnabled' => false,
                'itemsData' => [$parentItemData, $childItemData],
                'expected' => [
                    // Expected for Parent Item
                    [
                        'item_id' => '120',
                        'name' => 'Test Bundle Product',
                        'product_type' => 'bundle',
                        'product_options' => '{"info_buyRequest":{"qty":1}}'
                    ],
                    // Expected for Child Item: No parent info is added
                    [
                        'item_id' => '121',
                        'parent_item_id' => '120',
                        'name' => 'Test Simple Product',
                        'product_type' => 'simple',
                        'product_options' => '{"info_buyRequest":{"qty":1}}'
                    ]
                ]
            ]
        ];
    }

    /**
     * Test getSimpleOrderTableData with parent and child order items.
     *
     * @param bool $isToggleEnabled
     * @param array $itemsData
     * @param array $expectedResult
     * @dataProvider parentChildDataProvider
     */
    public function testGetSimpleOrderTableDataForParentChildItems(bool $isToggleEnabled, array $itemsData, array $expectedResult)
    {
        // This mock needs to be set up in your setUp() method
        $this->configMock->method('isTigerE468338ToggleEnabled')->willReturn($isToggleEnabled);

        $orderItems = [];
        foreach ($itemsData as $itemData) {
            // Create a mock that implements ArrayAccess
            $itemMock = $this->getMockBuilder(OrderItem::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getData', 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset'])
                ->getMock();

            // The SUT calls getData() directly, so we mock it
            $itemMock->method('getData')->willReturn($itemData);

            // The SUT also uses array access, so we mock the ArrayAccess methods
            $itemMock->method('offsetGet')
                ->willReturnCallback(function ($key) use ($itemData) {
                    return $itemData[$key] ?? null;
                });

            $itemMock->method('offsetExists')
                ->willReturnCallback(function ($key) use ($itemData) {
                    return isset($itemData[$key]);
                });

            $orderItems[] = $itemMock;
        }

        $this->orderItemCollectionMock->method('getItems')->willReturn($orderItems);

        $actualResult = $this->orderProcessMock->getSimpleOrderTableData(
            $this->orderItemCollectionMock,
            'Fedex\Mars\Model\OrderProcess::checkIsNull',
            0,
            true,
            'product_options'
        );

        $this->assertEquals($expectedResult, $actualResult);
    }
}
