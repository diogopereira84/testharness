<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceRates
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\TrackOrder\Test\Unit\Block\Order;

use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Model\Order\Address;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Block\Template\Context;
use Fedex\TrackOrder\Block\Order\OrderDetails;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Magento\Sales\Model\OrderFactory;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Magento\Sales\Model\Order;
use Fedex\TrackOrder\ViewModel\TrackOrderHome;
use Magento\Sales\Model\Order\Shipment\Item as ShipmentItem;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class OrderDetailsTest extends TestCase
{
    /**
     * @var OrderDetails
     */
    private $orderDetails;

    /**
     * @var OrderDetailsDataMapper
     */
    private $orderDetailsDataMapperMock;

    /**
     * @var OrderFactory
     */
    private $orderFactoryMock;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var OrderHistoryEnhacement
     */
    private $orderHistoryEnhancementViewModelMock;

    /**
     * @var TrackOrderHome
     */
    private $trackOrderHomeViewModelMock;

    /**
     * @var Context
     */
    private $contextMock;

    /**
     * @var Address
     */
    private $orderAddressMock;

    /**
     * @var LayoutInterface
     */
    private $layoutMock;

    /**
     * @var OrderDetails
     */
    private $blockMock;

    private MarketplaceCheckoutHelper $marketplaceCheckoutHelper;

    protected function setUp(): void
    {
        $this->orderDetailsDataMapperMock = $this->getMockBuilder(OrderDetailsDataMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderHistoryEnhancementViewModelMock = $this->getMockBuilder(OrderHistoryEnhacement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->trackOrderHomeViewModelMock = $this->getMockBuilder(TrackOrderHome::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getShippingAddress', 'loadByIncrementId', 'getShippingMethod', 'getShippingDescription'])
            ->getMock();

        $this->orderAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->marketplaceCheckoutHelper = $this->getMockBuilder(MarketplaceCheckoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutMock = $this->createMock(LayoutInterface::class);
        $this->blockMock = $this->createMock(OrderDetails::class);
        $this->contextMock->method('getLayout')->willReturn($this->layoutMock);

        $this->orderDetails = new OrderDetails(
            $this->contextMock,
            $this->orderDetailsDataMapperMock,
            $this->orderFactoryMock,
            $this->orderHistoryEnhancementViewModelMock,
            $this->trackOrderHomeViewModelMock,
            $this->marketplaceCheckoutHelper
        );
    }

    /**
     * Test OrderShippingTypePickup method.
     *
     * @return void
     */
    public function testIsOrderShippingTypePickup()
    {
        $order = $this->order;
        $order->method('getShippingMethod')->willReturn(OrderDetails::SHIPPING_TYPE_PICKUP);

        $result = $this->orderDetails->isOrderShippingTypePickup($order);
        $this->assertTrue($result);
    }

    /**
     * Test getOrderStatusHeading method.
     *
     * @return void
     */
    public function testGetOrderStatusHeading()
    {
        $orderData = [];
        $key = 'some_key';

        $this->orderDetailsDataMapperMock
            ->expects($this->once())
            ->method('getOrderStatusHeading')
            ->with($orderData, $key)
            ->willReturn('Status Heading');

        $result = $this->orderDetails->getOrderStatusHeading($orderData, $key);
        $this->assertEquals('Status Heading', $result);
    }

    /**
     * Test getOrderStatusDetail method.
     *
     * @return void
     */
    public function testGetOrderStatusDetail()
    {
        $orderData = [];
        $key = 'some_key';

        $this->orderDetailsDataMapperMock
            ->expects($this->once())
            ->method('getOrderStatusDetail')
            ->with($orderData, $key)
            ->willReturn('Status Detail');

        $result = $this->orderDetails->getOrderStatusDetail($orderData, $key);
        $this->assertEquals('Status Detail', $result);
    }

    /**
     * Test getOrderlist method.
     *
     * @return void
     */
    public function testGetOrderlist()
    {
        $orderIds = [1, 2, 3];
        $expectedResult = ['order1', 'order2', 'order3'];

        $this->orderDetailsDataMapperMock
            ->expects($this->once())
            ->method('getOrderlist')
            ->with($orderIds)
            ->willReturn($expectedResult);

        $result = $this->orderDetails->getOrderlist($orderIds);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test isOrderDelayed method.
     *
     * @return void
     */
    public function testIsOrderDelayed()
    {
        $orderData = [];
        $key = 'some_key';

        $this->orderDetailsDataMapperMock
            ->expects($this->once())
            ->method('isOrderDelayed')
            ->with($orderData, $key)
            ->willReturn(true);

        $result = $this->orderDetails->isOrderDelayed($orderData, $key);
        $this->assertTrue($result);
    }

    /**
     * Test isMktOrderDelayed method.
     *
     * @return void
     */
    public function testIsMktOrderDelayed()
    {
        $orderData = [];
        $key = 'some_key';

        $this->orderDetailsDataMapperMock
            ->expects($this->once())
            ->method('isMktOrderDelayed')
            ->with($orderData, $key)
            ->willReturn(false);

        $result = $this->orderDetails->isMktOrderDelayed($orderData, $key);
        $this->assertFalse($result);
    }

    /**
     * Test getOrderByIncrementId method.
     *
     * @return void
     */
    public function testGetOrderByIncrementId()
    {
        $orderIncrementId = '100001';

        $orderMock = $this->order;
        $orderMock->method('loadByIncrementId')->willReturnSelf();

        $this->orderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($orderMock);

        $result = $this->orderDetails->getOrderByIncrementId($orderIncrementId);
        $this->assertInstanceOf(Order::class, $result);
    }

    /**
     * Test getMiraklItemCount method.
     *
     * @return void
     */
    public function testGetMiraklItemCount()
    {
        $shipmentItemMock1 = $this->createShipmentItemMock();
        $shipmentItemMock1->method('getOrderItem')->willReturn($this->createOrderItemMock());

        $shipmentItemMock2 = $this->createShipmentItemMock();
        $orderItemMock2 = $this->createOrderItemMock();
        $orderItemMock2->method('__call')
            ->with('getData', ['mirakl_offer_id'])
            ->willReturn('offer123');
        $shipmentItemMock2->method('getOrderItem')->willReturn($orderItemMock2);
        $orderItemMock2->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn(123);

        $this->orderDetails->getMiraklItemCount([$shipmentItemMock1, $shipmentItemMock2]);
    }

    /**
     * Test getMiraklItemCount method.
     *
     * @return void
     */
    public function testGetItemThumbnailUrl()
    {
        $productObjMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedThumbnailUrl = 'http://example.com/product_thumbnail.jpg';

        $this->orderHistoryEnhancementViewModelMock
            ->expects($this->once())
            ->method('getItemThumbnailUrl')
            ->with($productObjMock)
            ->willReturn($expectedThumbnailUrl);

        $result = $this->orderDetails->getItemThumbnailUrl($productObjMock);

        $this->assertEquals($expectedThumbnailUrl, $result);
    }

    public function testGetOrderShippingDescription()
    {
        $order = $this->order;
        $order->method('getShippingMethod')
            ->willReturn('fedexshipping');
        $order->method('getShippingDescription')
            ->willReturn('FedEx Local Delivery - Thursday, August 8, 5:00pm');

        $return = $this->orderDetails->getOrderShippingDescription($order);
        $this->assertEquals('FedEx Local Delivery ', $return);
        $this->assertIsString($return, 'Shipping Description is String');
    }

    public function testGetOrderShippingDescriptionIsPickup()
    {

        $order = $this->order;
        $order->method('getShippingMethod')
            ->willReturn(OrderDetails::SHIPPING_TYPE_PICKUP);

        $this->orderAddressMock->method('getStreet')->willReturn(['123 Main St']);
        $this->orderAddressMock->method('getCity')->willReturn('City');
        $this->orderAddressMock->method('getCountryId')->willReturn('USA');
        $this->orderAddressMock->method('getPostcode')->willReturn('12345');
        $order->method('getShippingAddress')
            ->willReturn($this->orderAddressMock);

        $return = $this->orderDetails->getOrderShippingDescription($order);
        $this->assertEquals('123 Main St, City, USA 12345', $return);
    }

    /**
     * Test getOrderShippingType method.
     *
     * @return void
     */
    public function testGetOrderShippingType()
    {
        $orderMock = $this->order;

        $orderMock->method('getShippingMethod')
            ->willReturn(OrderDetails::SHIPPING_TYPE_PICKUP);
        $result = $this->orderDetails->getOrderShippingType($orderMock);
        $this->assertEquals(OrderDetailsDataMapper::DISPLAY_TITLE_PICKUP, $result);
    }

    /**
     * Test getTrackOrderHomeViewModel method.
     *
     * @return void
     */
    public function testGetTrackOrderHomeViewModel()
    {
        $result = $this->orderDetails->getTrackOrderHomeViewModel();
        $this->assertSame($this->trackOrderHomeViewModelMock, $result);
    }

    /**
     * Test getTrackorderDetailsPage method.
     *
     * @return void
     */
    public function testGetTrackorderDetailsPage()
    {
        $orderIds = ['123', '456'];
        $orderData = ['some' => 'data'];
        $orderKey = 'test_key';
        $expectedHtml = '<div>Some HTML</div>';

        $this->layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(
                $this->equalTo('Fedex\TrackOrder\Block\Order\OrderDetails'),
                $this->equalTo('trackorder_order_details_page_test_key'),
                $this->callback(function ($arguments) use ($orderIds, $orderData, $orderKey) {
                    return $arguments['data']['search'] === $orderIds &&
                        $arguments['data']['order_data'] === $orderData &&
                        $arguments['data']['order_key'] === $orderKey &&
                        $arguments['data']['track_order_home_view_model'] === $this->trackOrderHomeViewModelMock;
                })
            )
            ->willReturn($this->blockMock);

        $this->blockMock->expects($this->once())
            ->method('setTemplate')
            ->with($this->equalTo('Fedex_TrackOrder::order_details_page.phtml'))
            ->willReturnSelf();

        $this->blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($expectedHtml);

        $result = $this->orderDetails->getTrackorderDetailsPage($orderIds, $orderData, $orderKey);
        $this->assertEquals($expectedHtml, $result);
    }

    /**
     * Test getTrackorderOrderStatusComponent method.
     *
     * @return void
     */
    public function testGetTrackorderOrderStatusComponent()
    {
        $orderData = ['some' => 'data'];
        $orderKey = 'test_key';
        $orderStatusDetail = 'ordering';
        $expectedHtml = '<div>Some HTML</div>';

        $this->layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(
                $this->equalTo('Fedex\TrackOrder\Block\Order\OrderDetails'),
                $this->equalTo('trackorder_order_status_component_test_key'),
                $this->callback(function ($arguments) use ($orderData, $orderKey, $orderStatusDetail) {
                    return $arguments['data']['order_data'] === $orderData &&
                        $arguments['data']['order_key'] === $orderKey &&
                        $arguments['data']['order_status'] === $orderStatusDetail &&
                        $arguments['data']['track_order_home_view_model'] === $this->trackOrderHomeViewModelMock;
                })
            )
            ->willReturn($this->blockMock);

        $this->blockMock->expects($this->once())
            ->method('setTemplate')
            ->with($this->equalTo('Fedex_TrackOrder::order_status_component.phtml'))
            ->willReturnSelf();

        $this->blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($expectedHtml);

        $result = $this->orderDetails->getTrackorderOrderStatusComponent($orderData, $orderKey, $orderStatusDetail);
        $this->assertEquals($expectedHtml, $result);
    }

    /**
     * Test getTrackorderMktOrderStatusComponent method.
     *
     * @return void
     */
    public function testGetTrackorderMktOrderStatusComponent()
    {
        $orderData = ['test_key' => ['order_id' => '123']];
        $orderKey = 'test_key';
        $sellerId = 123;
        $seller = 'Seller Name';
        $expectedHtml = '<div>Some HTML</div>';

        $this->layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(
                $this->equalTo('Fedex\TrackOrder\Block\Order\OrderDetails'),
                $this->equalTo('trackorder_mkt_order_status_component_123_123'),
                $this->callback(function ($arguments) use ($orderData, $orderKey, $sellerId, $seller) {
                    return $arguments['data']['order_data'] === $orderData &&
                        $arguments['data']['order_key'] === $orderKey &&
                        $arguments['data']['seller_id'] === $sellerId &&
                        $arguments['data']['seller'] === $seller &&
                        $arguments['data']['track_order_home_view_model'] === $this->trackOrderHomeViewModelMock;
                })
            )
            ->willReturn($this->blockMock);

        $this->blockMock->expects($this->once())
            ->method('setTemplate')
            ->with($this->equalTo('Fedex_TrackOrder::mkt_order_status_component.phtml'))
            ->willReturnSelf();

        $this->blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($expectedHtml);

        $result = $this->orderDetails->getTrackorderMktOrderStatusComponent($orderData, $orderKey, $sellerId, $seller);
        $this->assertEquals($expectedHtml, $result);
    }

    /**
     * Test getTrackorderMktMultiple3PSellersTracker method.
     *
     * @return void
     */
    public function testGetTrackorderMktMultiple3PSellersTracker()
    {
        $orderData = ['some' => 'data'];
        $orderKey = 'test_key';
        $expectedHtml = '<div>Some HTML</div>';

        $this->layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(
                $this->equalTo('Fedex\TrackOrder\Block\Order\OrderDetails'),
                $this->equalTo('trackorder_mkt_multiple_3p_sellers_tracker_test_key'),
                $this->callback(function ($arguments) use ($orderData, $orderKey) {
                    return $arguments['data']['order_data'] === $orderData &&
                        $arguments['data']['order_key'] === $orderKey &&
                        $arguments['data']['track_order_home_view_model'] === $this->trackOrderHomeViewModelMock;
                })
            )
            ->willReturn($this->blockMock);

        $this->blockMock->expects($this->once())
            ->method('setTemplate')
            ->with($this->equalTo('Fedex_TrackOrder::marketplace/multiple_3p_sellers_tracker.phtml'))
            ->willReturnSelf();

        $this->blockMock->expects($this->once())
            ->method('toHtml')
            ->willReturn($expectedHtml);

        $result = $this->orderDetails->getTrackorderMktMultiple3PSellersTracker($orderData, $orderKey);
        $this->assertEquals($expectedHtml, $result);
    }

    /**
     * CreateShipmentItemMock.
     */
    private function createShipmentItemMock()
    {
        return $this->getMockBuilder(ShipmentItem::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * CreateOrderItemMock.
     */
    private function createOrderItemMock()
    {
        return $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * CreateOrderMock.
     */
    private function createOrderMock(array $methods, $onlyMethods = [])
    {
        return $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods($onlyMethods)
            ->setMethods($methods)
            ->getMock();
    }
}
