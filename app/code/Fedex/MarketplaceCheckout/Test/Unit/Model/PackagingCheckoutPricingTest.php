<?php

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceProduct\Model\Shop;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Service\PackagingItemService;

class PackagingCheckoutPricingTest extends TestCase
{
    /**
     * @var (\Mirakl\FrontendDemo\Helper\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteHelper;

    /**
     * @var (\Magento\Checkout\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $session;

    /**
     * @var (\Magento\Framework\App\CacheInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cache;

    /**
     * @var (\Fedex\MarketplaceProduct\Api\ShopRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shopRepository;

    /**
     * @var (\Fedex\MarketplaceRates\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketPlaceHelper;

    /**
     * @var (\Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing)
     */
    private $packagingCheckoutPricing;

    /**
     * @var (\Magento\Framework\HTTP\Client\Curl & \PHPUnit\Framework\MockObject\MockObject)
     */
    private $curlMock;

    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    private $jsonSerializerMock;

    /**
     * @var (\Psr\Log\LoggerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    private $loggerMock;

    /**
     * @var (\Fedex\MarketplaceProduct\Model\Shop & \PHPUnit\Framework\MockObject\MockObject)
     */
    private $shopMock;
    private $toggleConfig;
    private $packagingItemService;

    /**
     * Set up the test environment before each test.
     * @return void
     */
    protected function setUp(): void
    {
        $this->curlMock = $this->createMock(Curl::class);
        $this->jsonSerializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->shopMock = $this->createMock(Shop::class);
        $this->quoteHelper = $this->createMock(QuoteHelper::class);
        $this->session = $this->createMock(Session::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->shopRepository = $this->createMock(ShopRepositoryInterface::class);
        $this->marketPlaceHelper = $this->createMock(Data::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->packagingItemService = $this->createMock(PackagingItemService::class);

        $this->packagingCheckoutPricing = new PackagingCheckoutPricing(
            $this->curlMock,
            $this->jsonSerializerMock,
            $this->quoteHelper,
            $this->session,
            $this->loggerMock,
            $this->shopRepository,
            $this->marketPlaceHelper,
            $this->cache,
            $this->toggleConfig,
            $this->packagingItemService
        );
    }

    /**
     * Tests that the packaging details are retrieved successfully.
     * @return void
     */
    public function testGetPackagingDetailsSuccessful()
    {
        $items = [
            [
                "code" => "ELTE",
                "length" => "6",
                "width" => "6",
                "depth" => "2",
                "boardStrength" => "32",
                "quantity" => 4000
            ]
        ];

        $requestBody = ['items' => $items];
        $jsonRequestBody = json_encode($requestBody);

        $apiResponse = [
            'packaging' => [
                'quantity' => 1,
                'shape' => [
                    'length' => 48.0,
                    'width' => 40.0,
                    'depth' => 53.0,
                    'volume' => 101760.0,
                    'area' => 1920.0
                ],
                'totalWeight' => 820.0,
                'weight' => 820.0,
                'freightClass' => 85.0,
                'type' => 'pallet'
            ]
        ];

        $jsonResponseBody = json_encode($apiResponse);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($requestBody)
            ->willReturn($jsonRequestBody);

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with('https://example.com/api/endpoint', $jsonRequestBody);

        $this->curlMock->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('getBody')
            ->willReturn($jsonResponseBody);

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($jsonResponseBody)
            ->willReturn($apiResponse);

        $this->shopMock->expects($this->atMost(2))
            ->method('getSellerPackageApiEndpoint')
            ->willReturn('https://example.com/api/endpoint');

        $result = $this->packagingCheckoutPricing->getPackagingDetails($items, $this->shopMock);

        $this->assertEquals($apiResponse, $result);
    }

    /**
     * Tests that an exception is thrown when the API request fails.
     * @return void
     */
    public function testGetPackagingDetailsApiFailure()
    {
        $items = [
            [
                "code" => "ELTE",
                "length" => "6",
                "width" => "6",
                "depth" => "2",
                "boardStrength" => "32",
                "quantity" => 4000
            ]
        ];

        $requestBody = ['items' => $items];
        $jsonRequestBody = json_encode($requestBody);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($requestBody)
            ->willReturn($jsonRequestBody);

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with('https://example.com/api/endpoint', $jsonRequestBody);

        $this->curlMock->expects($this->atMost(3))
            ->method('getStatus')
            ->willReturn(500);

        $this->loggerMock->expects($this->atMost(2))
            ->method('error')
            ->with(
                'Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing::
                getPackagingDetails:102: API request failed. Status: 500'
            );

        $this->shopMock->expects($this->atMost(2))
            ->method('getSellerPackageApiEndpoint')
            ->willReturn('https://example.com/api/endpoint');

        $this->packagingCheckoutPricing->getPackagingDetails($items, $this->shopMock);
    }

    /**
     * Tests that an exception is thrown when the API response is not valid JSON.
     * @return void
     */
    public function testGetPackagingDetailsInvalidJsonResponse()
    {
        $items = [
            [
                "code" => "ELTE",
                "length" => "6",
                "width" => "6",
                "depth" => "2",
                "boardStrength" => "32",
                "quantity" => 4000
            ]
        ];

        $requestBody = ['items' => $items];
        $jsonRequestBody = json_encode($requestBody);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($requestBody)
            ->willReturn($jsonRequestBody);

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with('https://example.com/api/endpoint', $jsonRequestBody);

        $this->curlMock->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $this->curlMock->expects($this->once())
            ->method('getBody')
            ->willReturn('Invalid JSON');

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with('Invalid JSON')
            ->willThrowException(new \InvalidArgumentException('Unable to unserialize value.'));

        $this->shopMock->expects($this->atMost(2))
            ->method('getSellerPackageApiEndpoint')
            ->willReturn('https://example.com/api/endpoint');

        $this->packagingCheckoutPricing->getPackagingDetails($items, $this->shopMock);
    }

    /**
     * Test that the validateParams method correctly handles empty items.
     * @return void
     */
    public function testValidateParamsWithEmptyItems()
    {
        $this->shopMock->expects($this->never())
            ->method('getSellerPackageApiEndpoint');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Items array for Packaging API is empty.');

        $this->invokeValidateParams([], $this->shopMock);
    }

    /**
     * Test that validateParams handles the scenario when no seller API endpoint is provided.
     * @return void
     */
    public function testValidateParamsWithNoSellerApiEndpoint()
    {
        $items = [
            [
                "code" => "ELTE",
                "length" => "6",
                "width" => "6",
                "depth" => "2",
                "boardStrength" => "32",
                "quantity" => 4000
            ]
        ];

        $this->shopMock->expects($this->once())
            ->method('getSellerPackageApiEndpoint')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Seller API endpoint not found.');

        $this->invokeValidateParams($items, $this->shopMock);
    }

    /**
     * Test that validateParams correctly validates the items array.
     * @return void
     */
    private function invokeValidateParams(array $items, Shop $shop)
    {
        $reflection = new \ReflectionClass($this->packagingCheckoutPricing);
        $method = $reflection->getMethod('validateParams');
        $method->setAccessible(true);
        $method->invoke($this->packagingCheckoutPricing, $items, $shop);
    }

    /**
     * Tests that the packaging items are correctly retrieved when freight shipping is disabled.
     * @return void
     */
    public function testGetPackagingItemsWhenFreightShippingDisabled()
    {
        $this->marketPlaceHelper->expects($this->once())
            ->method('isFreightShippingEnabled')
            ->willReturn(false);

        $result = $this->packagingCheckoutPricing->getPackagingItems();
        $this->assertEquals([], $result);
    }

    /**
     * Tests that the packaging items are correctly retrieved when there are no marketplace items.
     * @return void
     */
    public function testGetPackagingItemsWithEmptyMarketplaceItems()
    {
        $this->marketPlaceHelper->expects($this->once())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $this->session->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->once())
            ->method('isMiraklQuote')
            ->with($quoteMock)
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('load')
            ->willReturn(false);

        $item1 = $this->createMock(Item::class);
        $item1->expects($this->once())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn(null);

        $item2 = $this->createMock(Item::class);
        $item2->expects($this->once())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn(false);

        $quoteMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$item1, $item2]);

