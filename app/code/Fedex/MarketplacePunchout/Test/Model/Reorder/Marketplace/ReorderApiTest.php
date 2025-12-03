<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Jyoti thakur <jyoti.thakur.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Reorder\Marketplace;

use Exception;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\MarketplacePunchout\Model\Reorder\Marketplace\ReorderApi;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\TrackOrder\Model\OrderDetailsDataMapper;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\MarketplacePunchout\Model\Authorization;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Mirakl\Api\Helper\Order as MiraklHelper;
use Fedex\Orderhistory\ViewModel\OrderHistoryEnhacement;
use Mirakl\MMP\FrontOperator\Domain\Order as ShopOrder;
use Mirakl\MMP\Common\Domain\Order\OrderShipping;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection as ShopShipmentCollection;
use Mirakl\Core\Domain\Collection\MiraklCollection;

class ReorderApiTest extends TestCase
{
    private MarketplaceConfig $configMock;
    private Curl $curlMock;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loggerMock;

    /** @var Authorization|\PHPUnit\Framework\MockObject\MockObject */
    private $marketplaceAuthorizationMock;

    /** @var CheckoutSession|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutSessionMock;

    /** @var OrderDetailsDataMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $orderDetailsDataMapperMock;

    /** @var MarketPlaceHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $marketPlaceHelperMock;

    /** @var ShipmentApi|\PHPUnit\Framework\MockObject\MockObject */
    private $shipmentApi;

    /** @var MiraklHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $miraklHelper;

    /** @var OrderHistoryEnhacement|\PHPUnit\Framework\MockObject\MockObject */
    private $orderHistoryEnhancement;

    private ReorderApi $reorderApi;

    private const BROKER_CONFIG_ID = '12345';
    private const ORDER_INCREMENT_ID = '12345';
    private const PRODUCT_SKU = 'abcdefgh';
    private const TEST_REORDER_URL = 'https://example.com/api/Reorder/bulk';

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(MarketplaceConfig::class);
        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->marketplaceAuthorizationMock = $this->createMock(Authorization::class);
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMarketplaceAuthToken','setMarketplaceAuthToken'])
            ->getMock();

        $this->orderDetailsDataMapperMock = $this->createMock(OrderDetailsDataMapper::class);
        $this->marketPlaceHelperMock = $this->createMock(MarketPlaceHelper::class);
        $this->shipmentApi = $this->createMock(ShipmentApi::class);
        $this->miraklHelper = $this->createMock(MiraklHelper::class);
        $this->orderHistoryEnhancement = $this->createMock(OrderHistoryEnhacement::class);

