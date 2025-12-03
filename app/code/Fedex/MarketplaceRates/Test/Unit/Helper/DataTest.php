<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceRates
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceRates\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceRates\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\LocalizedException;
use \Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data as Helper;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment;
use \Magento\Sales\Api\Data\OrderItemInterface;
use \Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Quote\Api\Data\CartItemInterface;

class DataTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplaceCheckout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketplaceHelper;
    /**
     * @var (\Magento\Sales\Api\OrderItemRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderItemRepository;
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $configMock;

    /**
     * @var Session
     */
    protected $customerSessionMock;

    /**
     * @var LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var Curl
     */
    protected $curlMock;

    /**
     * @var EncryptorInterface
     */
    protected $encryptorMock;

    /**
     * @var HandleMktCheckout
     */
    protected $handleMktCheckout;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var Shipment
     */
    protected $shipment;

    /**
     * @var OrderItemInterface
     */
    protected $orderItem;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var CartItemInterface
     */
    protected $cartItem;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $orderMock;
    private const MAX_RETRIES = 3;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ScopeConfigInterface::class);
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(
                [
                    'getFedexRatesToken',
                    'setFedexRatesToken',
                    'setFedexRatesExpirationTime',
                    'getFedexRatesExpirationTime'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical', 'error'])
            ->getMockForAbstractClass();

        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->setMethods(['willThrowException','getBody','post'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->encryptorMock = $this->createMock(EncryptorInterface::class);

        $this->handleMktCheckout = $this->createMock(HandleMktCheckout::class);
        $this->marketplaceHelper = $this->createMock(MarketplaceHelper::class);
        $this->orderItemRepository = $this->createMock(OrderItemRepositoryInterface::class);
        $this->helper = $this->createMock(Helper::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->item = $this->getMockBuilder(Item::class)
        ->setMethods(['getOrderItemId', 'getAdditionalData','getData'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->shipment = $this->getMockBuilder(Item::class)
        ->setMethods(['getOrderItemId', 'getAdditionalData', 'getItems'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->orderItem = $this->createMock(OrderItemInterface::class);
        $this->collection = $this->createMock(Collection::class);
        $this->collection = $this->getMockBuilder(Collection::class)
        ->setMethods(['getAllItems'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->cartItem = $this->getMockBuilder(CartItemInterface::class)
        ->setMethods(['getMiraklShopId', 'getAdditionalData'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
        ->setMethods(['getAllItems', 'getMiraklShippingFee'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->dataHelper = new Data(
            $this->createMock(\Magento\Framework\App\Helper\Context::class),
            $this->configMock,
            $this->customerSessionMock,
            $this->loggerMock,
            $this->curlMock,
            $this->encryptorMock,
            $this->handleMktCheckout,
            $this->marketplaceHelper,
            $this->orderItemRepository,
            $this->helper,
            $this->toggleConfig
        );
    }

    /**
     * Test getFedexRatesToken() method.
     *
     * @return void
     */
    public function testGetFedexRatesWithNoConfig(): void
    {
        $setupURL = null;
        $tokenUrl = null;
        $clientId = null;
        $clientSecret = null;

        $this->configMock->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $setupURL,
                $tokenUrl,
                $clientId,
                $clientSecret
            );

        $this->customerSessionMock->expects($this->any())
            ->method('getFedexRatesToken')
            ->willReturn(null);

        $this->dataHelper->getFedexRatesToken();
    }

    /**
     * Test getFedexRatesToken() method.
     *
     * @return void
     */
    public function testGetFedexRatesTokenNotExpired(): void
    {
        $setupURL = 'https://example.com/rate/v1/rates/quotes';
        $tokenUrl = 'https://example.com/oauth/token';
        $clientId = null;
        $clientSecret = 'clientSecret1';

        $this->encryptorMock->expects($this->any())
            ->method('decrypt')
            ->willReturnOnConsecutiveCalls(
                $clientId,
                $clientSecret
            );

        $this->configMock->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $setupURL,
                $tokenUrl,
                $clientId,
                $clientSecret
            );

        $this->customerSessionMock->expects($this->any())
            ->method('getFedexRatesExpirationTime')
            ->willReturn(999999999999);

        $return = [
            'output' => [
                'rateReplyDetails' => 'asd'
            ]
        ];

        $this->customerSessionMock->expects($this->any())
            ->method('getFedexRatesToken')
            ->willReturn($return);

        $this->dataHelper->getFedexRatesToken();
    }

    /**
     * Test getResponseFromFedexRatesAPI() method.
     *
     * @return void
     */
    public function testGetResponseFromFedexRatesAPI()
    {
        $data = "{\"accountNumber\":{\"value\":\"740561073\"},\"requestedShipment\":{\"shipper\":{\"address\":
        {\"postalCode\":65247,\"countryCode\":\"US\"}},\"recipient\":{\"address\":{\"postalCode\":75063,
        \"countryCode\":\"US\",\"residential\":true}},\"shipDateStamp\":\"2023-03-29\",
        \"pickupType\":\"DROPOFF_AT_FEDEX_LOCATION\",\"rateRequestType\":[\"ACCOUNT\"],\"requestedPackageLineItems\":
        [{\"weight\":{\"units\":\"LB\",\"value\":10},\"dimensions\":{\"length\":10,\"width\":8,\"height\":2,
        \"units\":\"IN\"}}]},\"carrierCodes\":[\"FDXE\",\"FDXG\"]}";
        $setupURL = 'https://example.com/rate/v1/rates/quotes';
        $tokenUrl = 'https://example.com/oauth/token';
        $clientId = 1;
        $clientSecret = 'clientSecret1';
        $response = "{\"access_token\":\"eyJhOiJSUz\",\"token_type\":\"bearer\",\"expires_in\":3599,\"scope\":\"CXS\"}";
        $responseRates =[
            'output' => [
                'rateReplyDetails' => 'asd'
            ]
        ];
        $customerShippingAccount3PEnabled = false;
        $shipAccountNumber = '123';

        $this->encryptorMock->expects($this->any())
            ->method('decrypt')
            ->willReturnOnConsecutiveCalls(
                $clientId,
                $clientSecret
            );

        $this->configMock->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $setupURL,
                $tokenUrl,
                $clientId,
                $clientSecret
            );
        $this->curlMock->expects($this->any())
            ->method('post')
            ->willReturnOnConsecutiveCalls(
                $this->returnValue('{"error": "Something went wrong"}'),
                $this->returnValue('{"access_token": "token123"}'),
                $this->returnValue('{"access_token": "token123"}')
            );

        $this->customerSessionMock->expects($this->any())
            ->method('setFedexRatesExpirationTime')
            ->with($this->isType('int'));

        $return = $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willReturnOnConsecutiveCalls(
                $response,
                json_encode($responseRates)
            );

        $this->customerSessionMock->expects($this->any())
            ->method('getFedexRatesToken')
            ->willReturn($return);

        $this->customerSessionMock->expects($this->any())
            ->method('setFedexRatesToken')
            ->with($return);

        $response = $this->dataHelper->getResponseFromFedexRatesAPI(
            $customerShippingAccount3PEnabled,
            $shipAccountNumber,
            $data
        );

        $this->assertEquals($responseRates['output']['rateReplyDetails'], $response);
    }

    /**
     * Test getResponseFromFedexRatesAPI() method.
     *
     * @return void
     */
    public function testGetResponseFromFedexRatesAPINull()
    {
        $data = "{\"accountNumber\":{\"value\":\"740561073\"},\"requestedShipment\":{\"shipper\":{\"address\":
        {\"postalCode\":65247,\"countryCode\":\"US\"}},\"recipient\":{\"address\":{\"postalCode\":75063,
        \"countryCode\":\"US\",\"residential\":true}},\"shipDateStamp\":\"2023-03-29\",
        \"pickupType\":\"DROPOFF_AT_FEDEX_LOCATION\",\"rateRequestType\":[\"ACCOUNT\"],\"requestedPackageLineItems\":
        [{\"weight\":{\"units\":\"LB\",\"value\":10},\"dimensions\":{\"length\":10,\"width\":8,\"height\":2,
        \"units\":\"IN\"}}]},\"carrierCodes\":[\"FDXE\",\"FDXG\"]}";
        $setupURL = 'https://example.com/rate/v1/rates/quotes';
        $tokenUrl = 'https://example.com/oauth/token';
        $clientId = 1;
        $clientSecret = 'clientSecret1';
        $response = "{\"access_token\":\"eyJhOiJSUz\",\"token_type\":\"bearer\",\"expires_in\":3599,\"scope\":\"CXS\"}";
        $responseRates =[
            'output' => [
                'rateReplyDetails_not_set' => 'asd'
            ]
        ];
        $customerShippingAccount3PEnabled = false;
        $shipAccountNumber = '123';

        $this->encryptorMock->expects($this->any())
            ->method('decrypt')
            ->willReturnOnConsecutiveCalls(
                $clientId,
                $clientSecret
            );

        $this->configMock->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $setupURL,
                $tokenUrl,
                $clientId,
                $clientSecret
            );
        $this->curlMock->expects($this->any())
            ->method('post')
            ->willReturnOnConsecutiveCalls(
                $this->returnValue('{"error": "Something went wrong"}'),
                $this->returnValue('{"access_token": "token123"}'),
                $this->returnValue('{"access_token": "token123"}')
            );

        $this->customerSessionMock->expects($this->any())
            ->method('setFedexRatesExpirationTime')
            ->with($this->isType('int'));

        $return = $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willReturnOnConsecutiveCalls(
                $response,
                json_encode($responseRates)
            );

        $this->customerSessionMock->expects($this->any())
            ->method('getFedexRatesToken')
            ->willReturn($return);

        $this->customerSessionMock->expects($this->any())
            ->method('setFedexRatesToken')
            ->with($return);

        $response = $this->dataHelper->getResponseFromFedexRatesAPI(
            $customerShippingAccount3PEnabled,
            $shipAccountNumber,
            $data
        );

        $this->assertEquals(null, $response);
    }

    /**
     * Test setFedexRatesTokenInfo() method.
     *
     * @return void
     */
    public function testSetFedexRatesTokenInfo()
    {
        $gatewayToken = 'token';
        $response = ['expires_in' => 3600];

        $this->customerSessionMock->expects($this->any())
            ->method('setFedexRatesToken')
            ->with($gatewayToken);

        $this->customerSessionMock->expects($this->any())
            ->method('setFedexRatesExpirationTime')
            ->with(time() + $response["expires_in"] ?? 3600);

        $this->dataHelper->setFedexRatesTokenInfo($gatewayToken, $response);
    }

    /**
     * @return void
     */
    public function testGetFedexRatesToken()
    {
        $this->customerSessionMock->method('getFedexRatesToken')->willReturn('test_token');
        $this->customerSessionMock->method('getFedexRatesExpirationTime')->willReturn(time() + 3600);

        $this->assertEquals('test_token', $this->dataHelper->getFedexRatesToken());
    }

    /**
     * @return void
     */
    public function testGetFedexRatesTokenExpired()
    {
        $this->customerSessionMock->method('getFedexRatesToken')->willReturn('new_token');
        $this->customerSessionMock->method('getFedexRatesExpirationTime')->willReturn(time() - 3600);

        $this->configMock->method('getValue')->willReturnMap([
            ['fedex/fedex_rate_quotes/token_url', ScopeInterface::SCOPE_STORE, null, 'http://example.com/token'],
            ['fedex/fedex_rate_quotes/client_id', ScopeInterface::SCOPE_STORE, null, 'encrypted_id'],
            ['fedex/fedex_rate_quotes/client_secret', ScopeInterface::SCOPE_STORE, null, 'encrypted_secret']
        ]);

        $this->encryptorMock->method('decrypt')->willReturnMap([
            ['encrypted_id', 'decrypted_id'],
            ['encrypted_secret', 'decrypted_secret']
        ]);

        $this->curlMock->expects($this->once())->method('post');
        $this->curlMock->method('getBody')->willReturn(
            json_encode(
                ['access_token' => 'new_token', 'expires_in' => 3600]
            )
        );

        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);

        $this->assertEquals('new_token', $this->dataHelper->getFedexRatesToken());
    }

      /**
       * @return void
       */
    public function testGetFedexRatesException()
    {
        $this->customerSessionMock->method('getFedexRatesToken')->willReturn('new_token');
        $this->customerSessionMock->method('getFedexRatesExpirationTime')->willReturn(time() - 3600);

        $this->configMock->method('getValue')->willReturnMap([
            ['fedex/fedex_rate_quotes/token_url', ScopeInterface::SCOPE_STORE, null, 'http://example.com/token'],
            ['fedex/fedex_rate_quotes/client_id', ScopeInterface::SCOPE_STORE, null, 'encrypted_id'],
            ['fedex/fedex_rate_quotes/client_secret', ScopeInterface::SCOPE_STORE, null, 'encrypted_secret']
        ]);

        $this->encryptorMock->method('decrypt')->willReturnMap([
            ['encrypted_id', 'decrypted_id'],
            ['encrypted_secret', 'decrypted_secret']
        ]);

        $this->curlMock->expects($this->once())->method('post');
        $this->curlMock->method('getBody')->willReturn(json_encode(['error' => 'something went wrong!!']));
        $this->loggerMock->expects($this->any())
            ->method('critical')
            ->willReturnSelf();
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $result = $this->dataHelper->getFedexRatesToken();
        $this->assertEquals(null, $result);
    }

    public function testHandleMethodTitle()
    {
        $method = 'fedex_1';
        $title = 'FedEx 1 Day';
        $expectedTitle = 'FedEx 1 Day fedex_1';

        $this->configMock->method('getValue')->willReturn($title);
        $result = $this->dataHelper->handleMethodTitle($method);
        $this->assertEquals($expectedTitle, $result);
    }

    public function testHandleMethodTitleIfCondition()
    {
        $method = 'FedEx';
        $title = 'FedEx 1 Day';
        $expectedTitle = 'FedEx';

        $this->configMock->method('getValue')->willReturn($title);
        $result = $this->dataHelper->handleMethodTitle($method);
        $this->assertEquals($expectedTitle, $result);
    }

    public function testGetFreightShippingRatesUrl()
    {
        $this->configMock->method('getValue')->willReturn("test");
        $result = $this->dataHelper->getFreightShippingRatesUrl();
        $this->assertEquals("test", $result);
    }

    public function testIsFreightShippingEnabled()
    {
        $this->configMock->method('isSetFlag')->willReturn(true);
        $result = $this->dataHelper->isFreightShippingEnabled();
        $this->assertEquals(true, $result);
    }

    public function testGetFreightShippingSurchargeText()
    {
        $this->configMock->method('getValue')->willReturn("test");
        $result = $this->dataHelper->getFreightShippingSurchargeText();
        $this->assertEquals("test", $result);
    }

    public function testIsd216504toggleEnabled()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $result = $this->dataHelper->isd216504toggleEnabled();
        $this->assertEquals(true, $result);
    }

    public function testGetOrderItemMiraklShippingData()
    {
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->item->method('getOrderItemId')->willReturn("123");
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->orderItem->method('getAdditionalData')->willReturn('{"mirakl_shipping_data":"test"}');

        $result = $this->dataHelper->getOrderItemMiraklShippingData($this->shipment);
        $this->assertEquals('test', $result);
    }

    public function testGetOrderItemMiraklShippingDataWhenMiraklShipppingIsNotSet()
    {
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->item->method('getOrderItemId')->willReturn("123");
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->orderItem->method('getAdditionalData')->willReturn('{"mirakl_shipping":"test"}');

        $result = $this->dataHelper->getOrderItemMiraklShippingData($this->shipment);
        $this->assertEquals(null, $result);
    }

    public function testGetMktShippingAddressReturnsAddress()
    {
        $address = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'postal_code' => '12345',
            'country_code' => 'US'
        ];

        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn('1234');
        $this->cartItem->method('getAdditionalData')->willReturn(
            json_encode(
                ['mirakl_shipping_data' => ['address' => $address]]
            )
        );
        $result = $this->dataHelper->getMktShippingAddress($this->orderMock);
        $this->assertEquals($address, $result);
    }

    public function testGetMktShippingAddressReturnsNullWhenNoAddress()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn('1234');
        $this->cartItem->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data' => []]));
        $result = $this->dataHelper->getMktShippingAddress($this->orderMock);
        $this->assertEquals(null, $result);
    }

    public function testGetMktShippingTotalAmountPerItem()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->cartItem->method('getAdditionalData')->willReturn(
            json_encode(
                ['mirakl_shipping_data' => ['amount' => 100]]
            )
        );
        $this->cartItem->method('getItemId')->willReturn('421');

        $result = $this->dataHelper->getMktShippingTotalAmountPerItem($this->orderMock);
        $this->assertEquals(['421'=>100], $result);
    }

    public function testGetMktShippingTotalAmountPerItemWithNoAmount()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->cartItem->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data' => []]));

        $result = $this->dataHelper->getMktShippingTotalAmountPerItem($this->orderMock);
        $this->assertEquals([], $result);
    }

    public function testGetMktShippingTotalAmount()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->cartItem->method('getAdditionalData')->willReturn(
            json_encode(['mirakl_shipping_data' => ['amount' => 100]])
        );
        $result = $this->dataHelper->getMktShippingTotalAmount($this->orderMock, $this->shipment);
        $this->assertEquals(100, $result);
    }

    public function testGetMktShippingTotalAmountToggleOff()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->cartItem->method('getAdditionalData')->willReturn(
            json_encode(['mirakl_shipping_data' => ['amount' => 100]])
        );
        $result = $this->dataHelper->getMktShippingTotalAmount($this->orderMock, $this->shipment);
        $this->assertEquals(100, $result);
    }

    public function testGetMktShippingTotalAmountWithAmountZero()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->cartItem->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data' => []]));
        $result = $this->dataHelper->getMktShippingTotalAmount($this->orderMock, $this->shipment);
        $this->assertEquals(0, $result);
    }

    public function testGetMktShippingTotalAmountWithFilteredItems()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem, $this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->orderItem->method('getAdditionalData')->willReturn(
            json_encode(['mirakl_shipping_data' => ['amount' => 100]])
        );
        $result = $this->dataHelper->getMktShippingTotalAmount($this->orderMock, $this->shipment);
        $this->assertEquals(100, $result);
    }

    public function testGetMktShippingTotalAmountWithFilteredItemsWithAmountNull()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem, $this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->orderItem->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data' => []]));
        $result = $this->dataHelper->getMktShippingTotalAmount($this->orderMock, $this->shipment);
        $this->assertEquals(0, $result);
    }

    public function testGetMktShippingTotalAmountWithFilteredItemsWithShipmentNull()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem, $this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $result = $this->dataHelper->getMktShippingTotalAmount($this->orderMock, null);
        $this->assertEquals(0, $result);
    }

    public function testGetMktShipping()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem, $this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->orderItem->method('getAdditionalData')->willReturn(json_encode([]));
        $result = $this->dataHelper->getMktShipping($this->orderMock, null, $this->shipment);
        $this->assertEquals(null, $result);
    }

    public function testGetMktShippingWithShippingData()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem, $this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->shipment->method('getItems')->willReturn([$this->item]);
        $this->shipment->method('getOrderItemId')->willReturn(12);
        $this->orderItemRepository->method('get')->willReturn($this->orderItem);
        $this->orderItem->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data'=>['100']]));
        $result = $this->dataHelper->getMktShipping($this->orderMock, null, $this->shipment);
        $this->assertEquals(['100'], $result);
    }

    public function testGetMktShippingWithShippingDataWithItem()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem, $this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->item->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data'=>['100']]));
        $result = $this->dataHelper->getMktShipping($this->orderMock, $this->item, null);
        $this->assertEquals(['100'], $result);
    }

    public function testGetMktShippingWithShippingDataWithOneItem()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->item->method('getData')->willReturn(12);
        $this->cartItem->method('getAdditionalData')->willReturn(
            json_encode(['mirakl_shipping_data'=>['seller_id'=>'12']])
        );
        $result = $this->dataHelper->getMktShipping($this->orderMock, $this->item, null);
        $this->assertEquals(['seller_id'=>'12'], $result);
    }

    public function testGetMktShippingWithShippingDataNotMiraklShippingData()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->item->method('getData')->willReturn(12);
        $this->cartItem->method('getAdditionalData')->willReturn(json_encode([]));
        $result = $this->dataHelper->getMktShipping($this->orderMock, $this->item, null);
        $this->assertEquals(null, $result);
    }

    public function testGetMktShippingWithShippingDataNullSeller()
    {
        $this->orderMock->method('getAllItems')->willReturn([$this->cartItem]);
        $this->cartItem->method('getMiraklShopId')->willReturn(['1234']);
        $this->item->method('getData')->willReturn(null);
        $this->cartItem->method('getAdditionalData')->willReturn(
            json_encode(['mirakl_shipping_data'=>['seller_id'=>'12']])
        );
        $result = $this->dataHelper->getMktShipping($this->orderMock, $this->item, null);
        $this->assertEquals(['seller_id'=>'12'], $result);
    }
}
