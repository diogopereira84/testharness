<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceRates
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\TrackOrder\Test\Unit\Model;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Fedex\Shipment\Helper\Data as ShipmentHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as ShipmentTrackCollection;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Mirakl\MMP\Common\Domain\Order\State\OrderStatus;
use Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\Shipment\Model\ResourceModel\DueDateLog\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Fedex\Shipment\Api\GetOrderByIncrementIdInterface;
use Fedex\Shipment\Api\DueDateLogRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InStoreConfigInterface;

class OrderDetailsDataMapperTest extends TestCase
{
    protected $toggleConfig;
    /**
     * @var (\Fedex\MarketplaceProduct\Model\ShopManagement & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shopManagement;

    /**
     * @var OrderDetailsDataMapper
     */
    private $orderDetailsDataMapper;

    /**
     * @var ShipmentHelper
     */
    private $shipmentHelperMock;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactoryMock;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepositoryMock;

    /**
     * @var PageFactory
     */
    private $resultPageFactoryMock;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var Image
     */
    private $imageHelperMock;

    /**
     * @var ProductFactory
     */
    private $productFactoryMock;

    /**
     * @var Data
     */
    private $priceHelperMock;

    /**
     * @var MiraklHelper
     */
    private $miraklHelperMock;

    /**
     * @var ScopeConfigInterface
     */
    private $configInterfaceMock;
    private $shippingRepositoryInterface;
    private $creditMemoRepository;

    private $shipmentApi;
    private $marketplaceCheckoutHelper;
    private $collectionMock;
    private $dueDateLogCollectionFactory;
    private $getOrderByIncrementId;
    private $dueDateLogRepository;
    private $productBundleConfig;
    private $inStoreConfigInterface;


