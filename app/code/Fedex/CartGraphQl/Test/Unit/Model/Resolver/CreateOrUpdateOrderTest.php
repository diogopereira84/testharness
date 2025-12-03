<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\Cart\Model\Quote\IntegrationItem\Repository as IntegrationItemRepository;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Magento\Framework\Phrase;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\Cart\Model\Quote\Integration\Command\SaveRetailCustomerIdInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\CartGraphQl\Model\Resolver\CreateOrUpdateOrder;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Magento\Framework\GraphQl\Config\Element\Field;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Quote\Model\Quote\AddressFactory;
use Fedex\CartGraphQl\Model\Address\Builder;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\Stdlib\DateTime;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote\Item;

class CreateOrUpdateOrderTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var CartIntegrationRepositoryInterface|MockObject
     */
    protected $cartItemMock;

    /**
     * @var Product|MockObject
     */
    protected $cartItemProductMock;

    /**
     * @var CreateOrUpdateOrder
     */
    private CreateOrUpdateOrder $createOrUpdateOrder;

    /**
     * Mock object for the CartIntegrationRepository used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $cartIntegrationRepositoryMock;

    /**
     * Mock object for the CartRepository interface used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $cartRepositoryMock;

    /**
     * Mock object for the Address Builder used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $addressBuilderMock;

    /**
     * Mock object for the FXORateQuote used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $fxoRateQuoteMock;

    /**
     * Mock object for the IntegrationItemRepository used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $integrationItemRepositoryMock;

    /**
     * Mock object for the AddressFactory used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $addressFactoryMock;

    /**
     * Mock object for the DateTime used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $dateTimeMock;

    /**
     * Mock object for the InstoreConfig used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $instoreConfigMock;

    /**
     * Mock object for the SaveRetailCustomerIdInterface used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $saveRetailCustomerIdMock;

    /**
     * Mock object for the Request used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $requestMock;

    /**
     * Mock object for the TotalsCollector used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $totalsCollectorMock;

    /**
     * Mock object for the SetFedexAccountNumber used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $setFedexAccountNumberMock;

    /**
     * Mock object for the Cart model used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $cartModelMock;

    /**
     * Mock object for the RequestCommandFactory used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $requestCommandFactoryMock;

    /**
     * Mock object for the BatchResponseFactory used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $batchResponseFactoryMock;

    /**
     * Mock object for the LoggerHelper used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $loggerHelperMock;

    /**
     * Mock object for the ValidationComposite used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $validationCompositeMock;

    /**
     * Mock object for the ContextInterface used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $contextMock;

    /**
     * Mock object for the Field used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $fieldMock;

    /**
     * Mock object for the BatchResponse used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $batchResponseMock;

    /**
     * Mock object for the Quote used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $quoteMock;

    /**
     * Mock object for the CartIntegrationInterface used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $integrationMock;

    /**
     * Mock object for the QuoteItem used in unit tests.
     *
     * @var MockObject
     */
    private MockObject $requestQueryValidator;

    /**
     * Sets up the test environment by initializing mock objects and the CreateOrUpdateOrder instance.
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->cartIntegrationRepositoryMock = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->addressBuilderMock = $this->createMock(Builder::class);
        $this->fxoRateQuoteMock = $this->createMock(FXORateQuote::class);
        $this->integrationItemRepositoryMock = $this->createMock(IntegrationItemRepository::class);
        $this->addressFactoryMock = $this->createMock(AddressFactory::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->saveRetailCustomerIdMock = $this->createMock(SaveRetailCustomerIdInterface::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->totalsCollectorMock = $this->createMock(TotalsCollector::class);
        $this->setFedexAccountNumberMock = $this->createMock(SetFedexAccountNumber::class);
        $this->cartModelMock = $this->createMock(Cart::class);
        $this->requestCommandFactoryMock = $this->createMock(RequestCommandFactory::class);
        $this->batchResponseFactoryMock = $this->createMock(BatchResponseFactory::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->validationCompositeMock = $this->createMock(ValidationBatchComposite::class);
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->batchResponseMock = $this->createMock(BatchResponse::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->integrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->cartItemMock = $this->createMock(QuoteItem::class);
        $this->cartItemProductMock = $this->createMock(Product::class);
        $this->requestQueryValidator = $this->getMockBuilder(RequestQueryValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createOrUpdateOrder = new CreateOrUpdateOrder(
            $this->cartIntegrationRepositoryMock,
            $this->cartRepositoryMock,
            $this->addressBuilderMock,
            $this->fxoRateQuoteMock,
            $this->integrationItemRepositoryMock,
            $this->addressFactoryMock,
            $this->dateTimeMock,
            $this->instoreConfigMock,
            $this->saveRetailCustomerIdMock,
            $this->requestMock,
            $this->totalsCollectorMock,
            $this->setFedexAccountNumberMock,
            $this->requestQueryValidator,
            $this->cartModelMock,
            $this->requestCommandFactoryMock,
            $this->batchResponseFactoryMock,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders
        );
    }

    /**
     * Tests the proceed functionality for creating or updating an order.
     *
     * @return void
     */
    public function testProceed(): void
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);
        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $requestMockData = [
            "input" => [
                "user_jwt" => "123",
                "cart_id" => "123",
                "contact_information" => [
                    "retail_customer_id" => "1003809192",
                    "firstname" => "John",
                    "lastname" => "Doe",
                    "email" => "john.doe@mail.com",
                    "telephone" => "(123) 456-7890",
                    "organization" => "test123",
                    "ext" => "1234",
                    "alternate_contact" => [
                        "firstname" => "Mary",
                        "lastname" => "Doe",
                        "email" => "mary.doe@mail.com",
                        "telephone" => "(098) 765-4321",
                        "ext" => "5678",
                    ]
                ],
                "pickup_data" => [
                    "pickup_location_id" => 1492,
                    "pickup_store_id" => 1492,
                    "pickup_location_name" => "Ft. Lauderdale FL SE 17th",
                    "pickup_location_street" => "1501 SE 17TH ST",
                    "pickup_location_city" => "Fort Lauderdale",
                    "pickup_location_state" => "FL",
                    "pickup_location_zipcode" => "33316",
                    "pickup_location_country" => "US",
                    "pickup_location_date" => "2021-12-22T10:30:00"
                ]
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $this->quoteMock->method('getShippingAddress')->willReturn($this->createMock(Address::class));
        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $this->cartRepositoryMock->method('save')->willReturnSelf();
        $this->addressBuilderMock->method('setAddressData')->willReturnSelf();
        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $this->setFedexAccountNumberMock->method('setFedexAccountNumber')->willReturnSelf();
        $pickupLocationDate = "2021-12-22 10:30:00";
        $this->quoteMock->expects(static::any())
            ->method('getId')
            ->willReturn(1);

        $this->cartIntegrationRepositoryMock->expects(static::any())
            ->method('getByQuoteId')
            ->with(1)
            ->willReturn($this->integrationMock);

        $this->dateTimeMock->expects(static::any())
            ->method('formatDate')
            ->with($requestMockData['input']['pickup_data']['pickup_location_date'], true)
            ->willReturn($pickupLocationDate);

        $this->integrationMock->expects(static::any())
            ->method('setPickupLocationDate')
            ->with($pickupLocationDate);

        $this->saveRetailCustomerIdMock->expects(static::any())
            ->method('execute')
            ->with(
                $this->integrationMock,
                $requestMockData['input']['contact_information']['retail_customer_id']
            );
        $this->cartItemProductMock->method('getName')->willReturn('item_1');
        $this->cartItemMock->method('getSku')->willReturn('item_sku_1');
        $this->cartItemMock->method('getQty')->willReturn(2);
        $this->cartItemMock->method('getProduct')->willReturn($this->cartItemProductMock);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$this->cartItemMock]);
        $response = $this->createOrUpdateOrder->proceed($this->contextMock, $this->fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    /**
     * Tests that the in-store update of the shipping address returns early when the pickup data is empty.
     */
    public function testInStoreUpdateShippingAddressReturnsEarlyOnEmptyPickupData()
    {
        $quoteMock = $this->createMock(Quote::class);
        $pickupData = [];
        $shippingContact = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'telephone' => '1234567890'
        ];

        $quoteMock->expects($this->never())->method('getShippingAddress');
        $quoteMock->expects($this->never())->method('setShippingAddress');
        $quoteMock->expects($this->never())->method('save');
        $this->addressFactoryMock->expects($this->never())->method('create');

        $reflection = new \ReflectionClass($this->createOrUpdateOrder);
        $method = $reflection->getMethod('inStoreUpdateShippingAddress');
        $method->setAccessible(true);

        $method->invoke($this->createOrUpdateOrder, $quoteMock, $pickupData, $shippingContact);
    }

    /**
     * Tests that a new shipping address is created when the shipping address is empty during an in-store update.
     */
    public function testInStoreUpdateShippingAddressCreatesNewAddressWhenShippingAddressIsEmpty()
    {
        $quoteMock = $this->createMock(Quote::class);
        $pickupData = [
            'pickup_location_street' => '123 Main St',
            'pickup_location_city' => 'Metropolis',
            'pickup_location_country' => 'US',
            'pickup_location_zipcode' => '12345'
        ];
        $shippingContact = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'telephone' => '1234567890'
        ];

        $quoteMock->method('getShippingAddress')->willReturn(null);

        $addressMock = $this->createMock(Address::class);
        $this->addressFactoryMock->expects($this->once())->method('create')->willReturn($addressMock);

        $addressMock->expects($this->once())->method('addData')->with($this->arrayHasKey('firstname'));
        $quoteMock->expects($this->once())->method('setShippingAddress')->with($addressMock);
        $quoteMock->expects($this->once())->method('save');

        $reflection = new \ReflectionClass($this->createOrUpdateOrder);
        $method = $reflection->getMethod('inStoreUpdateShippingAddress');
        $method->setAccessible(true);

        $method->invoke($this->createOrUpdateOrder, $quoteMock, $pickupData, $shippingContact);
    }

    /**
     * Tests that the in-store save pickup location date method returns early when pickup data is empty.
     */
    public function testInStoreSavePickupLocationDateReturnsEarlyOnEmptyPickupData()
    {
        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $pickupData = [];
        $headerArray = ['test' => 'header'];

        $this->loggerHelperMock->expects($this->never())->method('error');
        $this->dateTimeMock->expects($this->never())->method('formatDate');

        $reflection = new \ReflectionClass($this->createOrUpdateOrder);
        $method = $reflection->getMethod('inStoreSavePickupLocationDate');
        $method->setAccessible(true);

        $method->invoke($this->createOrUpdateOrder, $integrationMock, $pickupData, $headerArray);
    }

    /**
     * Tests the in-store save pickup location date functionality and verifies that exceptions are logged correctly.
     */
    public function testInStoreSavePickupLocationDateLogsException()
    {
        $integrationMock = $this->createMock(CartIntegrationInterface::class);
        $pickupData = ['pickup_location_date' => '2021-12-22T10:30:00'];
        $headerArray = ['test' => 'header'];

        $this->dateTimeMock->method('formatDate')
            ->willThrowException(new \Exception('Format error'));

        $this->loggerHelperMock->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error on saving data on quote integration. Format error'),
                $headerArray
            );

        $reflection = new \ReflectionClass($this->createOrUpdateOrder);
        $method = $reflection->getMethod('inStoreSavePickupLocationDate');
        $method->setAccessible(true);

        $method->invoke($this->createOrUpdateOrder, $integrationMock, $pickupData, $headerArray);
    }

    /**
     * Tests that the organization key is set in contactInformation if not present.
     */
    public function testProceedSetsOrganizationIfNotPresent()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'alternate_contact' => [],
            'retail_customer_id' => '1003809192',
            'ext' => '1234',
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $this->cartModelMock->method('setCustomerCartData')
            ->willReturnCallback(function ($quote, $shippingContact, $alternateContact) {
                if ($alternateContact !== null && !is_array($alternateContact)) {
                    throw new \InvalidArgumentException('alternateContact must be array or null');
                }
                return $this->cartModelMock;
            });

        $this->addressBuilderMock->method('setAddressData')->willReturnSelf();
        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $this->cartRepositoryMock->method('save')->willReturnSelf();
        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $totalMock = $this->createMock(Total::class);
        $this->totalsCollectorMock->method('collectQuoteTotals')->willReturn($totalMock);
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);

        $this->integrationMock->method('getStoreId')->willReturn(1);
        $this->integrationMock->method('getLocationId')->willReturn(1);
        $this->integrationMock->method('getRaqNetAmount')->willReturn(100.0);

        $this->integrationMock->method('getPickupLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupStoreId')->willReturn(1);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
        $this->quoteMock->method('getGrandTotal')->willReturn(100);
        $this->quoteMock->method('getCustomTaxAmount')->willReturn(10);
        $this->quoteMock->method('getGtn')->willReturn('GTN123');

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();

        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();

        $this->loggerHelperMock->method('info')->willReturnSelf();

        $response = $this->createOrUpdateOrder->proceed($this->contextMock, $this->fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    /**
     * Tests that Fedex account numbers are set when the config is enabled and account numbers are present.
     */
    public function testProceedSetsFedexAccountNumbersIfEnabled()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'retail_customer_id' => '1003809192',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'ext' => '1234',
            'fedex_account_number' => 'FAKE123',
            'fedex_ship_account_number' => 'SHIP456',
            'alternate_contact' => [],
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $this->instoreConfigMock->method('isAddOrUpdateFedexAccountNumberEnabled')->willReturn(true);
        $this->setFedexAccountNumberMock->expects($this->once())
            ->method('setFedexAccountNumber')
            ->with('FAKE123', 'SHIP456', $this->quoteMock);

        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();
        $this->addressBuilderMock->method('setAddressData')->willReturnSelf();
        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $this->cartRepositoryMock->method('save')->willReturnSelf();
        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $totalMock = $this->createMock(\Magento\Quote\Model\Quote\Address\Total::class);
        $this->totalsCollectorMock->method('collectQuoteTotals')->willReturn($totalMock);
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);

        $this->integrationMock->method('getStoreId')->willReturn(1);
        $this->integrationMock->method('getLocationId')->willReturn(1);
        $this->integrationMock->method('getRaqNetAmount')->willReturn(100.0);
        $this->integrationMock->method('getPickupLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupStoreId')->willReturn(1);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
        $this->quoteMock->method('getGrandTotal')->willReturn(100);
        $this->quoteMock->method('getCustomTaxAmount')->willReturn(10);
        $this->quoteMock->method('getGtn')->willReturn('GTN123');

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();
        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();
        $this->loggerHelperMock->method('info')->willReturnSelf();

        $response = $this->createOrUpdateOrder->proceed($this->contextMock, $this->fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    /**
     * Tests that GraphQlFujitsuResponseException is caught, logged, and rethrown in proceed().
     */
    public function testProceedLogsAndThrowsGraphQlFujitsuResponseException()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'retail_customer_id' => '1003809192',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'ext' => '1234',
            'alternate_contact' => [],
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $this->fxoRateQuoteMock->method('getFXORateQuote')
            ->willThrowException(new GraphQlFujitsuResponseException(new Phrase('FXO error')));

        $errorMessages = [];
        $this->loggerHelperMock
            ->method('error')
            ->willReturnCallback(function ($msg) use (&$errorMessages) {
                $errorMessages[] = $msg;
            });

        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();
        $this->addressBuilderMock->method('setAddressData')->willReturnSelf();
        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $this->cartRepositoryMock->method('save')->willReturnSelf();
        $totalMock = $this->createMock(Total::class);
        $this->totalsCollectorMock->method('collectQuoteTotals')->willReturn($totalMock);
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);

        $this->integrationMock->method('getStoreId')->willReturn(1);
        $this->integrationMock->method('getLocationId')->willReturn(1);
        $this->integrationMock->method('getRaqNetAmount')->willReturn(100.0);
        $this->integrationMock->method('getPickupLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupStoreId')->willReturn(1);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
        $this->quoteMock->method('getGrandTotal')->willReturn(100);
        $this->quoteMock->method('getCustomTaxAmount')->willReturn(10);
        $this->quoteMock->method('getGtn')->willReturn('GTN123');

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();
        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();

        try {
            $this->createOrUpdateOrder->proceed($this->contextMock, $this->fieldMock, $requests, $headerArray);
            $this->fail('Expected GraphQlInputException was not thrown');
        } catch (GraphQlInputException $e) {
            $this->assertStringContainsString('Error on saving information into cart FXO error', $e->getMessage());
        } finally {
            $this->assertTrue(
                array_reduce($errorMessages, function ($carry, $msg) {
                    return $carry || stripos($msg, 'fxo error') !== false;
                }, false),
                'Expected error message not found in LoggerHelper::error calls'
            );
        }
    }

    /**
     * Tests that the proceed method saves the cart and sets address data
     * when the shipping address contains a country ID.
     *
     * @return void
     */
    public function testProceedSavesCartAndSetsAddressDataWhenShippingAddressHasCountryId()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'retail_customer_id' => '1003809192',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'ext' => '1234',
            'alternate_contact' => [],
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');
        $quoteShipMock->method('getCountryId')->willReturn('US');

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $this->cartRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->quoteMock);

        $this->addressBuilderMock->expects($this->atLeastOnce())
            ->method('setAddressData')
            ->with($this->quoteMock, $contactInformation, []);

        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $totalMock = $this->createMock(\Magento\Quote\Model\Quote\Address\Total::class);
        $this->totalsCollectorMock->method('collectQuoteTotals')->willReturn($totalMock);
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);

        $this->integrationMock->method('getStoreId')->willReturn(1);
        $this->integrationMock->method('getLocationId')->willReturn(1);
        $this->integrationMock->method('getRaqNetAmount')->willReturn(100.0);
        $this->integrationMock->method('getPickupLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupStoreId')->willReturn(1);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
        $this->quoteMock->method('getGrandTotal')->willReturn(100);
        $this->quoteMock->method('getCustomTaxAmount')->willReturn(10);
        $this->quoteMock->method('getGtn')->willReturn('GTN123');

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();
        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();
        $this->loggerHelperMock->method('info')->willReturnSelf();

        $response = $this->createOrUpdateOrder->proceed($this->contextMock, $this->fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    /**
     * Tests that the proceed method handles NoSuchEntityException correctly and logs the appropriate info message.
     */
    public function testProceedHandlesNoSuchEntityExceptionAndLogsInfo()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'retail_customer_id' => '1003809192',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'ext' => '1234',
            'alternate_contact' => [],
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');
        $quoteShipMock->method('getCountryId')->willReturn('US');

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'getAdditionalData',
                'getRowTotal',
                'getDiscountAmount',
                'getTaxAmount',
                'getProductId',
                'getInstanceId',
                'getDiscount'
            ])
            ->onlyMethods([
                'getProduct',
                'getId',
                'getPrice',
                'getQty'
            ])
            ->getMock();
        $itemMock->method('getProduct')->willReturn($this->cartItemProductMock);
        $itemMock->method('getId')->willReturn(1);
        $itemMock->method('getAdditionalData')->willReturn('{}');
        $itemMock->method('getRowTotal')->willReturn(10);
        $itemMock->method('getPrice')->willReturn(10);
        $itemMock->method('getDiscountAmount')->willReturn(0);
        $itemMock->method('getQty')->willReturn(1);
        $itemMock->method('getTaxAmount')->willReturn(0);
        $itemMock->method('getProductId')->willReturn(123);
        $itemMock->method('getInstanceId')->willReturn('instance_1');
        $itemMock->method('getDiscount')->willReturn(0);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $this->integrationItemRepositoryMock->method('getByQuoteItemId')
            ->willThrowException(new NoSuchEntityException(__('Not found')));

        $this->loggerHelperMock->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->callback(function ($message) {
                    return is_string($message);
                }),
                $headerArray
            );

        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $totalMock = $this->createMock(Total::class);
        $this->totalsCollectorMock->method('collectQuoteTotals')->willReturn($totalMock);
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);

        $this->integrationMock->method('getStoreId')->willReturn(1);
        $this->integrationMock->method('getLocationId')->willReturn(1);
        $this->integrationMock->method('getRaqNetAmount')->willReturn(100.0);
        $this->integrationMock->method('getPickupLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupStoreId')->willReturn(1);

        $this->quoteMock->method('getGrandTotal')->willReturn(100);
        $this->quoteMock->method('getCustomTaxAmount')->willReturn(10);
        $this->quoteMock->method('getGtn')->willReturn('GTN123');

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();
        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();
        $this->loggerHelperMock->method('info')->willReturnSelf();

        $response = $this->createOrUpdateOrder->proceed($this->contextMock, $this->fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    /**
     * Tests that when isGraphQl() is true, cartTotals and integration methods are called and their values are used.
     */
    public function testProceedGraphQlTotalsAreSetCorrectly()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'retail_customer_id' => '1003809192',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'ext' => '1234',
            'alternate_contact' => [],
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $this->requestQueryValidator->method('isGraphQl')->willReturn(true);

        $cartTotalsMock = $this->getMockBuilder(Total::class)
            ->addMethods(['getSubtotal', 'getCustomTaxAmount', 'getFedexDiscountAmount'])
            ->getMock();
        $cartTotalsMock->method('getSubtotal')->willReturn(200.0);
        $cartTotalsMock->method('getCustomTaxAmount')->willReturn(20.0);
        $cartTotalsMock->method('getFedexDiscountAmount')->willReturn(5.0);
        $this->totalsCollectorMock->method('collectQuoteTotals')->willReturn($cartTotalsMock);

        $this->integrationMock->method('getRaqNetAmount')->willReturn(175.0);
        $this->integrationMock->method('getStoreId')->willReturn(1);
        $this->integrationMock->method('getLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupLocationId')->willReturn(1);
        $this->integrationMock->method('getPickupStoreId')->willReturn(1);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);
        $this->quoteMock->method('getGrandTotal')->willReturn(225.0);
        $this->quoteMock->method('getCustomTaxAmount')->willReturn(20.0);
        $this->quoteMock->method('getGtn')->willReturn('GTN123');

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();
        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();
        $this->loggerHelperMock->method('info')->willReturnSelf();
        $this->addressBuilderMock->method('setAddressData')->willReturnSelf();
        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $this->cartRepositoryMock->method('save')->willReturnSelf();
        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);
        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();

        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);

        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with(
                $requests[0],
                $this->callback(function ($responseData) {
                    $cart = $responseData['cart'];
                    return isset(
                        $cart['gross_amount'],
                        $cart['net_amount'],
                        $cart['tax_amount'],
                        $cart['total_discount_amount']
                    )
                        && $cart['gross_amount'] === 200.0
                        && $cart['net_amount'] === 175.0
                        && $cart['tax_amount'] === 20.0
                        && $cart['total_discount_amount'] === 5.0;
                })
            );

        $response = $this->createOrUpdateOrder->proceed($contextMock, $fieldMock, $requests, $headerArray);
        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    /**
     * Tests that the proceed method correctly extracts product data from additional data
     * when the request is made via GraphQL.
     */
    public function testProceedExtractsProductDataFromAdditionalDataWhenIsGraphQl()
    {
        $requests = [$this->createMock(ResolveRequest::class)];
        $headerArray = [];

        $contactInformation = [
            'retail_customer_id' => '1003809192',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@mail.com',
            'telephone' => '1234567890',
            'ext' => '1234',
            'alternate_contact' => [],
        ];

        $requestMockData = [
            "input" => [
                "cart_id" => "123",
                "contact_information" => $contactInformation,
                "pickup_data" => []
            ]
        ];

        $requests[0]->method('getArgs')->willReturn($requestMockData);

        $quoteShipMock = $this->createMock(Address::class);
        $quoteShipMock->method('getCompany')->willReturn('TestCompany');

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getAllVisibleItems', 'getShippingAddress'])
            ->addMethods(['getGrandTotal', 'getCustomTaxAmount', 'getGtn'])
            ->getMock();

        $this->cartModelMock->method('getCart')->willReturn($this->quoteMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($quoteShipMock);
        $this->cartIntegrationRepositoryMock->method('getByQuoteId')->willReturn($this->integrationMock);

        $this->requestQueryValidator->method('isGraphQl')->willReturn(true);

        $itemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData', 'getDiscount'])
            ->onlyMethods(['getProduct', 'getId', 'getPrice', 'getQty'])
            ->getMock();

        $itemMock->method('getAdditionalData')->willReturn(json_encode([
            'productLinePrice' => 123.45,
            'productRetailPrice' => 99.99
        ]));
        $itemMock->method('getDiscount')->willReturn(10.0);

        $productMock = $this->createMock(Product::class);
        $productMock->method('getName')->willReturn('Test Product');
        $itemMock->method('getProduct')->willReturn($productMock);
        $itemMock->method('getId')->willReturn(1);
        $itemMock->method('getPrice')->willReturn(99.99);
        $itemMock->method('getQty')->willReturn(1);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $this->saveRetailCustomerIdMock->method('execute')->willReturnSelf();
        $this->cartIntegrationRepositoryMock->method('save')->willReturnSelf();
        $this->loggerHelperMock->method('info')->willReturnSelf();
        $this->addressBuilderMock->method('setAddressData')->willReturnSelf();
        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $this->cartRepositoryMock->method('save')->willReturnSelf();
        $this->fxoRateQuoteMock->method('getFXORateQuote')->willReturnSelf();
        $this->batchResponseFactoryMock->method('create')->willReturn($this->batchResponseMock);
        $this->cartModelMock->method('setCustomerCartData')->willReturnSelf();

        $contextMock = $this->createMock(ContextInterface::class);
        $fieldMock = $this->createMock(Field::class);

        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with(
                $requests[0],
                $this->callback(function ($responseData) {
                    $cart = $responseData['cart'];
                    $rateDetails = $cart['rate_details'][0];
                    return isset(
                        $rateDetails['product_line_price'],
                        $rateDetails['retail_price'],
                        $rateDetails['discount_amount']
                    )
                        && $rateDetails['product_line_price'] === 123.45
                        && $rateDetails['retail_price'] === 99.99
                        && $rateDetails['discount_amount'] === 10.0;
                })
            );

        $response = $this->createOrUpdateOrder->proceed($contextMock, $fieldMock, $requests, $headerArray);

        $this->assertInstanceOf(BatchResponse::class, $response);
    }
}
