<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RateQuote;

use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

class ShippingDeliveryTest extends TestCase
{
    /**
     * @var InstoreConfig|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of InstoreConfig used for managing in-store configuration settings.
     */
    private $instoreConfig;

    /**
     * @var CartIntegrationInterface|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of the cart integration service used for handling cart-related operations.
     */
    private $cartIntegration;

    /**
     * @var CartIntegrationInterface|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of the cart integration interface.
     */
    private $cartIntegrationInterface;

    /**
     * @var CartIntegrationRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     * Instance of the cart integration repository.
     *
     * Used to interact with cart integration data within the test.
     */
    private $cartIntegrationRepository;

    /**
     * @var CartIntegrationRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of the cart integration repository interface.
     */
    private $cartIntegrationRepositoryInterface;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of Quote model used in tests.
     */
    private $quote;

    /**
     * @var JsonSerializer|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of the JSON serializer used for encoding and decoding JSON data.
     */
    private $jsonSerializer;

    /**
     * @var DateTime|\PHPUnit\Framework\MockObject\MockObject
     *
     * Instance of DateTime used for handling date and time operations within the test.
     */
    private $dateTime;

    /**
     * @var ShippingDelivery
     *
     * Instance of the ShippingDelivery model used for testing shipping delivery functionality.
     */
    private ShippingDelivery $shippingDelivery;

    private LoggerInterface $logger;