    protected function setUp(): void
    {
        $this->shipmentHelperMock = $this->getMockBuilder(ShipmentHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->getMock();

        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->imageHelperMock = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->miraklHelperMock = $this->getMockBuilder(MiraklHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shopManagement = $this->getMockBuilder(ShopManagement::class)
            ->addMethods(['getShop'])
            ->onlyMethods(['getShopByProduct'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shippingRepositoryInterface = $this->getMockBuilder(ShipmentRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->creditMemoRepository = $this->getMockBuilder(CreditmemoRepository::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->shipmentApi = $this->getMockBuilder(ShipmentApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceCheckoutHelper = $this->getMockBuilder(MarketplaceCheckoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionMock = $this->createMock(AbstractDb::class);

        // Mock the factory to return the collection mock
        $this->dueDateLogCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->dueDateLogCollectionFactory
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->getOrderByIncrementId = $this->createMock(GetOrderByIncrementIdInterface::class);
        $this->dueDateLogRepository = $this->createMock(DueDateLogRepositoryInterface::class);
        $this->productBundleConfig = $this->createMock(ConfigInterface::class);
        $this->inStoreConfigInterface = $this->createMock(InStoreConfigInterface::class);

        $this->orderDetailsDataMapper = new OrderDetailsDataMapper(
            $this->configInterfaceMock,
            $this->shipmentHelperMock,
            $this->resultJsonFactoryMock,
            $this->resultPageFactoryMock,
            $this->searchCriteriaBuilderMock,
            $this->orderRepositoryMock,
            $this->imageHelperMock,
            $this->productFactoryMock,
            $this->priceHelperMock,
            $this->miraklHelperMock,
            $this->toggleConfig,
            $this->shopManagement,
            $this->shippingRepositoryInterface,
            $this->creditMemoRepository,
            $this->shipmentApi,
            $this->marketplaceCheckoutHelper,
            $this->dueDateLogCollectionFactory,
            $this->getOrderByIncrementId,
            $this->dueDateLogRepository,
            $this->productBundleConfig,
            $this->inStoreConfigInterface
        );
    }

    /**
     * Test getShipmentType method.
     *
     * @return void
     */
    public function testGetShipmentType()
    {
        $orderDataMock = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getShippingMethod'])
            ->getMockForAbstractClass();

        $orderDataMock->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');

        $orderDataMock->expects($this->atMost(2))
            ->method('getShippingDescription')
            ->willReturn('FedEx 2 Day - Estimated Delivery: Aug 21, 2023 10:00 AM');

        $this->toggleConfig->expects($this->atMost(2))
            ->method('getToggleConfigValue')
            ->withConsecutive([OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE])
            ->willReturn(true);

        $result = $this->orderDetailsDataMapper->getShipmentType($orderDataMock);

        $this->assertSame(['In-store pickup (FedEx Office)', 'pickup', 'FedEx 2 Day'], $result);
    }

    /**
     * Test getShipmentType method.
     *
     * @return void
     */
    public function testGetShipmentTypeImprovementToggleOnPickup()
    {
        $this->toggleConfig->expects($this->atMost(2))
            ->method('getToggleConfigValue')
            ->withConsecutive([OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE])
            ->willReturn(true);

        $orderDataMock = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getShippingMethod'])
            ->getMockForAbstractClass();

        $orderDataMock->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');

        $result = $this->orderDetailsDataMapper->getShipmentType($orderDataMock);

        $this->assertSame(['In-store pickup (FedEx Office)', 'pickup', ''], $result);
    }

    /**
     * Test getShipmentType method.
     *
     * @return void
     */
    public function testGetShipmentTypeImprovementToggleOnShipping()
    {
        $this->toggleConfig->expects($this->atMost(2))
            ->method('getToggleConfigValue')
            ->withConsecutive([OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE])
            ->willReturn(true);

        $orderDataMock = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getShippingMethod'])
            ->getMockForAbstractClass();

        $orderDataMock->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping');

        $result = $this->orderDetailsDataMapper->getShipmentType($orderDataMock);

        $this->assertSame(['Shipment (FedEx Office)', 'shipment', ''], $result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeadingEmptyToggleOn()
    {
        $orderData = [
            0 => [
                'order_status' => '',
                'shipment_type' => ''
            ]
        ];
        $key = 0;

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $result = $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
        $this->assertNull($result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeadingCanceled()
    {
        $orderData = [
            0 => [
                'order_status' => 'canceled',
                'shipment_type' => 'pickup'
            ]
        ];
        $key = 0;

        $result = $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
        $this->assertEquals(OrderDetailsDataMapper::STATUS_CANCELED, $result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeadingCanceledToggleOn()
    {
        $orderData = [
            0 => [
                'order_status' => 'canceled',
                'shipment_type' => 'pickup'
            ]
        ];
        $key = 0;

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $result = $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
        $this->assertEquals(OrderDetailsDataMapper::STATUS_CANCELED, $result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeadingDelivery()
    {
        $orderData = [
            0 => [
                'order_status' => 'new',
                'shipment_type' => 'pickup'
            ]
        ];
        $key = 0;

        $result = $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
        $this->assertEquals(OrderDetailsDataMapper::DISPLAY_STATUS_EXPECTED_DELIVERY, $result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeadingDeliveryToggleOn()
    {
        $orderData = [
            0 => [
                'order_status' => 'new',
                'shipment_type' => 'pickup'
            ]
        ];
        $key = 0;

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $result = $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
        $this->assertEquals(OrderDetailsDataMapper::DISPLAY_STATUS_EXPECTED_DELIVERY, $result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeadingExpectedAvailabilityToggleOn()
    {
        $orderData = [
            0 => [
                'order_status' => 'new',
                'shipment_type' => [1 => 'pickup']
            ]
        ];
        $key = 0;

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $result = $this->orderDetailsDataMapper->getOrderStatusHeading($orderData, $key);
        $this->assertEquals(OrderDetailsDataMapper::DISPLAY_STATUS_EXPECTED_AVAILABILITY, $result);
    }

    /**
     * Test getItemThumbnailUrl method.
     *
     * @return void
     */
    public function testGetItemThumbnailUrl()
    {
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(['getSmallImage', 'setImageHelper'])
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->method('getSmallImage')->willReturn('product_image.jpg');

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($productMock, 'product_page_image_small')
            ->willReturnSelf();

        $this->imageHelperMock->expects($this->once())
            ->method('setImageFile')
            ->with('product_image.jpg')
            ->willReturnSelf();

        $this->imageHelperMock->expects($this->once())
            ->method('keepFrame')
            ->with(false)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('resize')
            ->with(140, 160)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('getUrl')
            ->willReturn('https://example.com/product_image.jpg');

        $result = $this->orderDetailsDataMapper->getItemThumbnailUrl($productMock);

        $this->assertSame('https://example.com/product_image.jpg', $result);
    }

    /**
     * Test getPickupAddress method.
     *
     * @return void
     */
    public function testGetPickupAddress()
    {
        $shippingAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $shippingAddressMock->method('getStreet')->willReturn(['123 Main St']);
        $shippingAddressMock->method('getCity')->willReturn('City');
        $shippingAddressMock->method('getRegion')->willReturn('Region');
        $shippingAddressMock->method('getPostcode')->willReturn('12345');

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getShippingAddress')->willReturn($shippingAddressMock);

        $result = $this->orderDetailsDataMapper->getPickupAddress($orderMock);

        $this->assertSame('123 Main St, City, Region 12345', $result);
    }

    /**
     * Test getPickupAddress method.
     *
     * @return void
     */
    public function testGetPickupAddressToggleOn()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $shippingAddressMock = $this->createMock(\Magento\Sales\Model\Order\Address::class);
        $shippingAddressMock->method('getStreet')->willReturn(['123 Main St']);
        $shippingAddressMock->method('getCity')->willReturn('City');
        $shippingAddressMock->method('getRegion')->willReturn('Region');
        $shippingAddressMock->method('getPostcode')->willReturn('12345');

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getShippingAddress')->willReturn($shippingAddressMock);

        $result = $this->orderDetailsDataMapper->getPickupAddress($orderMock);

        $this->assertSame('123 Main St, City, Region 12345', $result);
    }

    /**
     * Test getTrackingNumber method.
     *
     * @return void
     */
    public function testGetTrackingNumber()
    {
        $trackMock = $this->getMockBuilder(Track::class)
            ->setMethods(['count','getData','getTrackNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $trackMock->method('getTrackNumber')
            ->willReturn('123456789');

        $trackMock->method('count')
            ->willReturn(1);

        $trackCollectionMock = $this->getMockBuilder(ShipmentTrackCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trackCollectionMock->method('getFirstItem')
            ->willReturn($trackMock);

        $shipmentMock = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shipmentMock->method('getTracks')
            ->willReturn([$trackMock]);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->method('getTracksCollection')
            ->willReturn($trackCollectionMock);

        $this->orderDetailsDataMapper->getTrackingNumber($orderMock);
    }

    public function testGetCanceledDateWithImprovedLogicCanceled()
    {
        $updatedAt = '2023-08-15 10:00:00';
        $expectedDate = ['Tuesday', 'Aug 15, 2023', '10:00am'];

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->method('getState')->willReturn(Order::STATE_CANCELED);
        $orderMock->method('getUpdatedAt')->willReturn($updatedAt);

        $result = $this->orderDetailsDataMapper->getCanceledDate($orderMock);
        $this->assertSame($expectedDate, $result);
    }

    public function testGetCanceledDateWithImprovedLogicNotCanceled()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->method('getState')->willReturn(Order::STATE_PROCESSING);

        $result = $this->orderDetailsDataMapper->getCanceledDate($orderMock);
        $this->assertNull($result);
    }

    /**
     * Test getCanceledDate method.
     *
     * @return void
     */
    public function testGetCanceledDate()
    {
        $updatedAt = '2023-08-15 10:00:00';
        $expectedDate = [
            0 => 'Tuesday',
            1 => 'Aug 15, 2023',
            2 => '10:00am'
        ];

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->method('getState')
            ->willReturn(Order::STATE_CANCELED);
        $orderMock->method('getUpdatedAt')
            ->willReturn($updatedAt);

        $result = $this->orderDetailsDataMapper->getCanceledDate($orderMock);
        $this->assertSame($expectedDate, $result);
    }

    /**
     * Test isOrderDelayed method.
     *
     * @return void
     */
    public function testIsOrderDelayedTrue()
    {
        $orderData = [
            'some_key' => [
                'delivery_date' => [null, '2021-01-01'],
            ],
        ];

        $result = $this->orderDetailsDataMapper->isOrderDelayed($orderData, 'some_key');

        $this->assertTrue($result);
    }

    /**
     * Test isMktOrderDelayed method.
     *
     * @return void
     */
    public function testIsMktOrderDelayed()
    {
        $orderData = [
            'some_key' => [
                'orderMktItems' => [
                    [
                        'delivery_date' => [null, '2021-01-01'],
                    ],
                ],
            ],
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayed($orderData, 'some_key');

        $this->assertTrue($result);
    }

    /**
     * Test isMktOrderDelayed method.
     *
     * @return void
     */
    public function testIsMktOrderDelayedCodeImprovementLogicOn()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $orderData = [
            'some_key' => [
                'orderMktItems' => [
                    2022 => [
                        'delivery_date' => [null, '2021-01-01']
                    ],
                ],
            ],
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayed($orderData, 'some_key', 2022);

        $this->assertFalse($result);
    }

    public function testIsMktOrderDelayedEnhancementTrue()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $currentDate = date('Y-m-d', strtotime('+1 day'));
        $orderData = [
            'key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, $currentDate]
                    ]
                ]
            ]
        ];
        $result = $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($orderData, 'key', 1);
        $this->assertTrue($result);
    }

    public function testIsMktOrderDelayedEnhancementNoDeliveryDate()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $orderData = [
            'key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, null]
                    ]
                ]
            ]
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($orderData, 'key', 1);
        $this->assertFalse($result);
    }

    public function testIsMktOrderDelayedEnhancement()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementDisabled'
                    ]
                )
            );
        $currentDate = date('Y-m-d', strtotime('+1 day'));
        $orderData = [
            'key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, $currentDate]
                    ]
                ]
            ]
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($orderData, 'key', 1);
        $this->assertFalse($result);
    }

    public function testIsMktOrderDelayedEnhancementHigherCurrentDate()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementDisabled'
                    ]
                )
            );

        $orderData = [
            'key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, '2024-08-27']
                    ]
                ]
            ]
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($orderData, 'key', 1);
        $this->assertFalse($result);
    }

    public function testIsMktOrderDelayedEnhancementHigherCurrentDateEssendantToggle()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $orderData = [
            'key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, '2024-08-27']
                    ]
                ]
            ]
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($orderData, 'key', 1);
        $this->assertTrue($result);
    }