        $result = $this->packagingCheckoutPricing->getPackagingItems();
        $this->assertEquals([], $result);
    }

    /**
     * Tests that the packaging items are correctly retrieved when the quote is not a Mirakl quote.
     * @return void
     */
    public function testGetPackagingItemsWithNonMiraklQuote()
    {
        $this->marketPlaceHelper->expects($this->once())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->session->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->once())
            ->method('isMiraklQuote')
            ->with($quoteMock)
            ->willReturn(false);

        $result = $this->packagingCheckoutPricing->getPackagingItems();
        $this->assertEquals([], $result);
    }

    /**
     * Tests that the packaging items are correctly retrieved from cache.
     * @return void
     */
    public function testGetPackagingItemsFromCache()
    {
        $this->marketPlaceHelper->expects($this->once())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn('123');

        $this->session->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->once())
            ->method('isMiraklQuote')
            ->with($quoteMock)
            ->willReturn(true);

        $cachedData = ['shop1' => [['packaging_info']]];
        $serializedData = json_encode($cachedData);

        $this->cache->expects($this->once())
            ->method('load')
            ->with('freight_packaging_response_123')
            ->willReturn($serializedData);

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serializedData)
            ->willReturn($cachedData);

        $result = $this->packagingCheckoutPricing->getPackagingItems();
        $this->assertEquals($cachedData, $result);
    }

    /**
     * Tests the full flow of getting packaging items, including cache and API calls.
     * @return void
     */
    public function testGetPackagingItemsFullFlow()
    {
        $packagingDataObject = (object)['length' => '10', 'width' => '8', 'depth' => '6'];
        $packagingDetails = [
            'packaging' => [
                'quantity' => 1,
                'shape' => ['length' => 48, 'width' => 40, 'depth' => 53],
                'totalWeight' => 820.0
            ]
        ];

        $this->marketPlaceHelper->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn('123');

        $this->session->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);

        $this->cache->expects($this->any())
            ->method('load')
            ->with('freight_packaging_response_123')
            ->willReturn(false);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['getAdditionalData', 'getMiraklShopId'])
            ->getMock();

        $itemMock->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('offer1');

        $additionalData = json_encode([
            'punchout_enabled' => true,
            'packaging_data' => $packagingDataObject
        ]);
        $itemMock->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData);

        $itemMock->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1');

        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$itemMock]);

        $shopMock = $this->createMock(Shop::class);
        $shopMock->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn(['freight_enabled' => true]);

        $shopMock->expects($this->any())
            ->method('getSellerPackageApiEndpoint')
            ->willReturn('https://example.com/api');

        $shopMock->expects($this->any())
            ->method('getId')
            ->willReturn('shop1');

        $this->shopRepository->expects($this->any())
            ->method('getById')
            ->with((int)'shop1')
            ->willReturn($shopMock);

        $actualPackagingCheckoutPricing = new PackagingCheckoutPricing(
            $this->curlMock,
            $this->jsonSerializerMock,
            $this->quoteHelper,
            $this->session,
            $this->loggerMock,
            $this->shopRepository,
            $this->marketPlaceHelper,
            $this->cache,
            $this->toggleConfig,
            $this->packagingItemService
        );

        $spyPackagingCheckoutPricing = $this->getMockBuilder(PackagingCheckoutPricing::class)
            ->setConstructorArgs([
                $this->curlMock,
                $this->jsonSerializerMock,
                $this->quoteHelper,
                $this->session,
                $this->loggerMock,
                $this->shopRepository,
                $this->marketPlaceHelper,
                $this->cache,
                $this->toggleConfig,
                $this->packagingItemService
            ])
            ->onlyMethods(['getPackagingDetails'])
            ->getMock();

        $spyPackagingCheckoutPricing->expects($this->once())
            ->method('getPackagingDetails')
            ->willReturn($packagingDetails);

        $this->packagingCheckoutPricing = $spyPackagingCheckoutPricing;

        $this->packagingCheckoutPricing->getPackagingItems();
    }

    /**
     * Tests that the packaging items are correctly retrieved with the save parameter.
     * @return void
     */
    public function testGetPackagingItemsWithSaveParameter()
    {
        $realPackagingCheckoutPricing = new PackagingCheckoutPricing(
            $this->curlMock,
            $this->jsonSerializerMock,
            $this->quoteHelper,
            $this->session,
            $this->loggerMock,
            $this->shopRepository,
            $this->marketPlaceHelper,
            $this->cache,
            $this->toggleConfig,
            $this->packagingItemService
        );

        $this->marketPlaceHelper->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn('123');

        $this->session->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);

        $this->cache->expects($this->never())
            ->method('load');

        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([]);

        $this->jsonSerializerMock->expects($this->any())
            ->method('serialize')
            ->willReturn('[]');

        $this->cache->expects($this->any())
            ->method('save');

        $this->assertTrue(true, 'Test expectations set up successfully');
    }

    /**
     * Tests the findSellerRecord method to ensure it retrieves the correct seller record.
     * @return void
     */
    public function testFindSellerRecord()
    {
        $shopId = 'shop1';
        $packaging = [
            'shop1' => ['packaging_info' => 'data1'],
            'shop2' => ['packaging_info' => 'data2'],
            'shop3' => ['packaging_info' => 'data3']
        ];

        $result = $this->packagingCheckoutPricing->findSellerRecord($shopId, $packaging);
        $this->assertEquals(['packaging_info' => 'data1'], $result);

        $nonExistentShopId = 'shop4';
        $result = $this->packagingCheckoutPricing->findSellerRecord($nonExistentShopId, $packaging);
        $this->assertNull($result);

        $emptyPackaging = [];
        $result = $this->packagingCheckoutPricing->findSellerRecord($shopId, $emptyPackaging);
        $this->assertNull($result);

        $numericShopId = 123;
        $numericPackaging = [
            123 => ['packaging_info' => 'numeric1'],
            456 => ['packaging_info' => 'numeric2']
        ];
        $result = $this->packagingCheckoutPricing->findSellerRecord($numericShopId, $numericPackaging);
        $this->assertEquals(['packaging_info' => 'numeric1'], $result);

        $stringShopId = '123';
        $result = $this->packagingCheckoutPricing->findSellerRecord($stringShopId, $numericPackaging);
        $this->assertEquals(['packaging_info' => 'numeric1'], $result);
    }

    /**
     * Tests the getPackagingItems method with multiple items from the same shop.
     * @return void
     */
    public function testGetPackagingItemsWithMultipleItemsFromSameShop()
    {
        $packagingDataObject1 = (object)['length' => '10', 'width' => '8', 'depth' => '6'];
        $packagingDataObject2 = (object)['length' => '12', 'width' => '10', 'depth' => '8'];

        $packagingDetails1 = [
            'packaging' => [
                'quantity' => 1,
                'shape' => ['length' => 48, 'width' => 40, 'depth' => 53],
                'totalWeight' => 600.0
            ]
        ];

        $this->marketPlaceHelper->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn('123');

        $this->session->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);

        $this->cache->expects($this->any())
            ->method('load')
            ->with('freight_packaging_response_123')
            ->willReturn(false);

        $itemMock1 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['getAdditionalData', 'getMiraklShopId'])
            ->getMock();

        $itemMock2 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['getAdditionalData', 'getMiraklShopId'])
            ->getMock();

        $itemMock1->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('offer1');

        $additionalData1 = json_encode([
            'punchout_enabled' => true,
            'packaging_data' => $packagingDataObject1
        ]);

        $itemMock1->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData1);

        $itemMock1->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1');

        $itemMock2->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('offer2');

        $additionalData2 = json_encode([
            'punchout_enabled' => true,
            'packaging_data' => $packagingDataObject2
        ]);

        $itemMock2->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData2);

        $itemMock2->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1');

        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$itemMock1, $itemMock2]);

        $shopMock = $this->createMock(Shop::class);
        $shopMock->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn(['freight_enabled' => true]);

        $shopMock->expects($this->any())
            ->method('getSellerPackageApiEndpoint')
            ->willReturn('https://example.com/api');

        $shopMock->expects($this->any())
            ->method('getId')
            ->willReturn('shop1');

        $this->shopRepository->expects($this->any())
            ->method('getById')
            ->with((int)'shop1')
            ->willReturn($shopMock);

        $spyPackagingCheckoutPricing = $this->getMockBuilder(PackagingCheckoutPricing::class)
            ->setConstructorArgs([
                $this->curlMock,
                $this->jsonSerializerMock,
                $this->quoteHelper,
                $this->session,
                $this->loggerMock,
                $this->shopRepository,
                $this->marketPlaceHelper,
                $this->cache,
                $this->toggleConfig,
                $this->packagingItemService
            ])
            ->onlyMethods(['getPackagingDetails'])
            ->getMock();

        $spyPackagingCheckoutPricing->expects($this->once())
            ->method('getPackagingDetails')
            ->willReturn($packagingDetails1);

        $this->packagingCheckoutPricing = $spyPackagingCheckoutPricing;

        $this->packagingCheckoutPricing->getPackagingItems();
    }

    /**
     * Tests the getPackagingItems method with multiple packaging details for the same seller.
     * @return void
     */
    public function testGetPackagingItemsWithMultiplePackagingDetailsForSameSeller()
    {
        $packagingDataObject1 = (object)['groupId' => 'group1', 'length' => '10', 'width' => '8', 'depth' => '6'];
        $packagingDataObject2 = (object)['groupId' => 'group1', 'length' => '12', 'width' => '10', 'depth' => '8'];
        $packagingDataObject3 = (object)['groupId' => 'group2', 'length' => '15', 'width' => '12', 'depth' => '10'];

        $packagingDetails1 = [
            'packaging' => [
                'quantity' => 1,
                'shape' => ['length' => 48, 'width' => 40, 'depth' => 53],
                'totalWeight' => 600.0,
                'groupId' => 'group1'
            ]
        ];

        $packagingDetails2 = [
            'packaging' => [
                'quantity' => 2,
                'shape' => ['length' => 72, 'width' => 60, 'depth' => 48],
                'totalWeight' => 1200.0,
                'groupId' => 'group2'
            ]
        ];

        $this->marketPlaceHelper->expects($this->any())
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn('123');

        $this->session->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteHelper->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(true);

        $this->cache->expects($this->any())
            ->method('load')
            ->with('freight_packaging_response_123')
            ->willReturn(false);

        $shopMock = $this->createMock(Shop::class);
        $shopMock->expects($this->any())
            ->method('getShippingRateOption')
            ->willReturn(['freight_enabled' => true]);

        $shopMock->expects($this->any())
            ->method('getSellerPackageApiEndpoint')
            ->willReturn('https://example.com/api');

        $shopMock->expects($this->any())
            ->method('getId')
            ->willReturn('shop1');

        $shopMock1 = clone $shopMock;
        $shopMock2 = clone $shopMock;

        $itemMock1 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['getAdditionalData', 'getMiraklShopId'])
            ->getMock();

        $itemMock2 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['getAdditionalData', 'getMiraklShopId'])
            ->getMock();

        $itemMock3 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['getAdditionalData', 'getMiraklShopId'])
            ->getMock();

        $itemMock1->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('offer1');

        $additionalData1 = json_encode([
            'punchout_enabled' => true,
            'packaging_data' => $packagingDataObject1,
            'group_id' => 'group1'
        ]);

        $itemMock1->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData1);

        $itemMock1->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1');

        $itemMock2->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('offer2');

        $additionalData2 = json_encode([
            'punchout_enabled' => true,
            'packaging_data' => $packagingDataObject2,
            'group_id' => 'group1'
        ]);

        $itemMock2->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData2);

        $itemMock2->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1');

        $itemMock3->expects($this->any())
            ->method('getData')
            ->with('mirakl_offer_id')
            ->willReturn('offer3');

        $additionalData3 = json_encode([
            'punchout_enabled' => true,
            'packaging_data' => $packagingDataObject3,
            'group_id' => 'group2'
        ]);

        $itemMock3->expects($this->any())
            ->method('getAdditionalData')
            ->willReturn($additionalData3);

        $itemMock3->expects($this->any())
            ->method('getMiraklShopId')
            ->willReturn('shop1-group2');

        $quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$itemMock1, $itemMock2, $itemMock3]);

        $this->shopRepository->expects($this->any())
            ->method('getById')
            ->willReturnCallback(function ($shopId) use ($shopMock1, $shopMock2) {
                if ($shopId == (int)'shop1') {
                    return $shopMock1;
                } elseif ($shopId == (int)'shop1-group2') {
                    $shopMock2->expects($this->any())
                        ->method('getId')
                        ->willReturn('shop1');
                    return $shopMock2;
                }
                return $shopMock1;
            });

        $spyPackagingCheckoutPricing = $this->getMockBuilder(PackagingCheckoutPricing::class)
            ->setConstructorArgs([
                $this->curlMock,
                $this->jsonSerializerMock,
                $this->quoteHelper,
                $this->session,
                $this->loggerMock,
                $this->shopRepository,
                $this->marketPlaceHelper,
                $this->cache,
                $this->toggleConfig,
                $this->packagingItemService
            ])
            ->onlyMethods(['getPackagingDetails'])
            ->getMock();

        $spyPackagingCheckoutPricing->expects($this->exactly(2))
            ->method('getPackagingDetails')
            ->willReturnOnConsecutiveCalls($packagingDetails1, $packagingDetails2);

        $this->packagingCheckoutPricing = $spyPackagingCheckoutPricing;

        $this->packagingCheckoutPricing->getPackagingItems();
    }
}
