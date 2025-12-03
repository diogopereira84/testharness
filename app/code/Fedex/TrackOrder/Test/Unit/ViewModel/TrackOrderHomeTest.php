<?php
/**
 * @category    Fedex
 * @package     Fedex_TrackOrder
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Adithya Adithya <adithya.adithya@fedex.com>
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Fedex\TrackOrder\ViewModel\TrackOrderHome;
use Fedex\EnvironmentManager\Model\Config\TrackOrderPod;
use Fedex\TrackOrder\Helper\OrderHelper;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Fedex\TrackOrder\Model\OrderDetailApi;
use Fedex\Delivery\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\TrackOrder\Model\Config;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class TrackOrderHomeTest extends TestCase
{
    protected TrackOrderHome $trackOrderHome;
    protected MockObject|TrackOrderPod $trackOrderPodMock;
    protected MockObject|OrderHelper $orderHelperMock;
    protected MockObject|OrderDetailsDataMapper $orderDetailsDataMapperMock;
    protected MockObject|OrderDetailApi $orderDetailApiMock;
    protected MockObject|Data $deliveryHelperMock;
    protected MockObject|ScopeConfigInterface $scopeConfigInterfaceMock;
    protected MockObject|ToggleConfig $toggleConfigMock;
    /**
     * @var Config|MockObject
     */
    private $configMock;
    private $assetMock;

    /** @var array */
    private $defaultOrderData;

    /** @var string */
    private $defaultKey;

    protected function setUp(): void
    {
        $this->trackOrderPodMock = $this->createMock(TrackOrderPod::class);
        $this->orderHelperMock = $this->createMock(OrderHelper::class);
        $this->orderDetailsDataMapperMock = $this->createMock(OrderDetailsDataMapper::class);
        $this->orderDetailApiMock = $this->createMock(OrderDetailApi::class);
        $this->deliveryHelperMock = $this->createMock(Data::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->configMock = $this->createMock(Config::class);
        $this->assetMock = $this->createMock(AssetRepository::class);

        $this->trackOrderHome = new TrackOrderHome(
            $this->trackOrderPodMock,
            $this->orderDetailsDataMapperMock,
            $this->deliveryHelperMock,
            $this->scopeConfigInterfaceMock,
            $this->toggleConfigMock,
            $this->configMock,
            $this->orderDetailApiMock,
            $this->orderHelperMock,
            $this->assetMock
        );

        $this->defaultOrderData = [
            'someKey' => [
                'orderMktItems' => [1, 2, 3],
                'orderItems' => [1]
            ]
        ];
        $this->defaultKey = 'someKey';
        $this->trackOrderHome->initOrderContext($this->defaultOrderData, $this->defaultKey);
    }

    public function testIsTrackOrderActive()
    {
        $this->trackOrderPodMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->assertTrue($this->trackOrderHome->isTrackOrderActive());
    }

    public function testGetOrderlist()
    {
        $orderIds = [1, 2, 3];
        $expectedResult = ['order1', 'order2', 'order3'];

        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('getOrderlist')
            ->with($orderIds)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->trackOrderHome->getOrderlist($orderIds));
    }

    public function testIsOrderDelayed()
    {
        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('isOrderDelayed')
            ->with($this->defaultOrderData, $this->defaultKey)
            ->willReturn(true);

        $this->assertTrue($this->trackOrderHome->isOrderDelayed());
    }

    public function testIsMktOrderDelayed()
    {
        $this->orderDetailsDataMapperMock->expects($this->any())
            ->method('isMktOrderDelayed')
            ->with($this->defaultOrderData, $this->defaultKey)
            ->willReturn(true);

        $this->assertTrue($this->trackOrderHome->isMktOrderDelayed());
    }

    public function testIsMktOrderDelayedEnhancement()
    {
        $shopId = 123;

        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('isMktOrderDelayedEnhancement')
            ->with($this->defaultOrderData, $this->defaultKey, $shopId)
            ->willReturn(true);

        $this->assertTrue($this->trackOrderHome->isMktOrderDelayedEnhancement($shopId));
    }

    public function testGetOrderStatusHeading()
    {
        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('getOrderStatusHeading')
            ->with($this->defaultOrderData, $this->defaultKey)
            ->willReturn('Some Heading');

        $this->assertEquals('Some Heading', $this->trackOrderHome->getOrderStatusHeading());
    }

    public function testGetOrderStatusDetail()
    {
        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('getOrderStatusDetail')
            ->with($this->defaultOrderData, $this->defaultKey)
            ->willReturn('Shipped');

        $this->assertEquals('Shipped', $this->trackOrderHome->getOrderStatusDetail());
    }

    public function testGetMktOrderStatusDetail()
    {
        $orderData = ['someOrderData'];
        $key = 'someKey';

        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('getMktOrderStatusDetail')
            ->with($orderData, $key)
            ->willReturn('shipping');

        $result = $this->trackOrderHome->getMktOrderStatusDetail($orderData, $key);
        $this->assertIsString($result);
        $this->assertEquals('shipping', $result);
    }

    public function testGetMktOrderStatusDetailEnhancement()
    {
        $shopId = 123;

        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('getMktOrderStatusDetailEnhancement')
            ->with($this->defaultOrderData, $this->defaultKey, $shopId, null)
            ->willReturn('shipping');

        $result = $this->trackOrderHome->getMktOrderStatusDetailEnhancement($shopId);
        $this->assertIsString($result);
        $this->assertEquals('shipping', $result);
    }

    public function testIsCommercialCustomer()
    {
        $this->deliveryHelperMock->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->assertTrue($this->trackOrderHome->isCommercialCustomer());
    }
    public function testIsOrderTrackingDeliveryDateUpdateEnableFalse()
    {
        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('isOrderTrackingDeliveryDateUpdateEnable')
            ->willReturn(false);

        $result = $this->trackOrderHome->isOrderTrackingDeliveryDateUpdateEnable();

        $this->assertFalse($result);
    }

    public function testIsOrderTrackingDeliveryDateUpdateEnable()
    {
        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('isOrderTrackingDeliveryDateUpdateEnable')
            ->willReturn(true);

        $result = $this->trackOrderHome->isOrderTrackingDeliveryDateUpdateEnable();

        $this->assertTrue($result);
    }

    public function testGetLegacyTrackOrderUrl()
    {
        $url = 'https://example.com';
        $this->orderDetailsDataMapperMock->expects($this->once())
            ->method('getLegacyTrackOrderUrl')
            ->willReturn($url);

        $result = $this->trackOrderHome->getLegacyTrackOrderUrl();
        $this->assertIsString($result);
        $this->assertEquals($url, $result);
    }
    public function testGetLabelText()
    {
        $this->assertEquals('Track My Print Order', $this->trackOrderHome->getLabelText());
    }

    public function testGetTrackOrderHeader()
    {
        $expectedHeader = 'Track Your Order';
        $this->configMock->expects($this->once())
            ->method('getTrackOrderHeader')
            ->willReturn($expectedHeader);

        $this->assertEquals($expectedHeader, $this->trackOrderHome->getTrackOrderHeader());
    }

    public function testGetTrackOrderDescription()
    {
        $trackOrderDescription = 'Track Order Description';
        $this->configMock->expects($this->once())
            ->method('getTrackOrderDescription')
            ->willReturn($trackOrderDescription);

        $this->assertEquals($trackOrderDescription, $this->trackOrderHome->getTrackOrderDescription());
    }

    public function testGetTrackShipmentUrl()
    {
        $trackShipmentUrl = 'http://example.com/track';
        $this->configMock->expects($this->once())
            ->method('getTrackShipmentUrl')
            ->willReturn($trackShipmentUrl);

        $this->assertEquals($trackShipmentUrl, $this->trackOrderHome->getTrackShipmentUrl());
    }

    public function testToggleCodeImprovementLogic()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_B_2177449_order_tracking_improvement_toggle')
            ->willReturn(true);

        $this->assertTrue($this->trackOrderHome->toggleCodeImprovementLogic());
    }

    public function testGetTrackingDetails()
    {
        $orderData = [
            'key1' => [
                'order_id' => 123,
                'orderMktItems' => [
                    [
                        'track_order_url' => 'http://example.com/track',
                        'tracking_number' => 'TRACK123'
                    ]
                ]
            ]
        ];
        $key = 'key1';

        $expectedResult = [
            'originStatusProgressKey' => 123,
            'trackMktOrderUrl' => 'http://example.com/track',
            'mktTrackingNumber' => 'TRACK123'
        ];

        $this->assertEquals($expectedResult, $this->trackOrderHome->getTrackingDetails($orderData, $key));
    }

    public function testGetTrackingDetailsMultiple3pSellersTrackerToggleOn()
    {
        $orderData = [
            'key1' => [
                'order_id' => 123,
                'orderMktItems' => [
                    [
                        'track_order_url' => 'http://example.com/track',
                        'tracking_number' => 'TRACK123'
                    ]
                ]
            ]
        ];
        $key = 'key1';

        $seller = [
            'track_order_url' => 'http://example.com/track',
            'tracking_number' => 'TRACK123'
        ];

        $expectedResult = [
            'originStatusProgressKey' => 123,
            'trackMktOrderUrl' => 'http://example.com/track',
            'mktTrackingNumber' => 'TRACK123'
        ];

        $this->assertEquals($expectedResult, $this->trackOrderHome->getTrackingDetails($orderData, $key, 123, $seller));
    }

    public function testGetOrderlistApi()
    {
        $orderId = 12345;
        $expectedResult = [
            'order_id' => $orderId,
            'isValid' => true,
            'order_details' => ['id' => $orderId, 'status' => 'shipped']
        ];

        $this->orderDetailApiMock->expects($this->once())
            ->method('fetchOrderDetailFromApi')
            ->with($orderId)
            ->willReturn($expectedResult);

        $result = $this->trackOrderHome->getOrderlistApi($orderId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetSegregatedOrderIds()
    {
        $orderIds = ['30101234', '20105678'];
        $segregatedOrders = [
            'apiOrders' => ['30101234'],
            'magOrders' => ['20105678'],
        ];

        $this->orderHelperMock->expects($this->once())
            ->method('segregateOrderIds')
            ->with($orderIds)
            ->willReturn($segregatedOrders);

        $result = $this->trackOrderHome->getSegregatedOrderIds($orderIds);

        $this->assertEquals($segregatedOrders, $result);
    }

    public function testGetProductDueDateMessage()
    {
        $expectedResult = 'Test Due Date Message 1P';

        $this->configMock->expects($this->once())
            ->method('getProductDueDateMessage')
            ->willReturn($expectedResult);

        $result = $this->trackOrderHome->getProductDueDateMessage();

        $this->assertEquals($expectedResult, $result);
    }

    public function testGuardAgainstUninitializedContextThrowsException()
    {
        $uninitializedTrackOrderHome = new TrackOrderHome(
            $this->trackOrderPodMock,
            $this->orderDetailsDataMapperMock,
            $this->deliveryHelperMock,
            $this->scopeConfigInterfaceMock,
            $this->toggleConfigMock,
            $this->configMock,
            $this->orderDetailApiMock,
            $this->orderHelperMock,
            $this->assetMock
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Order context must be initialized by calling initOrderContext() before using this method.');

        $uninitializedTrackOrderHome->getMiraklItemCount();
    }

    public function testGetMiraklItemCountAfterInitialization()
    {
        $this->assertEquals(3, $this->trackOrderHome->getMiraklItemCount());
    }

    /**
     * @dataProvider imageUrlProvider
     */
    public function testGetImageUrlMethods(string $methodName, string $expectedImagePath)
    {
        $expectedUrl = 'http://localhost/static/version123/frontend/Vendor/theme/en_US/' . $expectedImagePath;

        $this->assetMock->expects($this->once())
            ->method('getUrl')
            ->with($expectedImagePath)
            ->willReturn($expectedUrl);

        $result = $this->trackOrderHome->$methodName();

        $this->assertEquals($expectedUrl, $result);
    }

    public function imageUrlProvider(): array
    {
        return [
            'Info Image Url' => ['getInfoImageUrl', 'Fedex_TrackOrder::images/info.png'],
            'Notification Banner Image Url' => ['getNotificationBannerImageUrl', 'Fedex_TrackOrder::images/Notification-banner.png'],
            'Up Arrow Image Url' => ['getUpArrowImageUrl', 'Fedex_TrackOrder::images/up-arrow.png'],
            'Down Arrow Image Url' => ['getDownArrowImageUrl', 'Fedex_TrackOrder::images/down-arrow.png'],
            'Ordered Icon Image Url' => ['getOrderedIconImageUrl', 'Fedex_TrackOrder::images/Ordered-icon.png'],
            'Ordered Icon Delay Image Url' => ['getOrderedIconDelayImageUrl', 'Fedex_TrackOrder::images/Delay-icon.png'],
        ];
    }
}