    public function testIsMktOrderDelayedEnhancementNoDelayEssendantToggle()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $this->marketplaceCheckoutHelper->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);

        $currentDate = date('Y-m-d', strtotime('+1 day'));
        $orderData = [
            'key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, $currentDate]
                    ]
                ]
            ]
        ];

        $result = $this->orderDetailsDataMapper->isMktOrderDelayedEnhancement($orderData, 'key', 1);
        $this->assertFalse($result);
    }

    /**
     * Test getMktOrderStatusDetailEnhancement method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailEnhancementEnhancementOrdered()
    {
        $orderData = [
            'some_key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, '2024-08-27']
                    ]
                ]
            ],
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $miraklOrderStatus = $this->getMockBuilder(OrderStatus::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrderStatus->expects($this->once())
            ->method('getState')
            ->willReturn('ordered');

        $miraklOrder = $this->getMockBuilder(MiraklOrder::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrder->expects($this->once())
            ->method('getStatus')
            ->willReturn($miraklOrderStatus);

        $miraklOrderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data =[$miraklOrder];
        $iterator = new \ArrayIterator($data);
        $miraklOrderCollection->expects(
                $this->any()
            )->method(
                'getIterator'
            )->willReturn(
                $iterator
            );

        $this->miraklHelperMock->expects($this->once())
            ->method('getOrders')
            ->with(['commercial_ids' => 'some_key', 'shop_ids' => 1, 'offer_ids' => null])
            ->willReturn($miraklOrderCollection);

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetailEnhancement($orderData, 'some_key', 1);

        $this->assertEquals(OrderDetailsDataMapper::STATUS_ORDERED, $result);
    }

    /**
     * Test getMktOrderStatusDetailEnhancement method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailEnhancementCanceled()
    {
        $orderData = [
            'some_key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, '2024-08-27']
                    ]
                ]
            ],
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $miraklOrderStatus = $this->getMockBuilder(OrderStatus::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrderStatus->expects($this->once())
            ->method('getState')
            ->willReturn('canceled');

        $miraklOrder = $this->getMockBuilder(MiraklOrder::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrder->expects($this->once())
            ->method('getStatus')
            ->willReturn($miraklOrderStatus);

        $miraklOrderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data =[$miraklOrder];
        $iterator = new \ArrayIterator($data);
        $miraklOrderCollection->expects(
            $this->any()
        )->method(
            'getIterator'
        )->willReturn(
            $iterator
        );

        $this->miraklHelperMock->expects($this->once())
            ->method('getOrders')
            ->with(['commercial_ids' => 'some_key', 'shop_ids' => 1, 'offer_ids' => null])
            ->willReturn($miraklOrderCollection);

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetailEnhancement($orderData, 'some_key', 1);

        $this->assertEquals(OrderDetailsDataMapper::STATUS_CANCELED, $result);
    }

    /**
     * Test getMktOrderStatusDetailEnhancement method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailEnhancementDelivered()
    {
        $orderData = [
            'some_key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, '2024-08-27']
                    ]
                ]
            ],
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $miraklOrderStatus = $this->getMockBuilder(OrderStatus::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrderStatus->expects($this->once())
            ->method('getState')
            ->willReturn('shipped');

        $miraklOrder = $this->getMockBuilder(MiraklOrder::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrder->expects($this->once())
            ->method('getStatus')
            ->willReturn($miraklOrderStatus);

        $miraklOrderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data =[$miraklOrder];
        $iterator = new \ArrayIterator($data);
        $miraklOrderCollection->expects(
                $this->any()
            )->method(
                'getIterator'
            )->willReturn(
                $iterator
            );

        $this->miraklHelperMock->expects($this->once())
            ->method('getOrders')
            ->with(['commercial_ids' => 'some_key', 'shop_ids' => 1, 'offer_ids' => null])
            ->willReturn($miraklOrderCollection);

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetailEnhancement($orderData, 'some_key', 1);

        $this->assertEquals(OrderDetailsDataMapper::STATUS_SHIPPED, $result);
    }

    /**
     * Test getMktOrderStatusDetailEnhancement method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailEnhancementNew()
    {
        $orderData = [
            'some_key' => [
                'orderMktItems' => [
                    1 => [
                        'delivery_date' => [null, '2024-08-27']
                    ]
                ]
            ],
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $miraklOrderStatus = $this->getMockBuilder(OrderStatus::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrderStatus->expects($this->once())
            ->method('getState')
            ->willReturn('processing');

        $miraklOrder = $this->getMockBuilder(MiraklOrder::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrder->expects($this->once())
            ->method('getStatus')
            ->willReturn($miraklOrderStatus);

        $miraklOrderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data =[$miraklOrder];
        $iterator = new \ArrayIterator($data);
        $miraklOrderCollection->expects(
                $this->any()
            )->method(
                'getIterator'
            )->willReturn(
                $iterator
            );

        $this->miraklHelperMock->expects($this->once())
            ->method('getOrders')
            ->with(['commercial_ids' => 'some_key', 'shop_ids' => 1, 'offer_ids' => null])
            ->willReturn($miraklOrderCollection);

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetailEnhancement($orderData, 'some_key', 1);

        $this->assertEquals(OrderDetailsDataMapper::STATUS_ORDERED, $result);
    }

    /**
     * Test getOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetOrderStatusDetailOrdered()
    {
        $orderData = [
            'some_key' => [
                'order_status' => 'new',
                'delivery_date' => [null, '2023-01-01']
            ],
        ];

        $result = $this->orderDetailsDataMapper->getOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_ORDERED, $result);
    }

    /**
     * Test getOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetOrderStatusDetailCanceled()
    {
        $orderData = [
            'some_key' => [
                'order_status' => 'canceled'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_CANCELED, $result);
    }

    /**
     * Test getOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetOrderStatusDetailDelivered()
    {
        $orderData = [
            'some_key' => [
                'order_status' => 'delivered'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_SHIPPED, $result);
    }

    /**
     * Test getOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetOrderStatusDetailPickup()
    {
        $orderData = [
            'some_key' => [
                'order_status' => 'ready_for_pickup'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_READY_FOR_PICKUP, $result);
    }

    /**
     * Test getOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetOrderStatusDetailNew()
    {
        $orderData = [
            'some_key' => [
                'order_status' => 'processing'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_PROCESSING, $result);
    }

    /**
     * Test getMktOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailOrdered()
    {
        $orderData = [
            'some_key' => [
                'mkt_order_status' => 'waiting_acceptance',
            ],
        ];

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_ORDERED, $result);
    }

    /**
     * Test getMktOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailCanceled()
    {
        $orderData = [
            'some_key' => [
                'mkt_order_status' => 'canceled'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_CANCELED, $result);
    }

    /**
     * Test getMktOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailDelivered()
    {
        $orderData = [
            'some_key' => [
                'mkt_order_status' => 'received'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_SHIPPED, $result);
    }

    /**
     * Test getMktOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetMktOrderStatusDetailNew()
    {
        $orderData = [
            'some_key' => [
                'mkt_order_status' => 'shipping'
            ],
        ];

        $result = $this->orderDetailsDataMapper->getMktOrderStatusDetail($orderData, 'some_key');

        $this->assertEquals(OrderDetailsDataMapper::STATUS_PROCESSING, $result);
    }

    /**
     * Test getTrackOrderUrl method.
     *
     * @return void
     */
    public function testGetTrackOrderUrl()
    {
        $expectedUrl = 'http://example.com/track_order';
        $this->configInterfaceMock ->expects($this->once())
            ->method('getValue')
            ->with('fedex/general/track_order_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedUrl);

        $trackOrderUrl = $this->orderDetailsDataMapper->getTrackOrderUrl();
        $this->assertEquals($expectedUrl, $trackOrderUrl);
    }

    /**
     * Test getLegacyTrackOrderUrl method.
     *
     * @return void
     */
    public function testGetLegacyTrackOrderUrl()
    {
        $expectedLegacyTrackOrderUrl = 'http://example.com/legacy-track-order';

        $this->configInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with('fedex/general/legacy_track_order_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedLegacyTrackOrderUrl);

        $legacyTrackOrderUrl = $this->orderDetailsDataMapper->getLegacyTrackOrderUrl();
        $this->assertSame($expectedLegacyTrackOrderUrl, $legacyTrackOrderUrl);
    }

    /**
     * Test getShipmentTypeFromMktItem method.
     *
     * @return void
     */
    public function testGetShipmentTypeFromMktItem()
    {
        $expectedShipmentTitle = 'Expected Shipment Title';
        $expectedShipmentValue = 'shipment';

        $mockMiraklItem = [
            'mirakl_shipping_data' => [
                'title' => $expectedShipmentTitle,
            ],
        ];

        $mockItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockItem->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn(json_encode($mockMiraklItem));

        $shipmentType = $this->orderDetailsDataMapper->getShipmentTypeFromMktItem($mockItem);
        $this->assertSame([$expectedShipmentTitle, $expectedShipmentValue], $shipmentType);
    }

    public function testGetExpectedDeliveryDateWithToggleOn()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $mockOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipment = $this->getMockBuilder(Shipment::class)
            ->setMethods(['getOrderCompletionDate','getId','getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipmentCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockOrder->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $mockOrder->method('getShipmentsCollection')->willReturn($mockShipmentCollection);
        $mockShipmentCollection->method('getFirstItem')->willReturn($mockShipment);
        $mockShipment->method('getId')->willReturn(1);

        $this->shipmentHelperMock->method('getShipmentById')->willReturn($mockShipment);
        $mockShipment->method('getOrderCompletionDate')->willReturn('2023-05-15 14:30:00');

        $result = $this->orderDetailsDataMapper->getExpectedDeliveryDate($mockOrder);
        $year = date('Y');
        $this->assertEquals(['Monday','May 15, '.$year, '2:30pm '], $result);
    }

    public function testGetExpectedDeliveryDateWithToggleOnAndNonPickupMethod()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(true);

        $mockOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockOrder->method('getShippingMethod')->willReturn('fedexshipping');
        $mockOrder->method('getShippingDescription')->willReturn('FedEx Local Delivery - Thursday, August 8, 5:00pm');

        $result = $this->orderDetailsDataMapper->getExpectedDeliveryDate($mockOrder);
        $year = date('Y');
        $this->assertEquals(['Thursday','August 8, '.$year, '5:00pm '], $result);
    }

    public function testGetExpectedDeliveryDateWithToggleOff()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(false);

        $mockOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipment = $this->getMockBuilder(Shipment::class)
            ->setMethods(['getOrderCompletionDate','getId','getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipmentCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockOrder->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $mockOrder->method('getShipmentsCollection')->willReturn($mockShipmentCollection);
        $mockShipmentCollection->method('getFirstItem')->willReturn($mockShipment);
        $mockShipment->method('getId')->willReturn(1);

        $this->shipmentHelperMock->method('getShipmentById')->willReturn($mockShipment);
        $mockShipment->method('getOrderCompletionDate')->willReturn('2023-05-15 14:30:00');

        $result = $this->orderDetailsDataMapper->getExpectedDeliveryDate($mockOrder);
        $year = date('Y');
        $this->assertEquals(['Monday','May 15, '.$year,'2:30pm '], $result);
    }

    public function testGetExpectedDeliveryDateWithToggleOffAndNonPickupMethod()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE)
            ->willReturn(false);

        $mockOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockOrder->method('getShippingMethod')->willReturn('fedexshipping');
        $mockOrder->method('getShippingDescription')->willReturn('FedEx Local Delivery - Thursday, August 8, 5:00pm');

        $result = $this->orderDetailsDataMapper->getExpectedDeliveryDate($mockOrder);
        $year = date('Y');
        $this->assertEquals(['Thursday','August 8, '.$year, '5:00pm '], $result);
    }

    /**
     * Test getShipmentTypeFromMktItem method.
     *
     * @return void
     */
    public function testGetExpectedDeliveryDateWithShipmentPickup()
    {
        $expectedDate = [
            0 => 'Monday',
            1 => 'August 21, 2023',
            2 => '10:00am ',
        ];

        $mockOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipment = $this->getMockBuilder(Shipment::class)
            ->setMethods(['getOrderCompletionDate','getId','getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipmentCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockOrder->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');

        $mockOrder->method('getShipmentsCollection')
            ->willReturn($mockShipmentCollection);

        $mockShipmentCollection->method('getFirstItem')
            ->willReturn($mockShipment);

        $mockShipment->method('getId')
            ->willReturn(1);

        $mockShipment->method('getOrder')
            ->willReturn($mockOrder);

        $mockShipment->method('getOrderCompletionDate')
            ->willReturn('2023-08-21 10:00:00');

        $this->configInterfaceMock->method('getValue')
            ->willReturn('https://example.com/track_order');

        $this->shipmentHelperMock->method('getShipmentById')
            ->willReturn($mockShipment);

        $deliveryDate = $this->orderDetailsDataMapper->getExpectedDeliveryDate($mockOrder);
        //$this->assertEquals($expectedDate, $deliveryDate);
        $this->assertNotNull($deliveryDate);
    }

    /**
     * Test getShipmentTypeFromMktItem method.
     *
     * @return void
     */
    public function testGetExpectedDeliveryDateWithShipmentDelivery()
    {
        $expectedDate = [
            0 => 'Monday',
            1 => 'Aug 21, 2023',
            2 => '10:00am'
        ];

        $mockOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipment = $this->getMockBuilder(Shipment::class)
            ->setMethods(['getOrderCompletionDate','getId','getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockShipmentCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockShippingAddress = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->setMethods(['getStreet','getCity','getRegion','getPostcode'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockShippingAddress->method('getStreet')->willReturn(['123 Main St']);
        $mockShippingAddress->method('getCity')->willReturn('New York');
        $mockShippingAddress->method('getRegion')->willReturn('NY');
        $mockShippingAddress->method('getPostcode')->willReturn('10001');

        $mockShippingDescription = 'In-store delivery (FedEx Office) - Estimated Delivery: Aug 21, 2023 10:00 AM';
        $mockOrder->method('getShippingDescription')->willReturn($mockShippingDescription);

        $mockOrder->method('getShippingAddress')->willReturn($mockShippingAddress);

        $mockOrder->method('getShippingMethod')
            ->willReturn('fedexshipping_DELIVERY');

        $mockOrder->method('getShipmentsCollection')
            ->willReturn($mockShipmentCollection);

        $mockShipmentCollection->method('getFirstItem')
            ->willReturn($mockShipment);

        $mockShipment->method('getId')
            ->willReturn(1);

        $mockShipment->method('getOrder')
            ->willReturn($mockOrder);

        $mockShipment->method('getOrderCompletionDate')
            ->willReturn('2023-08-21 10:00:00');

        $this->configInterfaceMock->method('getValue')
            ->willReturn('https://example.com/track_order');

        $this->shipmentHelperMock->method('getShipmentById')
            ->willReturn($mockShipment);

        $this->orderDetailsDataMapper->getExpectedDeliveryDate($mockOrder);
    }

    public function testGetOrderlist() {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilderMock->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);

        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getIncrementId'])
            ->addMethods(['getAllVisibleItems', 'getAllItems', 'getShippingMethod', 'getShippingAddress', 'getTracksCollection','hasShipments'])
            ->getMockForAbstractClass();
        $orderItem1 = $this->createMock(Item::class);
        $orderItem1->expects($this->once())->method('getPrice')->willReturn(100);
        $orderItem2 = $this->createMock(Item::class);
        $orderItem2->expects($this->once())->method('getProductType')->willReturn('bundle');
        $orderItem2->expects($this->once())->method('getPrice')->willReturn(100);
        $child = $this->createMock(Item::class);
        $productChild = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(['getSmallImage', 'setImageHelper'])
            ->disableOriginalConstructor()
            ->getMock();
        $productChild->method('getSmallImage')->willReturn('product_image.jpg');

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($productChild, 'product_page_image_small')
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('setImageFile')
            ->with('product_image.jpg')
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('keepFrame')
            ->with(false)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('resize')
            ->with(140, 160)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('getUrl')
            ->willReturn('https://example.com/product_image.jpg');

        $child->expects($this->once())->method('getProduct')->willReturn($productChild);
        $child->expects($this->once())->method('getName')->willReturn('Child Item Name');
        $child->expects($this->once())->method('getSku')->willReturn('child-sku');
        $child->expects($this->once())->method('getQtyOrdered')->willReturn(5);
        $child->expects($this->once())->method('getPrice')->willReturn(100);
        $this->priceHelperMock->expects($this->atMost(3))
            ->method('currency')
            ->with(100, true, false)
            ->willReturn('$100.00');
        $orderItem2->expects($this->once())->method('getChildrenItems')->willReturn([$child]);
        $shippingAddress = $this->createMock(OrderAddressInterface::class);
        $trackCollection = $this->getMockBuilder(ShipmentTrackCollection::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTrackNumber'])
            ->onlyMethods(['count', 'getFirstItem'])
            ->getMockForAbstractClass();

        $order->expects($this->once())->method('getCreatedAt')->willReturn('2025-06-18 05:57:21');
        $order->expects($this->any())->method('getShippingMethod')->willReturn('freeshipping_freeshipping');
        $shippingAddress->expects($this->once())->method('getStreet')->willReturn(['line 1', 'line 2']);
        $order->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddress);
        $order->expects($this->any())->method('getShippingDescription')->willReturn('some-description');
        $trackCollection->expects($this->once())->method('count')->willReturn(5);
        $trackCollection->expects($this->once())->method('getFirstItem')->willReturnSelf();
        $trackCollection->expects($this->once())->method('getTrackNumber')->willReturn('tracking-number');

        $this->productBundleConfig->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);
        $order->expects($this->once())->method('getAllVisibleItems')->willReturn([$orderItem1, $orderItem2]);
        $order->expects($this->any())->method('getTracksCollection')->willReturn($trackCollection);
        $order->expects($this->exactly(5))->method('getIncrementId')->willReturn('2010580712450580');

        $orderSearchResult = $this->createMock(\Magento\Sales\Api\Data\OrderSearchResultInterface::class);
        $orderSearchResult->expects($this->once())->method('getTotalCount')->willReturn(2);
        $orderSearchResult->expects($this->once())->method('getItems')->willReturn([$order]);
        $this->orderRepositoryMock->expects($this->once())->method('getList')->willReturn($orderSearchResult);

        $orderIds = ['2010580712450580', '2010636202486866'];
        $this->orderDetailsDataMapper->getOrderlist($orderIds);

    }

    public function testGetExtendedDeliveryDateReturnsEmptyArrayIfNoDate()
    {
        $mockItem = $this->createMock(Item::class);

        $miraklOrderData = [
            "order_additional_fields" => [
                "items" => [
                    [
                        "code" => "extd-del-date",
                        "value" => "2023-01-01"
                    ]
                ]
            ]
        ];

        $mockItem->expects($this->any())
            ->method('getData')
            ->willReturn($miraklOrderData);

        $mockOrderAdditionalFields = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();

        $mockOrderAdditionalFields->expects($this->any())
            ->method('getItems')
            ->willReturn([$mockItem]);

        $miraklOrderData = [
            'order_additional_fields' => $mockOrderAdditionalFields,
        ];

        $result = $this->orderDetailsDataMapper->getExtendedDeliveryDate($miraklOrderData);

        $this->assertEquals([], $result);
    }

    public function testGetOrderlistModifyOrderTrackingLogicOn() {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementDisabled'
                    ]
                )
            );
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilderMock->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);

        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt', 'getIncrementId'])
            ->addMethods(['getAllItems', 'getShippingMethod', 'getShippingAddress', 'getTracksCollection', 'hasShipments'])
            ->getMockForAbstractClass();
        $orderItem1 = $this->createMock(Item::class);
        $orderItem2 = $this->createMock(Item::class);
        $shippingAddress = $this->createMock(OrderAddressInterface::class);
        $trackCollection = $this->getMockBuilder(ShipmentTrackCollection::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTrackNumber'])
            ->onlyMethods(['count', 'getFirstItem'])
            ->getMockForAbstractClass();

        $order->expects($this->once())->method('getCreatedAt')->willReturn('2025-06-18 05:57:21');
        $order->expects($this->any())->method('getShippingMethod')->willReturn('freeshipping_freeshipping');
        $shippingAddress->expects($this->once())->method('getStreet')->willReturn(['line 1', 'line 2']);
        $order->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddress);
        $order->expects($this->any())->method('getShippingDescription')->willReturn('some-description');
        $order->expects($this->any())->method('getIncrementId')->willReturn('2010580712450580');
        $trackCollection->expects($this->once())->method('count')->willReturn(5);
        $trackCollection->expects($this->once())->method('getFirstItem')->willReturnSelf();
        $trackCollection->expects($this->once())->method('getTrackNumber')->willReturn('tracking-number');

        $order->expects($this->once())->method('getAllItems')->willReturn([$orderItem1, $orderItem2]);
        $order->expects($this->any())->method('getTracksCollection')->willReturn($trackCollection);

        $orderSearchResult = $this->createMock(\Magento\Sales\Api\Data\OrderSearchResultInterface::class);
        $orderSearchResult->expects($this->once())->method('getTotalCount')->willReturn(2);
        $orderSearchResult->expects($this->once())->method('getItems')->willReturn([$order]);
        $this->orderRepositoryMock->expects($this->once())->method('getList')->willReturn($orderSearchResult);

        $miraklOrderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->miraklHelperMock->expects($this->once())
            ->method('getOrders')
            ->with(['commercial_ids' => '2010580712450580', 'shop_ids' => null, 'offer_ids' => null])
            ->willReturn($miraklOrderCollection);

        $orderIds = ['2010580712450580', '2010636202486866'];
        $this->orderDetailsDataMapper->getOrderlist($orderIds);

    }

    public function testGetOrderlistMiraklOffer() {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilderMock->expects($this->once())->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);

        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCreatedAt'])
            ->addMethods(['getAllItems', 'getShippingMethod', 'getShippingAddress', 'getTracksCollection', 'hasShipments', 'getId'])
            ->getMockForAbstractClass();

        $orderItem1 = $this->createMock(Item::class);
        $orderItem2 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProduct', 'getId', 'getPrice', 'getName', 'getSku', 'getQtyOrdered', 'getAdditionalData'])
            ->addMethods(['getMiraklOfferId', 'getMiraklShopId', 'getSellerId'])
            ->getMock();
        $orderItem2->expects($this->any())
            ->method('getId')
            ->willReturn(10);
        $orderItem2->expects($this->any())
            ->method('getPrice')
            ->willReturn(500);
        $orderItem2->expects($this->any())
            ->method('getName')
            ->willReturn('Product Name');
        $orderItem2->expects($this->any())
            ->method('getSku')
            ->willReturn('sku-123');
        $orderItem2->expects($this->any())
            ->method('getMiraklOfferId')
            ->willReturn(9999);
        $orderItem2->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn(2002);
        $orderItem2->expects($this->any())
            ->method('getSellerId')
            ->willReturn(2002);
        $orderItem2->expects($this->any())
            ->method('getQtyOrdered')
            ->willReturn(100);
        $orderItem2->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn('{"mirakl_shipping_data": {"title": "shipment","method_title": "fedexshipping"}}');
        $productMock = $this->createMock(Product::class);
        $orderItem2->expects($this->any())
            ->method('getProduct')
            ->willReturn($productMock);

        $trackMock = $this->getMockBuilder(Track::class)
            ->setMethods(['count','getData','getTrackNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $trackMock->method('getData')
            ->with('track_number')
            ->willReturn('123456789');
        $trackMock->method('count')
            ->willReturn(1);

        $shipmentMock = $this->getMockBuilder(Shipment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shipmentMock->method('getTracks')
            ->willReturn([$trackMock]);
        $shipmentMock->method('getShipmentStatus')
            ->willReturn('delivered');

        $this->shipmentHelperMock->expects($this->once())
            ->method('getShippingByOrderAndItemId')
            ->with($order, 10)
            ->willReturn($shipmentMock);

        $this->shipmentHelperMock->expects($this->once())
            ->method('getDeliveryDateFromMktItem')
            ->with($orderItem2)
            ->willReturn('2023-08-21 10:00:00');

        $this->shipmentHelperMock->expects($this->once())
            ->method('getShipmentStatusByValue')
            ->with('delivered')
            ->willReturn('9');

        $this->priceHelperMock->expects($this->exactly(2))
            ->method('currency')
            ->withConsecutive([null, true, false], [500, true, false])
            ->willReturnOnConsecutiveCalls(
                '<div class="price-container">100</div>',
                '<div class="price-container">500</div>'
            );

        $shopInterfaceMock = $this->getMockBuilder(ShopInterface::class)
            ->addMethods(['getSellerAltName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shopInterfaceMock->method('getSellerAltName')
            ->willReturn('seller-alt-name');
        $this->shopManagement->expects($this->once())
            ->method('getShopByProduct')
            ->with($productMock)
            ->willReturn($shopInterfaceMock);

        $expectedUrl = 'http://example.com/track_order';
        $this->configInterfaceMock ->expects($this->any())
            ->method('getValue')
            ->with('fedex/general/track_order_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedUrl);

        $this->imageHelperMock->expects($this->any())
            ->method('init')
            ->with($productMock, 'product_page_image_small')
            ->willReturnSelf();

        $this->imageHelperMock->expects($this->any())
            ->method('setImageFile')
            ->with(null)
            ->willReturnSelf();

        $this->imageHelperMock->expects($this->any())
            ->method('keepFrame')
            ->with(false)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->any())
            ->method('resize')
            ->with(140, 160)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->any())
            ->method('getUrl')
            ->willReturn('https://example.com/product_image.jpg');

        $shippingAddress = $this->createMock(OrderAddressInterface::class);
        $trackCollection = $this->getMockBuilder(ShipmentTrackCollection::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTrackNumber'])
            ->onlyMethods(['count', 'getFirstItem'])
            ->getMockForAbstractClass();

        $order->expects($this->once())->method('getCreatedAt')->willReturn('2025-06-18 05:57:21');
        $order->expects($this->any())->method('getShippingMethod')->willReturn('freeshipping_freeshipping');
        $shippingAddress->expects($this->once())->method('getStreet')->willReturn(['line 1', 'line 2']);
        $order->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddress);
        $order->expects($this->any())->method('getShippingDescription')->willReturn('some-description');
        $order->expects($this->any())->method('getIncrementId')->willReturn('2010580712450580');
        $trackCollection->expects($this->once())->method('count')->willReturn(5);
        $trackCollection->expects($this->once())->method('getFirstItem')->willReturnSelf();
        $trackCollection->expects($this->once())->method('getTrackNumber')->willReturn('tracking-number');

        $order->expects($this->once())->method('getAllItems')->willReturn([$orderItem1, $orderItem2]);
        $order->expects($this->any())->method('getTracksCollection')->willReturn($trackCollection);

        $orderSearchResult = $this->createMock(\Magento\Sales\Api\Data\OrderSearchResultInterface::class);
        $orderSearchResult->expects($this->once())->method('getTotalCount')->willReturn(2);
        $orderSearchResult->expects($this->once())->method('getItems')->willReturn([$order]);
        $this->orderRepositoryMock->expects($this->once())->method('getList')->willReturn($orderSearchResult);

        $miraklOrderStatus = $this->getMockBuilder(OrderStatus::class)
            ->setMethods(['getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrderStatus->expects($this->any())
            ->method('getState')
            ->willReturn('ordered');

        $miraklOrder = $this->getMockBuilder(MiraklOrder::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrder->expects($this->any())
            ->method('getStatus')
            ->willReturn($miraklOrderStatus);

        $miraklOrderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $miraklOrderCollection->expects($this->any())
            ->method('getItems')
            ->willReturn([$miraklOrder]);

        $this->miraklHelperMock->expects($this->any())
            ->method('getOrders')
            ->with(['commercial_ids' => '2010580712450580', 'shop_ids' => null, 'offer_ids' => null])
            ->willReturn($miraklOrderCollection);

        $orderIds = ['2010580712450580', '2010636202486866'];
        $this->orderDetailsDataMapper->getOrderlist($orderIds);

    }

    public function testPopulateMissingShippingItemInfoWithModifiedLogic()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $mktOrderItemArray = [
            1 => [
                'items' => [
                    ['seller_id' => 100, 'shipment_type' => null, 'delivery_date' => null, 'status' => null],
                    ['seller_id' => 101, 'shipment_type' => 'express', 'delivery_date' => '2023-08-30', 'status' => 'shipped'],
                ]
            ],
            2 => [
                'items' => [
                    ['seller_id' => 102, 'shipment_type' => null, 'delivery_date' => null, 'status' => null],
                ]
            ]
        ];

        $mktShippingInfo = [
            100 => ['delivery_date' => '2023-09-01', 'shipment_type' => 'standard', 'status' => 'processing'],
            101 => ['delivery_date' => '2023-08-31', 'shipment_type' => 'priority', 'status' => 'in-transit'],
            102 => ['delivery_date' => '2023-09-02', 'shipment_type' => 'economy', 'status' => 'pending'],
        ];

        $reflection = new \ReflectionClass(OrderDetailsDataMapper::class);
        $populateMissingShippingItemInfo = $reflection->getMethod('populateMissingShippingItemInfo');
        $populateMissingShippingItemInfo->setAccessible(true);
        $result = $populateMissingShippingItemInfo->invoke($this->orderDetailsDataMapper, $mktOrderItemArray, $mktShippingInfo);

        $this->assertEquals('2023-09-01', $result[1]['items'][0]['delivery_date']);
        $this->assertEquals('standard', $result[1]['items'][0]['shipment_type']);
        $this->assertEquals('processing', $result[1]['items'][0]['status']);
        $this->assertEquals('express', $result[1]['items'][1]['shipment_type']);
        $this->assertEquals('2023-08-30', $result[1]['items'][1]['delivery_date']);
        $this->assertEquals('shipped', $result[1]['items'][1]['status']);
        $this->assertEquals('2023-09-02', $result[2]['items'][0]['delivery_date']);
        $this->assertEquals('economy', $result[2]['items'][0]['shipment_type']);
        $this->assertEquals('pending', $result[2]['items'][0]['status']);
    }

    public function testPopulateMissingShippingItemInfoWithModifiedLogicImprovementLogicOff()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementDisabled'
                    ]
                )
            );

        $mktOrderItemArray = [
            1 => [
                'items' => [
                    ['seller_id' => 100, 'shipment_type' => null, 'delivery_date' => null, 'status' => null],
                    ['seller_id' => 101, 'shipment_type' => 'express', 'delivery_date' => '2023-08-30', 'status' => 'shipped'],
                ]
            ],
            2 => [
                'items' => [
                    ['seller_id' => 102, 'shipment_type' => null, 'delivery_date' => null, 'status' => null],
                ]
            ]
        ];

        $mktShippingInfo = [
            100 => ['delivery_date' => '2023-09-01', 'shipment_type' => 'standard', 'status' => 'processing'],
            101 => ['delivery_date' => '2023-08-31', 'shipment_type' => 'priority', 'status' => 'in-transit'],
            102 => ['delivery_date' => '2023-09-02', 'shipment_type' => 'economy', 'status' => 'pending'],
        ];

        $reflection = new \ReflectionClass(OrderDetailsDataMapper::class);
        $populateMissingShippingItemInfo = $reflection->getMethod('populateMissingShippingItemInfo');
        $populateMissingShippingItemInfo->setAccessible(true);
        $result = $populateMissingShippingItemInfo->invoke($this->orderDetailsDataMapper, $mktOrderItemArray, $mktShippingInfo);

        $this->assertEquals('2023-09-02', $result['delivery_date']);
        $this->assertEquals('economy', $result['shipment_type']);
        $this->assertEquals('pending', $result['status']);
    }

    public function testPopulateMissingShippingItemInfoWithNoMissingInfo()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->will(
                $this->returnCallback(
                    [
                        $this,'returnCallbackModifyEnabledCodeImprovementEnabled'
                    ]
                )
            );

        $mktOrderItemArray = [
            1 => [
                'items' => [
                    ['seller_id' => 100, 'shipment_type' => 'express', 'delivery_date' => '2023-08-30', 'status' => 'shipped'],
                ]
            ]
        ];

        $mktShippingInfo = [
            100 => ['delivery_date' => '2023-09-01', 'shipment_type' => 'standard', 'status' => 'processing'],
        ];

        $reflection = new \ReflectionClass(OrderDetailsDataMapper::class);
        $populateMissingShippingItemInfo = $reflection->getMethod('populateMissingShippingItemInfo');
        $populateMissingShippingItemInfo->setAccessible(true);
        $result = $populateMissingShippingItemInfo->invoke($this->orderDetailsDataMapper, $mktOrderItemArray, $mktShippingInfo);

        $this->assertEquals($mktOrderItemArray, $result);
    }

    public function testPopulateMktItemDataEnhancement()
    {
        $mktOrderItemArray = [];
        $order = $this->createMock(Order::class);
        $item = $this->getMockBuilder(Item::class)
            ->addMethods(['getSellerId'])
            ->onlyMethods(['getPrice', 'getProduct', 'getName', 'getSku', 'getQtyOrdered', 'getId', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();;
        $miraklShopId = 123;
        $productObject = $this->getMockBuilder(Product::class)
            ->addMethods(['getSmallImage'])
            ->disableOriginalConstructor()
            ->getMock();

        $shipment = $this->createMock(Shipment::class);
        $track = $this->createMock(Track::class);
        $track->method('getData')->with('track_number')->willReturn('TRACK123');
        $shipment->method('getTracks')->willReturn([$track]);
        $shipment->method('getShipmentStatus')->willReturn(1);

        $this->shipmentHelperMock->method('getShippingByOrderAndItemId')->willReturn($shipment);
        $this->shipmentHelperMock->method('getDeliveryDateFromMktItem')->willReturn('2023-09-01');
        $this->shipmentHelperMock->method('getShipmentStatusByValue')->willReturn('Shipped');

        $this->priceHelperMock->method('currency')->willReturn('$10.00');

        $shop = $this->getMockBuilder(ShopInterface::class)
            ->addMethods(['getSellerAltName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $shop->method('getSellerAltName')->willReturn('Test Shop');
        $this->shopManagement->method('getShopByProduct')->willReturn($shop);

        $item->method('getId')->willReturn(1);
        $item->method('getPrice')->willReturn(10.00);
        $item->method('getProduct')->willReturn($productObject);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getSku')->willReturn('TEST-SKU');
        $item->method('getQtyOrdered')->willReturn(2);
        $item->method('getAdditionalData')
            ->willReturn('{"mirakl_shipping_data": {"title": "shipment","method_title": "fedexshipping"}}');

        $expectedUrl = 'http://example.com/track_order';
        $this->configInterfaceMock ->expects($this->once())
            ->method('getValue')
            ->with('fedex/general/track_order_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($expectedUrl);

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($productObject, 'product_page_image_small')
            ->willReturnSelf();
        $productObject->expects($this->once())
            ->method('getSmallImage')
            ->willReturn('product_image.jpg');
        $this->imageHelperMock->expects($this->once())
            ->method('setImageFile')
            ->with('product_image.jpg')
            ->willReturnSelf();

        $this->imageHelperMock->expects($this->once())
            ->method('keepFrame')
            ->with(false)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('resize')
            ->with(140, 160)
            ->willReturnSelf();
        $this->imageHelperMock->expects($this->once())
            ->method('getUrl')
            ->willReturn('https://example.com/product_image.jpg');

        // Call the method
        $result = $this->orderDetailsDataMapper->populateMktItemDataEnhancement($mktOrderItemArray, $order, $item, $miraklShopId, $productObject);

        // Assertions
        $this->assertArrayHasKey($miraklShopId, $result);
        $this->assertEquals('Test Shop', $result[$miraklShopId]['mirakl_shop_name']);
        $this->assertEquals('2023-09-01', $result[$miraklShopId]['delivery_date']);
        $this->assertEquals('http://example.com/track_order', $result[$miraklShopId]['track_order_url']);
        $this->assertEquals('TRACK123', $result[$miraklShopId]['tracking_number']);

        $this->assertCount(1, $result[$miraklShopId]['items']);
        $itemData = $result[$miraklShopId]['items'][0];
        $this->assertEquals('Test Product', $itemData['name']);
        $this->assertEquals('TEST-SKU', $itemData['sku']);
        $this->assertEquals('$10.00', $itemData['price']);
        $this->assertEquals('https://example.com/product_image.jpg', $itemData['imgurl']);
        $this->assertEquals(2, $itemData['qty']);
        $this->assertEquals('Shipped', $itemData['status']);
        $this->assertEquals(['shipment', 'shipment'], $itemData['shipment_type']);
        $this->assertEquals(123, $itemData['seller_id']);
        $this->assertEquals('fedexshipping', $itemData['mkt_shipping_method']);
    }

    public function returnCallbackModifyEnabledCodeImprovementDisabled()
    {
        $args = func_get_args();
        if ($args[0] == OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE) {
            return false;
        }
    }

    public function returnCallbackModifyEnabledCodeImprovementEnabled()
    {
        $args = func_get_args();
        if ($args[0] == OrderDetailsDataMapper::CODE_IMPROVEMENT_TOGGLE) {
            return true;
        }
    }

    public function testIsOrderDueDateChangedReturnsTrue()
    {
        $orderId = 123;

        // Expect addFieldToFilter called with correct params and getSize returns > 0
        $this->collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_id', $orderId)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $result = $this->orderDetailsDataMapper->isOrderDueDateChanged($orderId);
        $this->assertTrue($result);
    }

    public function testIsOrderDueDateChangedReturnsFalse()
    {
        $orderId = 123;

        $this->collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_id', $orderId)
            ->willReturnSelf();

        $this->collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $result = $this->orderDetailsDataMapper->isOrderDueDateChanged($orderId);
        $this->assertFalse($result);
    }

    public function testGetRecentUpdatedTimeReturnsUpdatedAt()
    {
        $shippingId = 456;
        $dueDate = '2025-07-03 12:00:00';
        $expectedUpdatedAt = '2025-07-04 10:00:00';

        $this->collectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                ['shipment_id', $shippingId],
                ['new_due_date', $dueDate]
            )
            ->willReturnSelf();

        $lastItemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getUpdatedAt'])
            ->getMock();
        $lastItemMock->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($expectedUpdatedAt);

        $this->collectionMock->expects($this->once())
            ->method('getLastItem')
            ->willReturn($lastItemMock);

        $result = $this->orderDetailsDataMapper->getRecentUpdatedTime($shippingId, $dueDate);
        $this->assertEquals($expectedUpdatedAt, $result);
    }
}