    /**
     * Set up the test environment.
     *
     */
    protected function setUp(): void
    {
        $this->instoreConfig = $this->getMockBuilder(InstoreConfig::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->cartIntegration = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->cartIntegrationInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->cartIntegrationRepository = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->cartIntegrationRepositoryInterface = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->jsonSerializer = $this->getMockBuilder(JsonSerializer::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
           ->disableOriginalConstructor()
           ->setMethods([
                'getQuote',
                'getShipMethod',
                'getFedexShipAccountNumber',
                'getStreetAddress',
                'getCity',
                'getZipcode',
                'getAddressClassification',
                'getId',
           ])
           ->getMock();

        $this->dateTime = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['formatDate'])
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);

        $objectManager = new ObjectManager($this);

        $this->shippingDelivery = $objectManager->getObject(
            ShippingDelivery::class,
            [
                'instoreConfig' => $this->instoreConfig,
                'cartIntegration' => $this->cartIntegration,
                'cartIntegrationRepository' => $this->cartIntegrationRepository,
                'jsonSerializer' => $this->jsonSerializer,
                'dateTime' => $this->dateTime,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Tests that the validateIfLocalDelivery method returns true when the service type is configured.
     *
     * @return void
     */
    public function testValidateIfLocalDeliveryReturnsTrueWhenServiceTypeIsConfigured(): void
    {
        $serviceTypes = ['LOCAL_DELIVERY_AM', 'LOCAL_DELIVERY_PM'];
        $this->instoreConfig->method('serviceTypeForRAQ')
            ->willReturn($serviceTypes);

        $this->assertTrue($this->shippingDelivery->validateIfLocalDelivery('LOCAL_DELIVERY_AM'));
    }

    /**
     * Tests that the validateIfLocalDelivery method returns false
     * when the service type is not configured.
     *
     * @return void
     */
    public function testValidateIfLocalDeliveryReturnsFalseWhenServiceTypeIsNotConfigured(): void
    {
        $this->instoreConfig->method('serviceTypeForRAQ')
            ->willReturn(['LOCAL_DELIVERY_AM', 'LOCAL_DELIVERY_PM']);

        $this->assertFalse($this->shippingDelivery->validateIfLocalDelivery('EXPRESS_SAVER'));
    }

    /**
     * Tests that the setLocalDelivery method returns the expected structure
     * when provided with an array address.
     *
     * @return void
     */
    public function testSetLocalDeliveryReturnsExpectedStructureForArrayAddress(): void
    {
        $shippingAddress = [
            'shipping_account_type' => 'accountType',
            'shipping_method' => 'methodX',
            'shipping_account_number' => '123456',
            'shipping_estimated_delivery_local_time' => '2025-05-19 12:00:00',
            'cart_id' => 5,
            'shipping_location_street1' => 'Street 1',
            'shipping_location_city' => 'City',
            'shipping_location_state' => 'ST',
            'shipping_location_zipcode' => '12345',
            'shipping_address_classification' => 'Residential'
        ];

        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(false);

        $result = $this->shippingDelivery->setLocalDelivery($shippingAddress);

        $expectedAddress = [
            'streetLines' => ['Street 1'],
            'city' => 'City',
            'stateOrProvinceCode' => 'ST',
            'postalCode' => '12345',
            'countryCode' => 'US',
            'addressClassification' => 'Residential',
        ];

        $this->assertEquals($expectedAddress, $result['address']);
        $this->assertEquals('accountType', $result['shipmentAccountType']);
        $this->assertEquals('methodX', $result['serviceType']);
        $this->assertEquals('123456', $result['fedExAccountNumber']);
        $this->assertEquals('2025-05-19 12:00:00', $result['holdUntilDate']);
    }

    /**
     * Tests that getShipmentDeliveryAddress returns an array when provided with an array input.
     *
     * @return void
     */
    public function testGetShipmentDeliveryAddressReturnsArrayForArrayInput(): void
    {
        $address = [
            'shipping_location_street1' => 'Main St',
            'shipping_location_street2' => 'Suite 100',
            'shipping_location_street3' => 'Building A',
            'shipping_location_city' => 'Metropolis',
            'shipping_location_state' => 'MP',
            'shipping_location_zipcode' => '54321',
            'shipping_address_classification' => 'Business',
        ];

        $result = $this->shippingDelivery->getShipmentDeliveryAddress($address);

        $this->assertSame([
            'streetLines' => ['Main St', 'Suite 100', 'Building A'],
            'city' => 'Metropolis',
            'stateOrProvinceCode' => 'MP',
            'postalCode' => '54321',
            'countryCode' => 'US',
            'addressClassification' => 'Business',
        ], $result);
    }

    /**
     * Tests that the setExternalDelivery method returns the expected structure
     * when provided with an address in array format.
     *
     * @return void
     */
    public function testSetExternalDeliveryReturnsExpectedStructureForArrayAddress(): void
    {
        $shippingAddress = [
            'shipping_account_type' => 'accountType',
            'shipping_method' => 'methodY',
            'shipping_account_number' => '654321',
            'shipping_estimated_delivery_local_time' => '2025-06-01 15:00:00',
            'cart_id' => 10,
            'shipping_location_street1' => 'Avenue 1',
            'shipping_location_city' => 'Townsville',
            'shipping_location_state' => 'TS',
            'shipping_location_zipcode' => '67890',
            'shipping_address_classification' => 'Business'
        ];

        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(false);

        $result = $this->shippingDelivery->setExternalDelivery($shippingAddress);

        $expectedAddress = [
            'streetLines' => ['Avenue 1'],
            'city' => 'Townsville',
            'stateOrProvinceCode' => 'TS',
            'postalCode' => '67890',
            'countryCode' => 'US',
            'addressClassification' => 'Business',
        ];

        $this->assertEquals($expectedAddress, $result['address']);
        $this->assertEquals($expectedAddress, $result['originAddress']);
        $this->assertNull($result['holdUntilDate']);
        $this->assertEquals('accountType', $result['shipmentAccountType']);
        $this->assertTrue($result['incenterDeliveryOrder']);
        $this->assertEquals('methodY', $result['serviceType']);
        $this->assertNull($result['productionLocationId']);
        $this->assertEquals('654321', $result['fedExAccountNumber']);
        $this->assertNull($result['deliveryInstructions']);
        $this->assertEquals([
            'minimumEstimatedShipDate' => '2025-06-01 15:00:00',
            'maximumEstimatedShipDate' => '2025-06-01 15:00:00'
        ], $result['estimatedShipDates']);
    }

    /**
     * Tests the setExternalDelivery method when provided with an address object.
     *
     * @return void
     */
    public function testSetExternalDeliveryWithObjectAddress(): void
    {
        $quoteMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $quoteMock->method('getId')->willReturn(20);

        $shippingAddress = $this->getMockBuilder(stdClass::class)
            ->addMethods([
                'getQuote',
                'getShipMethod',
                'getFedexShipAccountNumber',
                'getStreetAddress',
                'getCity',
                'getZipcode',
                'getAddressClassification'
            ])
            ->getMock();
        $shippingAddress->method('getQuote')->willReturn($quoteMock);
        $shippingAddress->method('getShipMethod')->willReturn('methodZ');
        $shippingAddress->method('getFedexShipAccountNumber')->willReturn('999999');
        $shippingAddress->method('getStreetAddress')->willReturn(['Obj St']);
        $shippingAddress->method('getCity')->willReturn('ObjCity');
        $shippingAddress->method('getZipcode')->willReturn('11111');
        $shippingAddress->method('getAddressClassification')->willReturn('Residential');

        $integration = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getDeliveryData'])
            ->getMock();
        $integration->method('getDeliveryData')->willReturn(json_encode([
            'shipping_account_type' => 'objectType',
            'shipping_estimated_delivery_local_time' => '2025-07-01 10:00:00'
        ]));

        $this->cartIntegrationRepository->method('getByQuoteId')->willReturn($integration);
        $this->jsonSerializer->method('unserialize')->willReturn([
            'shipping_account_type' => 'objectType',
            'shipping_estimated_delivery_local_time' => '2025-07-01 10:00:00'
        ]);
        $this->dateTime->method('formatDate')->willReturn('2025-07-01 10:00:00');

        $result = $this->shippingDelivery->setExternalDelivery($shippingAddress);

        $expectedAddress = [
            'streetLines' => ['Obj St'],
            'city' => 'ObjCity',
            'stateOrProvinceCode' => '',
            'postalCode' => '11111',
            'countryCode' => 'US',
            'addressClassification' => 'Residential',
        ];

        $this->assertEquals($expectedAddress, $result['address']);
        $this->assertEquals($expectedAddress, $result['originAddress']);
        $this->assertNull($result['holdUntilDate']);
        $this->assertEquals('objectType', $result['shipmentAccountType']);
        $this->assertTrue($result['incenterDeliveryOrder']);
        $this->assertEquals('methodZ', $result['serviceType']);
        $this->assertNull($result['productionLocationId']);
        $this->assertEquals('999999', $result['fedExAccountNumber']);
        $this->assertNull($result['deliveryInstructions']);
        $this->assertEquals([
            'minimumEstimatedShipDate' => '2025-07-01 10:00:00',
            'maximumEstimatedShipDate' => '2025-07-01 10:00:00'
        ], $result['estimatedShipDates']);
    }

    /**
     * Tests the setExternalDeliveryFormatsEstimatedDate method when delivery dates fields are enabled.
     *
     * @return void
     */
    public function testSetExternalDeliveryFormatsEstimatedDateWhenDeliveryDatesFieldsEnabled(): void
    {
        $shippingAddress = [
            'shipping_account_type' => 'accountType',
            'shipping_method' => 'methodY',
            'shipping_account_number' => '654321',
            'shipping_estimated_delivery_local_time' => '2025-08-01 09:30:00',
            'cart_id' => 11,
            'shipping_location_street1' => 'Test St',
            'shipping_location_city' => 'Testville',
            'shipping_location_state' => 'TS',
            'shipping_location_zipcode' => '99999',
            'shipping_address_classification' => 'Business'
        ];

        $this->instoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $this->dateTime->method('formatDate')
            ->with('2025-08-01 09:30:00', false)
            ->willReturn('2025-08-01');

        $result = $this->shippingDelivery->setExternalDelivery($shippingAddress);

        $this->assertEquals([
            'minimumEstimatedShipDate' => '2025-08-01',
            'maximumEstimatedShipDate' => '2025-08-01'
        ], $result['estimatedShipDates']);
    }

    /**
     * Tests handling of NoSuchEntityException when getting cart integration in setExternalDelivery.
     *
     * @return void
     */
    public function testSetExternalDeliveryHandlesNoSuchEntityException(): void
    {
        // Create mock quote with ID
        $quoteId = 123;
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        // Create mock shipping address object
        $shippingAddress = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'getQuote',
                'getShipMethod',
                'getFedexShipAccountNumber',
                'getStreetAddress',
                'getCity',
                'getZipcode',
                'getAddressClassification'
            ])
            ->getMock();

        // Configure shipping address mock
        $shippingAddress->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $shippingAddress->expects($this->once())
            ->method('getShipMethod')
            ->willReturn('PRIORITY_OVERNIGHT');
        $shippingAddress->expects($this->once())
            ->method('getFedexShipAccountNumber')
            ->willReturn('123456789');
        $shippingAddress->expects($this->once())
            ->method('getStreetAddress')
            ->willReturn(['123 Main St']);
        $shippingAddress->expects($this->once())
            ->method('getCity')
            ->willReturn('Nashville');
        $shippingAddress->expects($this->once())
            ->method('getZipcode')
            ->willReturn('37203');
        $shippingAddress->expects($this->once())
            ->method('getAddressClassification')
            ->willReturn('Business');

        // Set up repository to throw NoSuchEntityException
        $exception = new NoSuchEntityException(
            __('No such entity found with quote_id = %1', $quoteId)
        );
        $this->cartIntegrationRepository->expects($this->once())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willThrowException($exception);

        // Logger should log the error
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in Fetching Quote Integration:'));

        // JSON serializer should handle empty object
        $this->jsonSerializer->expects($this->once())
            ->method('unserialize')
            ->with('{}')
            ->willReturn([]);

        $this->dateTime->method('formatDate')
            ->with(null, false)
            ->willReturn(null);

        // Execute the method
        $result = $this->shippingDelivery->setExternalDelivery($shippingAddress);

        // Verify the result contains expected data despite exception
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('shipmentAccountType', $result);
        $this->assertArrayHasKey('serviceType', $result);
        $this->assertArrayHasKey('fedExAccountNumber', $result);
        $this->assertArrayHasKey('estimatedShipDates', $result);

        // Verify expected values
        $this->assertEquals('PRIORITY_OVERNIGHT', $result['serviceType']);
        $this->assertEquals('123456789', $result['fedExAccountNumber']);
        $this->assertNull($result['shipmentAccountType']); // Should be null since integration data wasn't found
        $this->assertEquals([
            'minimumEstimatedShipDate' => null,
            'maximumEstimatedShipDate' => null
        ], $result['estimatedShipDates']);

        // Verify address data is correctly built
        $this->assertEquals([
            'streetLines' => ['123 Main St'],
            'city' => 'Nashville',
            'stateOrProvinceCode' => '',
            'postalCode' => '37203',
            'countryCode' => 'US',
            'addressClassification' => 'Business',
        ], $result['address']);
    }
}