        $this->reorderApi = new ReorderApi(
            $this->configMock,
            $this->curlMock,
            $this->loggerMock,
            $this->marketplaceAuthorizationMock,
            $this->checkoutSessionMock,
            $this->orderDetailsDataMapperMock,
            $this->marketPlaceHelperMock,
            $this->shipmentApi,
            $this->miraklHelper,
            $this->orderHistoryEnhancement
        );
    }

    /**
     * Test getReorderApiData method when curl returns HTTP status 400.
     *
     * @return void
     */
    public function testGetReorderApiDataCurlStatus400(): void
    {
        $token = 'token';
        $this->checkoutSessionMock->method('getMarketplaceAuthToken')->willReturn($token);

        $this->configMock->method('getNavitorReorderUrl')->willReturn(static::TEST_REORDER_URL);

        $this->curlMock->method('post');
        $this->curlMock->method('getStatus')->willReturn(400);
        $this->curlMock->method('getBody')->willReturn(null);

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            1,
            99
        );

        $this->assertEquals('{"status":400,"response":[],"track":[]}', $result);
    }


    /**
     * Test getReorderApiData method retries once after receiving a 401 Unauthorized, then succeeds.
     *
     * @return void
     */
    public function testGetReorderApiDataCurlStatus401ThenSuccess(): void
    {

        $this->checkoutSessionMock->method('getMarketplaceAuthToken')->willReturn('oldToken');

        $this->configMock->method('getNavitorReorderUrl')->willReturn(static::TEST_REORDER_URL);

        $this->curlMock->method('post')->willReturnCallback(function(){});
        $this->curlMock->method('getStatus')
            ->willReturnOnConsecutiveCalls(401, 200);

        $this->curlMock->method('getBody')
            ->willReturn('{"status":"success"}');

        $this->marketplaceAuthorizationMock->method('execute')->willReturn('newToken');

        $this->checkoutSessionMock->expects($this->once())
            ->method('setMarketplaceAuthToken')
            ->with('newToken');

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            1,
            99
        );

        $this->assertEquals('{"status":200,"response":{"status":"success"},"track":[]}', $result);
    }

    /**
     * Test getReorderApiData method retries all allowed attempts and still receives HTTP status 401 Unauthorized.
     *
     * @return void
     */
    public function testGetReorderApiDataCurlStatus401AllRetries(): void
    {
        $this->checkoutSessionMock->method('getMarketplaceAuthToken')->willReturn('oldToken');
        $this->configMock->method('getNavitorReorderUrl')->willReturn(static::TEST_REORDER_URL);

        $this->curlMock->method('getStatus')->willReturn(401);
        $this->curlMock->method('getBody')->willReturn(null);

        $this->marketplaceAuthorizationMock->method('execute')
            ->willReturnOnConsecutiveCalls('newToken1','newToken2','newToken3');

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            1,
            99
        );

        $this->assertEquals('{"status":401,"response":[],"track":[]}', $result);
    }

    /**
     * Test getReorderApiData method when curl request throws an exception.
     *
     * @return void
     */
    public function testGetReorderApiDataCurlThrowsException(): void
    {
        $this->checkoutSessionMock->method('getMarketplaceAuthToken')->willReturn('token');
        $this->configMock->method('getNavitorReorderUrl')->willReturn(static::TEST_REORDER_URL);

        $this->curlMock->method('post')->willThrowException(new Exception("Some Curl Error"));
        $this->curlMock->method('getStatus')->willReturn(200);

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            1,
            99
        );

        $this->assertStringContainsString('"status":500', $result);
        $this->assertStringContainsString('Some Curl Error', $result);
    }

    /**
     * Test getReorderApiData method with no existing auth token and exception occurs while obtaining a new token.
     *
     * @return void
     */
    public function testGetReorderApiDataNoTokenAndExceptionWhileSettingNewToken(): void
    {
        $this->checkoutSessionMock->method('getMarketplaceAuthToken')->willReturn(null);

        $this->marketplaceAuthorizationMock->method('execute')
            ->willThrowException(new Exception("Auth Token Error"));

        $this->configMock->method('getNavitorReorderUrl')->willReturn(static::TEST_REORDER_URL);

        $this->curlMock->method('post');
        $this->curlMock->method('getStatus')->willReturn(400);

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            1,
            99
        );

        $this->assertEquals('{"status":400,"response":[],"track":[]}', $result);
    }

    /**
     * Test getReorderApiData method for multi seller scenario with authorization URL and allow edit flag.
     *
     * @return void
     */
    public function testGetReorderApiDataMultiSellerWithAuthorizationUrlAndAllowEdit(): void
    {
        $shopCustomAttributes = [
            'reorder-url'         => 'https://custom-seller/api/reorder',
            'allow-edit-reorder'  => 'true',
            'authorization-url'   => 'https://custom-seller/api/authorize',
            'shared-secret-token' => 'fakeSecret',
            'mirakl-order-id-for-reorder' => 'false',
            'seller-headers'      => json_encode(['X-Seller-Custom' => 'ABC123'])
        ];
        $this->configMock->method('getShopCustomAttributesByProductSku')
            ->willReturn($shopCustomAttributes);

        $this->checkoutSessionMock->method('getMarketplaceAuthToken')->willReturn(null);
        $this->marketplaceAuthorizationMock->method('execute')->willReturn('multiSellerToken');

        $this->curlMock->method('post');
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('{"result":{"someKey":"someVal"}}');

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            2,
            99
        );

        $this->assertEquals('{"status":200,"response":{"result":{"someKey":"someVal"}},"track":[]}', $result);
    }

    /**
     * Test getReorderApiData method in multi-seller scenario using shared secret token and Mirakl order ID for reorder.
     *
     * @return void
     */
    public function testGetReorderApiDataMultiSellerWithSharedSecretAndMiraklIdForReorder(): void
    {
        $shopCustomAttributes = [
            'reorder-url'                   => 'https://custom-seller/api/reorder',
            'allow-edit-reorder'            => 'false',
            'mirakl-order-id-for-reorder'   => 'true',
            'shared-secret-token'           => 'fakeSecret',
            'shop_id'                       => 123,
            'offer_id'                      => 456
        ];
        $this->configMock->method('getShopCustomAttributesByProductSku')
            ->willReturn($shopCustomAttributes);


        $this->checkoutSessionMock->expects($this->never())
            ->method('getMarketplaceAuthToken');

        $this->orderDetailsDataMapperMock
            ->method('getMiraklOrderValue')
            ->with(
                self::ORDER_INCREMENT_ID,
                123,
                456,
                true
            )
            ->willReturn('TransformedMiraklOrderId');

        $this->curlMock->method('post');
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('{"result":{"abc":"123"}}');

        $result = $this->reorderApi->getReorderApiData(
            self::BROKER_CONFIG_ID,
            self::PRODUCT_SKU,
            self::ORDER_INCREMENT_ID,
            3,
            99
        );

        $this->assertEquals(
            '{"status":200,"response":[{"abc":"123","isSuccess":true}],"track":[]}',
            $result
        );
    }

    /**
     * Test getReorderApiData method in multi-seller scenario using shared secret token without Mirakl order ID for reorder.
     *
     * @return void
     */
    public function testGetReorderApiDataMultiSellerWithSharedSecretAndNoMiraklReorder(): void
    {
       $shopCustomAttributes = [
            'reorder-url'                => 'https://custom-seller/api/reorder',
            'allow-edit-reorder'         => 'false',
            'mirakl-order-id-for-reorder'=> 'false',
            'shared-secret-token'        => 'fakeSecret'
        ];

        $this->configMock->method('getShopCustomAttributesByProductSku')
            ->willReturn($shopCustomAttributes);

        $this->checkoutSessionMock->expects($this->never())
            ->method('getMarketplaceAuthToken');

        $this->orderDetailsDataMapperMock->expects($this->never())
            ->method('getMiraklOrderValue');

        $this->curlMock->method('post');
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('{"result":{"someKey":"someVal"}}');

        $result = $this->reorderApi->getReorderApiData(
            'originalBrokerId',
            'sampleSku',
            '100000001',
            5,
            99
        );

        $expected = '{"status":200,"response":[{"someKey":"someVal","isSuccess":true}],"track":[]}';
        $this->assertSame($expected, $result);
    }

    /**
     * Test getShipmentTrackingNumbersByOrderItem returns tracking numbers matching provided order line ID.
     *
     * @return void
     */
    public function testGetShipmentTrackingNumbersByOrderItemMatchesLineId(): void
    {
        $this->marketPlaceHelperMock->method('isEssendantToggleEnabled')->willReturn(true);

        $miraklOrder = new ShopOrder();
        $miraklOrder->setId('MIR-111');

        $this->miraklHelper->method('getOrders')->willReturn([$miraklOrder]);

        $shipment = new OrderShipping();

        $trackingData = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $trackingData->method('getData')
            ->with('tracking_number')
            ->willReturn('TRK-123456');

        $shipment->setTracking($trackingData);

        $shipmentLine1 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $shipmentLine1->method('getData')
            ->with('order_line_id')
            ->willReturn(50);

        $shipmentLine2 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $shipmentLine2->method('getData')
            ->with('order_line_id')
            ->willReturn(99);

        $shipment->setShipmentLines([$shipmentLine1, $shipmentLine2]);

        $miraklCollection = new MiraklCollection([$shipment]);
        $shipments = new ShopShipmentCollection();
        $shipments->setCollection($miraklCollection);

        $this->shipmentApi->method('getShipments')->willReturn($shipments);

        $this->orderHistoryEnhancement->method('getTrackOrderUrl')
            ->willReturn('https://track.example.com/');

        $trackNumbers = $this->reorderApi->getShipmentTrackingNumbersByOrderItem('1000001', 99);

        $this->assertCount(1, $trackNumbers);
        $this->assertEquals('TRK-123456', $trackNumbers[0]['number']);
        $this->assertEquals('https://track.example.com/TRK-123456', $trackNumbers[0]['url']);
    }

    /**
     * Test getShipmentTrackingNumbersByOrderItem method returns empty when Essendant toggle feature is disabled.
     *
     * @return void
     */
    public function testGetShipmentTrackingNumbersByOrderItemEssendantDisabled(): void
    {
        $this->marketPlaceHelperMock->method('isEssendantToggleEnabled')->willReturn(false);

        $result = $this->reorderApi->getShipmentTrackingNumbersByOrderItem('1000001', 123);
        $this->assertEmpty($result);
    }

    /**
     * Test getShipmentTrackingNumbersByOrderItem method returns empty when no Mirakl orders exist.
     *
     * @return void
     */
    public function testGetShipmentTrackingNumbersByOrderItemNoMiraklOrders(): void
    {
        $this->marketPlaceHelperMock->method('isEssendantToggleEnabled')->willReturn(true);
        $this->miraklHelper->method('getOrders')->willReturn([]);

        $result = $this->reorderApi->getShipmentTrackingNumbersByOrderItem('1000001', 123);
        $this->assertEmpty($result);
    }

    /**
     * Test getShipmentTrackingNumbersByOrderItem method returns empty when no shipment line ID matches.
     *
     * @return void
     */
    public function testGetShipmentTrackingNumbersByOrderItemNoMatchingLineId(): void
    {
        $this->marketPlaceHelperMock->method('isEssendantToggleEnabled')->willReturn(true);

        $miraklOrder = new ShopOrder();
        $miraklOrder->setId('MIR-999');
        $this->miraklHelper->method('getOrders')->willReturn([$miraklOrder]);

        $shipment = new OrderShipping();

        $shipmentLine = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $shipmentLine->method('getData')
            ->with('order_line_id')
            ->willReturn(1234);

        $shipment->setShipmentLines([$shipmentLine]);
        $trackingData = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getData'])
            ->getMock();
        $trackingData->method('getData')
            ->with('tracking_number')
            ->willReturn('TRK-999');

        $shipment->setTracking([$trackingData]);
        $miraklCollection = new MiraklCollection([$shipment]);

        $shipments = new ShopShipmentCollection();
        $shipments->setCollection($miraklCollection);

        $this->shipmentApi->method('getShipments')->willReturn($shipments);
        $this->orderHistoryEnhancement->method('getTrackOrderUrl')
            ->willReturn('https://track.example.com/');

        $result = $this->reorderApi->getShipmentTrackingNumbersByOrderItem('1000001', 99);
        $this->assertEmpty($result);
    }
}
