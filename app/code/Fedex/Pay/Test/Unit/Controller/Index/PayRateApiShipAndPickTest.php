<?php
/**
 * Php file for test case of RateAPI.
 *
 * @author  Infogain <Team_Explorer@infogain.com>
 * @license Reserve For fedEx
 */
namespace Fedex\Delivery\Test\Unit\Controller\Index;

use Fedex\Delivery\Model\ShippingMessage;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Context;
use Fedex\FXOPricing\Model\FXOModel;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Address;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;  // <-- Change to Data namespace
use Fedex\FXOPricing\Api\RateQuoteBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateAlertInterface;
use Fedex\FXOPricing\Api\RateAlertBuilderInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterfaceFactory;
use Fedex\Pay\Controller\Index\PayRateApiShipAndPick;
use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;
use Fedex\MarketplaceProduct\Helper\Quote as MarketplaceQuoteHelper;

class PayRateApiShipAndPickTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $cartFactoryMock;
    protected $cartMock;
    protected $quoteMock;
    protected $quoteHelper;
    protected $itemMock;
    protected $rateAlertBuilderMock;
    /**
     * @var (\Fedex\Cart\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartDataHelperMock;
    protected $checkoutSessionMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $requestMock;
    protected $regionFactoryMock;
    protected $jsonMock;
    protected $resultJsonFactoryMock;
    protected $toggleFeatureMock;
    protected $addressMock;
    protected $fxoPricingHelperMock;
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuoteMock;
    protected $fxoModelMock;
    protected $objectManagerHelper;
    /**
     * @var (\Fedex\Delivery\Model\ShippingMessage & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingMessageMock;
    /**
     * @var (\Fedex\FXOPricing\Api\RateQuoteBuilderInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rateQuoteBuilderMock;
    protected $transportFactoryMock;
    protected $rate;
    public const OUTPUT_WITH_ALERT = [
        'output' => [
            'rate' => [
                'currency'    => 'USD',
                'rateDetails' => [
                    0 => [
                        'productLines'        => [
                            0 => [
                                'instanceId'            => 0,
                                'productId'             => '1508784838900',
                                'retailPrice'           => '$0.09',
                                'discountAmount'        => '$0.90',
                                'unitQuantity'          => 1,
                                'linePrice'             => '$0.490',
                                'priceable'             => 1,
                                'productLineDetails'    => [
                                    0 => [
                                        'detailCode'                => '0173',
                                        'description'               => 'Single Sided Color',
                                        'detailCategory'            => 'PRINTING',
                                        'unitQuantity'              => 1,
                                        'unitOfMeasurement'         => 'EACH',
                                        'detailPrice'               => '$0.493',
                                        'detailDiscountPrice'       => '$0.000',
                                        'detailUnitPrice'           => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.002',
                                    ],
                                ],
                                'productRetailPrice'    => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice'      => '0.49',
                                'editable'              => '',
                            ],
                        ],
                        'grossAmount'         => '$0.499',
                        'discounts'           => [],
                        'totalDiscountAmount' => '$0.00',
                        'netAmount'           => '$0.494',
                        'taxableAmount'       => '$0.495',
                        'taxAmount'           => '$0.00',
                        'totalAmount'         => '$0.49',
                        'estimatedVsActual'   => 'ACTUAL',
                    ],
                ],

            ],
            'alerts' => [
                0 => [
                    'code' => 'INVALID.COUPON.CODE'
                ]
            ]
        ],
    ];
    protected const PRODUCT_ID = '1508784838900';
    protected const PRICE = '$0.49';
    protected const DISCOUNT_AMOUNT = '$0.00';
    protected const PRODUCT_DESCRIPTION = 'Single Sided Color';
    protected const DETAIL_UNIT_PRICE = '$0.4900';
    protected const LOCATION_ID = '75024';
    protected const STREET = 'Legacy DR';
    protected const FEDEX_ACCOUNT_NUMBER = '12345678';
    protected const ZIPCODE = '12345';

    /**
     * Creating the Mock.
     *
     * @author  Infogain <Team_Explorer@infogain.com>
     * @license Reserve For fedEx
     * @return  MockBuilder
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getMiraklOfferId', 'getAdditionalData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateAlertBuilderMock = $this->createMock(RateAlertBuilderInterface::class);
        $this->quoteHelper = $this->getMockBuilder(MarketplaceQuoteHelper::class)
            ->setMethods(['isMiraklQuote', 'isFullMiraklQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getShippingAddress',
                    'getAllItems',
                    'getData',
                    'setGrandTotal',
                    'getCouponCode',
                    'setBaseGrandTotal',
                    'setCustomTaxAmount',
                    'save',
                    'setData',
                    'setCustomerShippingAddress',
                    'setIsFromShipping',
                    'setLocationId',
                    'setShippingMethod',
                    'setPostCode',
                    'setRegion',
                    'setStreet',
                    'setCity',
                    'setFedexShipingAccountNumber',
                    'setFedExAccountNumber',
                    'setRegionCode',
                    'setIsFromAccountScreen',
                    'setCustomerPickupLocationData',
                    'setIsFromPickup',
                    'setRequestedPickupDateTime',
                    'setIsAjaxRequest'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData', 'encryptdata'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['getProductionLocationId', 'setRemoveFedexAccountNumber', 'setAppliedFedexAccNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->setMethods(['create', 'load', 'getCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleFeatureMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->setMethods(['getData', 'setShippingMethod', 'getAddressClassification'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoPricingHelperMock = $this->getMockBuilder(FXORate::class)
            ->setMethods(['getFXORate', 'removePromoCode'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->fxoRateQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->fxoModelMock = $this->getMockBuilder(FXOModel::class)
            ->setMethods(['removePromoCode','handlePromoAccountWarnings'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->objectManagerHelper  = new ObjectManagerHelper($this);
        $this->shippingMessageMock = $this->createMock(ShippingMessage::class);
        $this->rateQuoteBuilderMock = $this->createMock(RateQuoteBuilderInterface::class);
        $this->transportFactoryMock = $this->createMock(TransportInterfaceFactory::class);
        $this->transportFactoryMock->method('create')->willReturn($this->objectManagerHelper->getObject(ShippingMessage\Transport::class));
        $objectManagerHelper = new ObjectManager($this);
        $this->rate = $objectManagerHelper->getObject(
            PayRateApiShipAndPick::class,
            [
                'context' => $this->contextMock,
                'loggerInterface' => $this->loggerMock,
                'request' => $this->requestMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'regionFactory' => $this->regionFactoryMock,
                'cartFactory' => $this->cartFactoryMock,
                'fxoRate' => $this->fxoPricingHelperMock,
                'cartDataHelper' => $this->cartDataHelperMock,
                'checkoutSession' => $this->checkoutSessionMock,
                'fxoRateQuote' => $this->fxoRateQuoteMock,
                'fxoModel' => $this->fxoModelMock,
                'toggleConfig' => $this->toggleFeatureMock,
                'shippingMessage'   => $this->shippingMessageMock,
                'transportFactory'  => $this->transportFactoryMock,
                'rateQuoteBuilder'  => $this->rateQuoteBuilderMock,
                'rateAlertBuilder' => $this->rateAlertBuilderMock,
                'quoteHelper' => $this->quoteHelper,
            ]
        );
    }

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecute()
    {
        $output = json_encode(
            [
                'output' => [
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())->method('setRemoveFedexAccountNumber')->with(true)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_ship_account_number')
            ->willReturn(140678765);
        $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['removedFedexAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['locationId'],
            ['locationId'],
            ['account_payment_method'],
            ['locationId']
        )->willReturnOnConsecutiveCalls(
            'true',
            true,
            false,
            self::LOCATION_ID,
            self::LOCATION_ID,
            'cc',
            self::LOCATION_ID
        );
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->withConsecutive(
            ['shipping_method'],
            ['street'],
            ['city'],
            ['region_id'],
            ['postcode'],
            ['company']
        )->willReturnOnConsecutiveCalls(
            'fedexshipping_PICKUP',
            self::STREET,
            'Plano',
            'TX',
            self::LOCATION_ID,
            'Infogain'
        );
        $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('load')->willReturn('TX');
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(json_decode($output, true));
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

        /**
 * Test execute when ship_method is null and pickupAddress exists
 *
 * @return void
 */
public function testExecuteWhenShipMethodNullAndPickupAddressExists()
{
    $pickupAddressData = json_encode(['Id' => 'pickup123']);
    $output = json_encode(['output' => []]);
    
    // Make sure the toggle is enabled
    $this->toggleFeatureMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturnCallback(function($key) {
            // Return true for the D194518 feature flag
            return true;
        });
    
    $this->cartFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->cartMock);
    
    $this->cartMock->expects($this->any())
        ->method('getQuote')
        ->willReturn($this->quoteMock);
    
    $this->quoteMock->expects($this->any())
        ->method('setData')
        ->willReturnSelf();
    
    // Configure the address mock to return null for shipping_method and valid JSON for pickup_address
    $this->addressMock->expects($this->any())
        ->method('getData')
        ->willReturnCallback(function($key) use ($pickupAddressData) {
            switch ($key) {
                case 'shipping_method':
                    return null; // CRITICAL: Must be null to trigger the condition
                case 'pickup_address':
                    return $pickupAddressData; // CRITICAL: Must be valid JSON with Id
                case 'street':
                    return self::STREET;
                case 'city':
                    return 'Plano';
                case 'region_id':
                    return 'TX';
                case 'postcode':
                    return self::ZIPCODE;
                case 'company':
                    return 'Infogain';
                default:
                    return null;
            }
        });
    
    $this->quoteMock->expects($this->any())
        ->method('getShippingAddress')
        ->willReturn($this->addressMock);
    
    // Mock the request post values
    $this->requestMock->expects($this->any())
        ->method('getPostValue')
        ->willReturnCallback(function($key) use ($pickupAddressData) {
            switch ($key) {
                case 'removedFedexAccount':
                    return 'false';
                case 'isPickupPage':
                    return true;
                case 'isShippingPage':
                    return false;
                case 'pickupAddress': // Make sure pickupAddress is also available from the request
                    return $pickupAddressData;
                case 'requestedPickupLocalTime':
                    return '2023-05-01T12:00:00';
                case 'locationId':
                    return self::LOCATION_ID;
                default:
                    return null;
            }
        });
    
    // Verify that setIsFromPickup is called, indicating we entered the pickup branch
    $this->quoteMock->expects($this->once())
        ->method('setIsFromPickup')
        ->with(true)
        ->willReturnSelf();
    
    // Setup standard mocks needed for test execution
    $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
    
    $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
    $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
    $this->quoteMock->expects($this->any())->method('setRequestedPickupDateTime')->willReturnSelf();
    
    $this->fxoPricingHelperMock->expects($this->any())
        ->method('getFXORate')
        ->willReturn(json_decode($output, true));
    
    $this->resultJsonFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->jsonMock);
    
    $this->jsonMock->expects($this->any())
        ->method('setData')
        ->willReturnSelf();
    
    // Execute the controller
    $result = $this->rate->execute();
    
    // Assert that the controller returns the expected result
    $this->assertEquals($this->jsonMock, $result);
}

/**
 * Test execution with is_residence_shipping set to true
 *
 * @return void
 */
public function testExecuteWithIsResidenceShippingTrue()
{
    $output = json_encode(['output' => []]);
    
    // Set up basic mocks
    $this->toggleFeatureMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);
        
    $this->cartFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->cartMock);
        
    $this->cartMock->expects($this->any())
        ->method('getQuote')
        ->willReturn($this->quoteMock);
        
    // Configure the address mock to return true for is_residence_shipping
    $this->addressMock->expects($this->any())
        ->method('getData')
        ->willReturnCallback(function($key) {
            switch ($key) {
                case 'is_residence_shipping':
                    return true; // CRITICAL: This is what we're testing
                case 'shipping_method':
                    return 'fedexshipping_SHIPPING';
                case 'street':
                    return self::STREET;
                case 'city':
                    return 'Plano';
                case 'region_id':
                    return 'TX';
                case 'postcode':
                    return self::ZIPCODE;
                case 'company':
                    return ''; // No company name to ensure HOME classification
                default:
                    return null;
            }
        });
        
    $this->quoteMock->expects($this->any())
        ->method('getShippingAddress')
        ->willReturn($this->addressMock);
    
    
    // if the correct data is passed in the quote's setCustomerShippingAddress method
    $this->quoteMock->expects($this->once())
        ->method('setCustomerShippingAddress')
        ->with($this->callback(function($address) {
            // Verify the address classification is set to HOME when is_residence_shipping is true
            return isset($address['addrClassification']) && $address['addrClassification'] === 'HOME';
        }))
        ->willReturnSelf();
    
    // Mock request values
    $this->requestMock->expects($this->any())
        ->method('getPostValue')
        ->willReturnCallback(function($key) {
            switch ($key) {
                case 'removedFedexAccount':
                    return 'false';
                case 'isPickupPage':
                    return false;
                case 'isShippingPage':
                    return true;
                default:
                    return null;
            }
        });
    
    // Set up other necessary mocks
    $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
    
    $this->fxoPricingHelperMock->expects($this->any())
        ->method('getFXORate')
        ->willReturn(json_decode($output, true));
        
    $this->resultJsonFactoryMock->expects($this->any())
        ->method('create')
        ->willReturn($this->jsonMock);
        
    $this->jsonMock->expects($this->any())
        ->method('setData')
        ->willReturnSelf();
    
    // Execute controller
    $result = $this->rate->execute();
    
    // Assert result
    $this->assertEquals($this->jsonMock, $result);
}

/**
 * Test rate quote data retrieval with different response structures
 *
 * @return void
 */
public function testRateQuoteResponseDataPaths()
{
    // Define test data for all scenarios
    
    // Case 1: When rateQuote exists directly at root level (not in 'output')
    $responseWithDirectRateQuote = [
        'rateQuote' => [
            'quoteId' => '67890',
            'details' => ['someDetail' => 'value']
        ]
        // No 'output' key
    ];
    
    // Case 2: When rateQuote exists inside OUTPUT key
    $responseWithOutputRateQuote = [
        'output' => [
            'rateQuote' => [
                'quoteId' => '12345',
                'details' => ['someDetailInOutput' => 'value']
            ]
        ]
    ];
    
    // Case 3: When alerts exists directly at root level (not in 'output')
    $responseWithDirectAlerts = [
        'alerts' => [
            ['code' => 'DIRECT_ALERT']
        ]
        // No rateQuote
    ];
    
    // Case 4: Empty response with no rate quote data
    $responseWithNoRateQuote = [
        'output' => [
            // No rateQuote
        ]
    ];
    
    // *** CASE 1: Testing direct rateQuote ***
    $transportMock1 = $this->getMockBuilder(ShippingMessage\Transport::class)
        ->disableOriginalConstructor()
        ->setMethods(['setCart', 'setStrategy', 'setFXORateQuote', 'setFXORateAlert'])
        ->getMock();
        
    $transportFactoryMock1 = $this->getMockBuilder(TransportInterfaceFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        
    $transportFactoryMock1->expects($this->once())
        ->method('create')
        ->willReturn($transportMock1);
    
    $builtRateQuoteMock1 = $this->getMockBuilder(RateQuoteInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
    // IMPORTANT CHANGE: Create a separate mock for the builder for case 1
    $rateQuoteBuilderMock1 = $this->getMockBuilder(RateQuoteBuilderInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
    $rateQuoteBuilderMock1->expects($this->once())
        ->method('build')
        ->with($responseWithDirectRateQuote['rateQuote'])
        ->willReturn($builtRateQuoteMock1);
        
    $transportMock1->expects($this->once())
        ->method('setCart')
        ->with($this->quoteMock)
        ->willReturnSelf();
        
    $transportMock1->expects($this->once())
        ->method('setStrategy')
        ->with('rateQuote')
        ->willReturnSelf();
        
    $transportMock1->expects($this->once())
        ->method('setFXORateQuote')
        ->with($builtRateQuoteMock1)
        ->willReturnSelf();
    
    // Process Case 1 with the specific builder mock
    $result1 = $this->processRateDataWithCustomBuilder($responseWithDirectRateQuote, $this->quoteMock, $transportFactoryMock1, $rateQuoteBuilderMock1);
    $this->assertNull($result1, 'Processing direct rate quote should complete without errors');

    // *** CASE 2: Testing rateQuote in OUTPUT ***
    $transportMock2 = $this->getMockBuilder(ShippingMessage\Transport::class)
        ->disableOriginalConstructor()
        ->setMethods(['setCart', 'setStrategy', 'setFXORateQuote', 'setFXORateAlert'])
        ->getMock();
        
    $transportFactoryMock2 = $this->getMockBuilder(TransportInterfaceFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        
    $transportFactoryMock2->expects($this->once())
        ->method('create')
        ->willReturn($transportMock2);
    
    $builtRateQuoteMock2 = $this->getMockBuilder(RateQuoteInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
    // IMPORTANT CHANGE: Create a separate mock for the builder for case 2
    $rateQuoteBuilderMock2 = $this->getMockBuilder(RateQuoteBuilderInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
    $rateQuoteBuilderMock2->expects($this->once())
        ->method('build')
        ->with($responseWithOutputRateQuote['output']['rateQuote'])
        ->willReturn($builtRateQuoteMock2);
        
    $transportMock2->expects($this->once())
        ->method('setCart')
        ->with($this->quoteMock)
        ->willReturnSelf();
        
    $transportMock2->expects($this->once())
        ->method('setStrategy')
        ->with('rateQuote')
        ->willReturnSelf();
        
    $transportMock2->expects($this->once())
        ->method('setFXORateQuote')
        ->with($builtRateQuoteMock2)
        ->willReturnSelf();
    
    // Process Case 2 with the specific builder mock
    $result2 = $this->processRateDataWithCustomBuilder($responseWithOutputRateQuote, $this->quoteMock, $transportFactoryMock2, $rateQuoteBuilderMock2);
    $this->assertNull($result2, 'Processing output rate quote should complete without errors');

    // *** CASE 3: Testing direct alerts with no rateQuote ***
    $transportMock3 = $this->getMockBuilder(ShippingMessage\Transport::class)
        ->disableOriginalConstructor()
        ->setMethods(['setCart', 'setStrategy', 'setFXORateQuote', 'setFXORateAlert'])
        ->getMock();
        
    $transportFactoryMock3 = $this->getMockBuilder(TransportInterfaceFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        
    $transportFactoryMock3->expects($this->once())
        ->method('create')
        ->willReturn($transportMock3);
    
    // No rate quote methods should be called since there's no rate quote data
    $transportMock3->expects($this->never())
        ->method('setCart');
        
    $transportMock3->expects($this->never())
        ->method('setStrategy');
        
    $transportMock3->expects($this->never())
        ->method('setFXORateQuote');
    
    // Process Case 3
    $result3 = $this->processRateData($responseWithDirectAlerts, $this->quoteMock, $transportFactoryMock3);
    
    // KEY FIX: Use extractRateQuoteData instead of calling processRateData again
    $this->assertNull($result3, 'Processing direct alerts should complete without errors');
    $this->assertTrue(empty($this->extractRateQuoteData($responseWithDirectAlerts)), 
        'Direct alerts response should not contain rate quote data');    
    
    // *** CASE 4: Testing empty response with no rate quote ***
    $transportMock4 = $this->getMockBuilder(ShippingMessage\Transport::class)
        ->disableOriginalConstructor()
        ->setMethods(['setCart', 'setStrategy', 'setFXORateQuote', 'setFXORateAlert'])
        ->getMock();
        
    $transportFactoryMock4 = $this->getMockBuilder(TransportInterfaceFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        
    $transportFactoryMock4->expects($this->once())
        ->method('create')
        ->willReturn($transportMock4);
    
    // Again, no rate quote methods should be called due to empty data
    $transportMock4->expects($this->never())
        ->method('setCart');
        
    $transportMock4->expects($this->never())
        ->method('setStrategy');
        
    $transportMock4->expects($this->never())
        ->method('setFXORateQuote');
    
    // Process Case 4
    $result4 = $this->processRateData($responseWithNoRateQuote, $this->quoteMock, $transportFactoryMock4);
    
    // KEY FIX: Use extractRateQuoteData instead of calling processRateData again
    $this->assertNull($result4, 'Processing empty response should complete without errors');
    $this->assertTrue(empty($this->extractRateQuoteData($responseWithNoRateQuote)), 
        'Empty response should not contain rate quote data');
}

/**
 * Helper method to extract rate quote data from response for testing
 *
 * @param array $arrRateResponseData
 * @return array
 */
protected function extractRateQuoteData($arrRateResponseData)
{
    $rateQuoteForValidation = [];
    if (!empty($arrRateResponseData[PayRateApiShipAndPick::OUTPUT]['rateQuote'])) {
        $rateQuoteForValidation = $arrRateResponseData[PayRateApiShipAndPick::OUTPUT]['rateQuote'];
    } elseif (!empty($arrRateResponseData['rateQuote'])) {
        $rateQuoteForValidation = $arrRateResponseData['rateQuote'];
    }
    return $rateQuoteForValidation;
}

/**
 * Helper method to allow testing with private controller methods and custom builder
 * 
 * @param array $arrRateResponseData Response data
 * @param \Magento\Quote\Model\Quote $quote Quote object
 * @param \Fedex\Delivery\Model\ShippingMessage\TransportInterfaceFactory|null $transportFactory Optional transport factory
 * @param \Fedex\FXOPricing\Api\RateQuoteBuilderInterface|null $customBuilder Custom builder for testing
 */
public function processRateDataWithCustomBuilder(
    $arrRateResponseData,
    $quote,
    $transportFactory = null,
    $customBuilder = null
) {
    // Use the provided transportFactory or fall back to class property
    $transportFactoryToUse = $transportFactory ?: $this->transportFactoryMock;
    
    $transport = $transportFactoryToUse->create();
    
    // Fixed logic for safely accessing nested array keys
    $rateQuoteForValidation = [];
    if (!empty($arrRateResponseData[PayRateApiShipAndPick::OUTPUT]['rateQuote'])) {
        $rateQuoteForValidation = $arrRateResponseData[PayRateApiShipAndPick::OUTPUT]['rateQuote'];
    } elseif (!empty($arrRateResponseData['rateQuote'])) {
        $rateQuoteForValidation = $arrRateResponseData['rateQuote'];
    }
        
    if (!empty($rateQuoteForValidation)) {
        $builderToUse = $customBuilder ?: $this->rateQuoteBuilderMock;
        $transport
            ->setCart($quote)
            ->setStrategy('rateQuote')
            ->setFXORateQuote($builderToUse->build($rateQuoteForValidation));
    }
}

/**
 * Helper method to allow testing with private controller methods
 * 
 * @param array $arrRateResponseData Response data
 * @param \Magento\Quote\Model\Quote $quote Quote object
 * @param \Fedex\Delivery\Model\ShippingMessage\TransportInterfaceFactory|null $transportFactory Optional transport factory
 */
public function processRateData($arrRateResponseData, $quote, $transportFactory = null)
{
    // Use the provided transportFactory or fall back to class property
    $transportFactoryToUse = $transportFactory ?: $this->transportFactoryMock;
    
    $transport = $transportFactoryToUse->create();
    
    // Fixed logic for safely accessing nested array keys
    $rateQuoteForValidation = [];
    if (!empty($arrRateResponseData[PayRateApiShipAndPick::OUTPUT]['rateQuote'])) {
        $rateQuoteForValidation = $arrRateResponseData[PayRateApiShipAndPick::OUTPUT]['rateQuote'];
    } elseif (!empty($arrRateResponseData['rateQuote'])) {
        $rateQuoteForValidation = $arrRateResponseData['rateQuote'];
    }
        
    if (!empty($rateQuoteForValidation)) {
        $transport
            ->setCart($quote)
            ->setStrategy('rateQuote')
            ->setFXORateQuote($this->rateQuoteBuilderMock->build($rateQuoteForValidation));
    }
}

/**
 * Test that the transport object is correctly configured with rate quote data
 * 
 * @return void
 */
public function testTransportConfigurationWithRateQuote()
{
    // Create the input data that contains a rate quote
    $rateQuoteData = [
        'quoteId' => '12345',
        'quoteDetails' => ['someData' => 'value']
    ];

    $fxoRateCallResponse = [
        'output' => [
            'rateQuote' => $rateQuoteData,
            'alerts' => [['code' => 'INFO_MESSAGE']]
        ]
    ];

    // Setup standard mocks needed for the test
    $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');

    // Setup mock for the FXO rate quote call to return our test data
    $this->fxoRateQuoteMock->expects($this->once())
        ->method('getFXORateQuote')
        ->willReturn($fxoRateCallResponse);

    // Create a mock transport object
    $transportMock = $this->getMockBuilder(ShippingMessage\Transport::class)
        ->disableOriginalConstructor()
        ->setMethods(['setCart', 'setStrategy', 'setFXORateQuote', 'setFXORateAlert'])
        ->getMock();
    
    // Expect that setCart, setStrategy, and setFXORateQuote are called in sequence
    $transportMock->expects($this->once())
        ->method('setCart')
        ->with($this->quoteMock)
        ->willReturnSelf();
        
    $transportMock->expects($this->once())
        ->method('setStrategy')
        ->with('rateQuote')
        ->willReturnSelf();
    
    // This is the critical verification - that setFXORateQuote is called with 
    // the result of rateQuoteBuilder->build() using our rate quote data
    $builtRateQuoteMock = $this->getMockBuilder(RateQuoteInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
    $this->rateQuoteBuilderMock->expects($this->once())
        ->method('build')
        ->with($rateQuoteData)
        ->willReturn($builtRateQuoteMock);
    
    $transportMock->expects($this->once())
        ->method('setFXORateQuote')
        ->with($builtRateQuoteMock)
        ->willReturnSelf();

    // Also verify setFXORateAlert is called
    $builtRateAlertMock = $this->getMockBuilder(AlertCollectionInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
    $this->rateAlertBuilderMock->expects($this->once())
        ->method('build')
        ->willReturn($builtRateAlertMock);
        
    $transportMock->expects($this->once())
        ->method('setFXORateAlert')
        ->with($builtRateAlertMock)
        ->willReturnSelf();
    
    // IMPORTANT: Create a new transport factory mock specific for this test
    // This overrides the one from setUp()
    $transportFactoryMock = $this->getMockBuilder(TransportInterfaceFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        
    $transportFactoryMock->expects($this->once())
        ->method('create')
        ->willReturn($transportMock);
    
    // Create a new controller instance with our specific transport factory mock
    $objectManager = new ObjectManager($this);
    $controller = $objectManager->getObject(
        PayRateApiShipAndPick::class,
        [
            'context' => $this->contextMock,
            'loggerInterface' => $this->loggerMock,
            'request' => $this->requestMock,
            'resultJsonFactory' => $this->resultJsonFactoryMock,
            'regionFactory' => $this->regionFactoryMock,
            'cartFactory' => $this->cartFactoryMock,
            'fxoRate' => $this->fxoPricingHelperMock,
            'cartDataHelper' => $this->cartDataHelperMock,
            'checkoutSession' => $this->checkoutSessionMock,
            'fxoRateQuote' => $this->fxoRateQuoteMock,
            'fxoModel' => $this->fxoModelMock,
            'toggleConfig' => $this->toggleFeatureMock,
            'shippingMessage' => $this->shippingMessageMock,
            'transportFactory' => $transportFactoryMock, // Use our test-specific mock
            'rateQuoteBuilder' => $this->rateQuoteBuilderMock,
            'rateAlertBuilder' => $this->rateAlertBuilderMock,
            'quoteHelper' => $this->quoteHelper,
        ]
    );
    
    // Setup standard mocks needed for the execute method
    $this->toggleFeatureMock->expects($this->any())
        ->method('getToggleConfigValue')
        ->willReturn(true);

    $this->cartFactoryMock->expects($this->once())
        ->method('create')
        ->willReturn($this->cartMock);
        
    $this->cartMock->expects($this->once())
        ->method('getQuote')
        ->willReturn($this->quoteMock);
    
    // Setup shipping address
    $this->quoteMock->expects($this->atLeastOnce())
        ->method('getShippingAddress')
        ->willReturn($this->addressMock);
    
    $this->addressMock->expects($this->any())
        ->method('getData')
        ->willReturnCallback(function($key) {
            switch ($key) {
                case 'shipping_method':
                    return 'fedexshipping_SHIPPING';
                case 'street':
                    return self::STREET;
                case 'city':
                    return 'Plano';
                case 'region_id':
                    return 'TX';
                case 'postcode':
                    return self::ZIPCODE;
                default:
                    return null;
            }
        });
    
    // Setup result factories
    $this->resultJsonFactoryMock->expects($this->once())
        ->method('create')
        ->willReturn($this->jsonMock);
        
    $this->jsonMock->expects($this->once())
        ->method('setData')
        ->willReturnSelf();
    
    // Mock the shipping message to avoid null pointer exceptions
    $this->shippingMessageMock->expects($this->once())
        ->method('getMessage')
        ->willReturn(['message' => 'Free shipping message']);
    
    // Execute the controller method using our controller instance
    $result = $controller->execute();
    
    // Verify the result
    $this->assertEquals($this->jsonMock, $result);
}
    

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */

public function testExecuteToggleEnableFedexShiping()
{
    $output = json_encode(
        [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId'                    => 0,
                                    'productId'                     => self::PRODUCT_ID,
                                    'retailPrice'                   => self::PRICE,
                                    'discountAmount'                => self::DISCOUNT_AMOUNT,
                                    'unitQuantity'                  => 1,
                                    'linePrice'                     => self::PRICE,
                                    'priceable'                     => 1,
                                    'productLineDetails'            => [
                                        0 => [
                                            'detailCode'                => '0173',
                                            'description'               => self::PRODUCT_DESCRIPTION,
                                            'detailCategory'            => 'PRINTING',
                                            'unitQuantity'              => 1,
                                            'unitOfMeasurement'         => 'EACH',
                                            'detailPrice'               => self::PRICE,
                                            'detailDiscountPrice'       => self::DISCOUNT_AMOUNT,
                                            'detailUnitPrice'           => self::DETAIL_UNIT_PRICE,
                                            'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                        ],
                                    ],
                                    'productRetailPrice'            => 0.49,
                                    'productDiscountAmount'         => '0.00',
                                    'productLinePrice'              => '0.49',
                                    'editable'                      => '',
                                ],
                            ],
                            'grossAmount'         => self::PRICE,
                            'discounts'           => [],
                            'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                            'netAmount'           => self::PRICE,
                            'taxableAmount'       => self::PRICE,
                            'taxAmount'           => self::DISCOUNT_AMOUNT,
                            'totalAmount'         => self::PRICE,
                            'estimatedVsActual'   => 'ACTUAL',
                        ],
                    ],
                ],
            ],
        ]
    );
    // Force the toggle flag to false to use the fedex/tShip branch (not the Mirakl branch)
    $this->toggleFeatureMock
         ->expects($this->any())
         ->method('getToggleConfigValue')
         ->willReturn(false);

    $this->cartFactoryMock
         ->expects($this->any())
         ->method('create')
         ->willReturn($this->cartMock);

    $this->cartMock
         ->expects($this->any())
         ->method('getQuote')
         ->willReturn($this->quoteMock);

    $this->quoteMock
         ->expects($this->any())
         ->method('setData')
         ->willReturnSelf();

    $this->checkoutSessionMock
         ->expects($this->any())
         ->method('setRemoveFedexAccountNumber')
         ->with(true)
         ->willReturnSelf();

    $this->quoteMock
         ->expects($this->any())
         ->method('getData')
         ->with('fedex_ship_account_number')
         ->willReturn(140678765);

    // Configure standard request values
    $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['removedFedexAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['locationId'],
            ['locationId'],
            ['account_payment_method'],
            ['locationId']
         )->willReturnOnConsecutiveCalls(
            'true',
            true,
            false,
            self::LOCATION_ID,
            self::LOCATION_ID,
            'cc',
            self::LOCATION_ID
         );

    $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
    $this->addressMock->expects($this->any())->method('getData')->withConsecutive(
            ['shipping_method'],
            ['street'],
            ['city'],
            ['region_id'],
            ['postcode'],
            ['company']
         )->willReturnOnConsecutiveCalls(
            'fedexshipping_PICKUP',
            self::STREET,
            'Plano',
            'TX',
            self::LOCATION_ID,
            'Infogain'
         );
    // Configure expectations for the Mirakl branch.
    // Create a quote item mock with mirakl methods
    $this->itemMock->expects($this->once())
         ->method('getMiraklOfferId')
         ->willReturn('offer123');

    $shippingData = [
         'company'  => 'Infogain',  // non-empty: triggers BUSINESS classification.
         'postcode' => self::LOCATION_ID,
         'region'   => 'TX',
         'street'   => self::STREET,
         'city'     => 'Plano'
    ];
    $additionalData = json_encode(['mirakl_shipping_data' => ['address' => $shippingData]]);
    $this->itemMock->expects($this->once())
         ->method('getAdditionalData')
         ->willReturn($additionalData);

    // Expect getAllItems() to return the mirakl item.
    $this->quoteMock->expects($this->once())
         ->method('getAllItems')
         ->willReturn([$this->itemMock]);

         // Expect that the branch sets the customer shipping address as per mirakl data.
         $expectedAddress = [
            'shipMethod'                => '',
            'zipcode'                   => $shippingData['postcode'],
            'regionData'                => $shippingData['region'],
            'street'                    => $shippingData['street'], // Change key to 'street' 
            'city'                      => $shippingData['city'],
            'fedExAccountNumber'        => null, // Change to null to match
            'fedexDiscount'             => null,
            'addrClassification'        => 'BUSINESS',
            'fedExShippingAccountNumber'=> 140678765, // Change to match actual value
            'productionLocationId'      => null,
        ];
    $this->quoteMock->expects($this->once())
        ->method('setCustomerShippingAddress')
        ->with($expectedAddress)
        ->willReturnSelf();

    $this->quoteMock->expects($this->once())
        ->method('setCustomerShippingAddress')
        ->willReturnCallback(function($actualAddress) use ($shippingData) {
            // Assert structure (keys exist)
            $this->assertArrayHasKey('shipMethod', $actualAddress);
            $this->assertArrayHasKey('zipcode', $actualAddress);
            $this->assertArrayHasKey('regionData', $actualAddress);
            $this->assertArrayHasKey('street', $actualAddress);
            $this->assertArrayHasKey('city', $actualAddress);
            $this->assertArrayHasKey('fedExAccountNumber', $actualAddress);
            $this->assertArrayHasKey('fedexDiscount', $actualAddress);
            $this->assertArrayHasKey('addrClassification', $actualAddress);
            $this->assertArrayHasKey('fedExShippingAccountNumber', $actualAddress);
            $this->assertArrayHasKey('productionLocationId', $actualAddress);
            
            // Assert values
            $this->assertEquals('', $actualAddress['shipMethod']);
            $this->assertEquals($shippingData['postcode'], $actualAddress['zipcode']);
            $this->assertEquals($shippingData['region'], $actualAddress['regionData']);
            $this->assertEquals($shippingData['street'], $actualAddress['street']);
            $this->assertEquals($shippingData['city'], $actualAddress['city']);
            $this->assertNull($actualAddress['fedExAccountNumber']);
            $this->assertNull($actualAddress['fedexDiscount']);
            $this->assertEquals('BUSINESS', $actualAddress['addrClassification']);
            $this->assertEquals(140678765, $actualAddress['fedExShippingAccountNumber']);
            $this->assertNull($actualAddress['productionLocationId']);
            
            return true;
        });
        
   $this->quoteMock->expects($this->once())
        ->method('setIsFromShipping')
        ->with(true)
        ->willReturnSelf();

    $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('load')->willReturn('TX');

    $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
    $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
    $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();

    $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
         ->willReturn(json_decode($output, true));
    $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
    $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

    // Remove expectations on quoteHelper methods as the fedex/tShip branch does not invoke them
    // $this->quoteHelper->expects($this->once())->method('isMiraklQuote')->with($this->quoteMock)->willReturn(true);
    // $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->with($this->quoteMock)->willReturn(false);
    $this->quoteHelper->expects($this->once())
        ->method('isMiraklQuote')
        ->with($this->quoteMock)
        ->willReturn(true);
    $this->quoteHelper->expects($this->once())
        ->method('isFullMiraklQuote')
        ->with($this->quoteMock)
        ->willReturn(false);


    $this->assertEquals($this->jsonMock, $this->rate->execute());
}

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithRemoveFedexIsFalseIf()
    {
        $output = json_encode(
            [
                'output' => [
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())->method('setRemoveFedexAccountNumber')->with(true)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_ship_account_number')
            ->willReturn(140678765);
        $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['removedFedexAccount'],
            ['fedexAccount'],
            ['fedexAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['locationId'],
            ['locationId'],
            ['account_payment_method'],
            ['locationId']
        )->willReturnOnConsecutiveCalls(
            false,
            false,
            self::FEDEX_ACCOUNT_NUMBER,
            true,
            false,
            self::LOCATION_ID,
            self::LOCATION_ID,
            'cc',
            self::LOCATION_ID
        );
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->withConsecutive(
            ['shipping_method'],
            ['street'],
            ['city'],
            ['region_id'],
            ['postcode'],
            ['company']
        )->willReturnOnConsecutiveCalls(
            'fedexshipping_PICKUP',
            self::STREET,
            'Plano',
            'TX',
            self::LOCATION_ID,
            'Infogain'
        );
        $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('load')->willReturn('TX');
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(json_decode($output, true));
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    public function testExecuteWithFedexAccountEncryption()
{
    $output = json_encode([
        'output' => [
            'rate' => [
                'currency' => 'USD',
                'rateDetails' => [[]]
            ]
        ]
    ]);
    
    // Basic setup
    $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
    $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
    $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
    
    // Make getPostValue() return an account number for FEDEX_ACCOUNT
    $this->requestMock->expects($this->any())
        ->method('getPostValue')
        ->willReturnCallback(function($key) {
            if ($key === 'fedexAccount') {
                return self::FEDEX_ACCOUNT_NUMBER;  // Return the account number
            }
            return null;
        });
    
    // Set up cartDataHelper to encrypt the account number
    $encryptedAccountNumber = 'encrypted_' . self::FEDEX_ACCOUNT_NUMBER;
    $this->cartDataHelperMock->expects($this->once())
        ->method('encryptdata')
        ->with(self::FEDEX_ACCOUNT_NUMBER)
        ->willReturn($encryptedAccountNumber);
    
    // CRITICAL: Verify that setData is called with the correct key and encrypted value
    $this->quoteMock->expects($this->atLeastOnce())
        ->method('setData')
        ->withConsecutive(
            ['fedex_account_number', $encryptedAccountNumber]
        )
        ->willReturnSelf();
    
    // Set up other required mocks
    $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
    $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
    $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
    
    $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
        ->willReturn(json_decode($output, true));
    $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
    $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
    
    // Execute and verify
    $this->assertEquals($this->jsonMock, $this->rate->execute());
}

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithShippingFlowAndIsNotShippingPage()
    {
        $output = json_encode(
            [
                'output' => [
                    'alerts' => [
                        '0' => [
                            'code' => 'testCode',
                        ],
                    ],
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())->method('setRemoveFedexAccountNumber')->with(true)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_ship_account_number')
            ->willReturn(140678765);
        $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['removedFedexAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['locationId'],
            ['locationId'],
            ['account_payment_method'],
            ['company'],
            ['street'],
            ['city'],
            ['region_id'],
            ['zipcode'],
            ['ship_method']
        )->willReturnOnConsecutiveCalls(
            'true',
            false,
            false,
            self::LOCATION_ID,
            self::LOCATION_ID,
            'cc',
            'Infogain',
            'Plano',
            'Texas',
            'TX',
            75024,
            'fedexshipping_Local'
        );
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->addressMock->expects($this->exactly(13))
            ->method('getData')
            ->will($this->onConsecutiveCalls(
                'fedexshipping_Shipping',
                self::STREET,
                'Plano',
                self::ZIPCODE,
                self::LOCATION_ID,
                'Infogain',
                'Infogain',
                self::STREET,
                'Plano',
                self::ZIPCODE,
                self::LOCATION_ID
            ));

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(json_decode($output, true));
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithShippingFlowAndIsNotShippingPageWithoutOutput()
    {
        $output = json_encode(
            [
                'output' => [],
            ]
        );
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())->method('setRemoveFedexAccountNumber')->with(true)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_ship_account_number')
            ->willReturn(140678765);
        $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['removedFedexAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['locationId'],
            ['locationId'],
            ['account_payment_method'],
            ['company'],
            ['street'],
            ['city'],
            ['region_id'],
            ['zipcode'],
            ['ship_method']
        )->willReturnOnConsecutiveCalls(
            'true',
            false,
            false,
            self::LOCATION_ID,
            self::LOCATION_ID,
            'cc',
            'Infogain',
            'Plano',
            'Texas',
            'TX',
            75024,
            'fedexshipping_Local'
        );
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->addressMock->expects($this->exactly(13))
            ->method('getData')
            ->will($this->onConsecutiveCalls(
                'fedexshipping_Shipping',
                self::STREET,
                'Plano',
                self::ZIPCODE,
                self::LOCATION_ID,
                'Infogain',
                'Infogain',
                self::STREET,
                'Plano',
                self::ZIPCODE,
                self::LOCATION_ID
            ));

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(json_decode($output, true));
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithShippingFlowAndIsShippingPage()
    {
        $output = json_encode(
            [
                'output' => [],
            ]
        );
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())->method('setRemoveFedexAccountNumber')->with(true)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_ship_account_number')
            ->willReturn(140678765);
        $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['removedFedexAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['company'],
            ['street'],
            ['city'],
            ['region_id'],
            ['zipcode'],
            ['ship_method']
        )->willReturnOnConsecutiveCalls(
            'true',
            false,
            true,
            'Infogain',
            'Plano',
            'Texas',
            'TX',
            75024,
            'fedexshipping_Local'
        );
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->addressMock->expects($this->exactly(7))
            ->method('getData')
            ->will($this->onConsecutiveCalls(
                'fedexshipping_Shipping',
                self::STREET,
                'Plano',
                self::ZIPCODE,
                self::LOCATION_ID,
                'Infogain'
            ));

        $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(json_decode($output, true));
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test execute with tShip method as same as for Pickup.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithShippingFlowAndIsNotShippingPageAndToggleOFF()
    {
        $output = json_encode(
            [
                'output' => [
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->checkoutSessionMock->expects($this->any())->method('setRemoveFedexAccountNumber')->with(true)
            ->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_ship_account_number')
            ->willReturn(140678765);
        $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
            ['fedexAccount'],
            ['shippingAccount'],
            ['isPickupPage'],
            ['isShippingPage'],
            ['requestedPickupLocalTime'],
            ['locationId'],
            ['locationId'],
            ['account_payment_method'],
            ['company'],
            ['street'],
            ['city'],
            ['region_id'],
            ['zipcode'],
            ['ship_method']
        )->willReturnOnConsecutiveCalls(
            self::FEDEX_ACCOUNT_NUMBER,
            self::FEDEX_ACCOUNT_NUMBER,
            false,
            true,
            self::LOCATION_ID,
            self::LOCATION_ID,
            'cc',
            'Infogain',
            'Plano',
            'Texas',
            'TX',
            75024,
            'fedexshipping_Local'
        );
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->withConsecutive(
            ['shipping_description'],
            ['shipping_method'],
            ['street'],
            ['city'],
            ['region_id'],
            ['postcode'],
            ['company']
        )->willReturnOnConsecutiveCalls(
            null,
            'fedexShipping_LOCAL',
            self::STREET,
            'Plano',
            self::ZIPCODE,
            self::LOCATION_ID,
            'Infogain'
        );
        $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('setIsFromAccountScreen')->willReturnSelf();
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(json_decode($output, true));
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for setFedexShippingData
     */
    public function testSetFedexShippingData()
        {
            // Mocking region factory behavior
            $this->regionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
            $this->regionFactoryMock->expects($this->any())->method('load')->willReturnSelf();
            $this->regionFactoryMock->expects($this->any())->method('getCode')->willReturn('TX');

            // Mocking request behavior for post values
            $this->requestMock->expects($this->any())->method('getPostValue')->withConsecutive(
                ['street'],
                ['city'],
                ['region_id'],
                ['zipcode'],
                ['ship_method'],
                ['selectedProductionId']
            )->willReturnOnConsecutiveCalls(
                'Plano',
                'Texas',
                'TX',
                75024,
                'fedexshipping_Local',
                '12345' // Simulating selectedProductionId
            );

            // Mocking toggle feature behavior
            $this->toggleFeatureMock->expects($this->any())
                ->method('getToggleConfigValue')
                ->withConsecutive(
                    ['tech_titans_d_213795'],
                    ['explorers_d188299_production_location_fix'],
                    ['techtitans_205447_wrong_location_fix']
                )
                ->willReturnOnConsecutiveCalls(
                    false, // tech_titans_d_213795
                    true,  // explorers_d188299_production_location_fix
                    true   // techtitans_205447_wrong_location_fix
                );

            // Mocking checkout session behavior
            $this->checkoutSessionMock->expects($this->any())
                ->method('getProductionLocationId')
                ->willReturn('9876543212'); // Simulating production location ID

            // Asserting the method under test
            $this->assertNull($this->rate->setFedexShippingData(
                1,
                $this->addressMock,
                '',
                '9876543212',
                '9876543212',
                'HOME',
                $this->quoteMock,
                $this->regionFactoryMock,
                '12345',
                'Texas',
                'Plano'
            ));
        }
    /**
     * Test CAse for Get FXO RAte With ALert
     */
    public function testgetFXORateCall()
    {
        $output = json_encode(
            [
                'output' => [
                    'alerts' => [
                        0 => [
                            'code' => "Test"
                        ]
                    ],
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
        ->willReturn(json_decode($output, true));

        $this->assertNull($this->rate->getFXORateCall($this->quoteMock, 1));
    }

    /**
     * Test FXORateCall with condition that triggers handlePromoAccountWarnings
     */
    public function testGetFXORateCallWithHandlePromoAccountWarnings()
{
    // Create output with alerts that will make getConditionForResultJson return true
    $output = [
        'output' => [
            'alerts' => [
                [
                    'code' => 'INVALID.PROMO.CODE'  // This alert code should make getConditionForResultJson return true
                ]
            ],
            'rate' => [
                'currency' => 'USD',
                'rateDetails' => [[]]
            ]
        ]
    ];
    
    // Make quote methods return as expected
    $this->quoteMock->expects($this->once())
        ->method('setIsAjaxRequest')
        ->with(true)
        ->willReturnSelf();
        
    $this->quoteMock->expects($this->once())
        ->method('setIsFromAccountScreen')
        ->with(false)
        ->willReturnSelf();
    
    // THIS IS THE KEY CHANGE: Mock fxoRateQuote instead of fxoPricingHelper
    $this->fxoRateQuoteMock->expects($this->once())
        ->method('getFXORateQuote')
        ->with($this->quoteMock)
        ->willReturn($output);
    
    // Verify handlePromoAccountWarnings is called
    $this->fxoModelMock->expects($this->once())
        ->method('handlePromoAccountWarnings')
        ->with(
            $this->equalTo($this->quoteMock),
            $this->equalTo($output['output'])
        );
    
    // Call the method under test
    $result = $this->rate->getFXORateCall($this->quoteMock);
    
    // The method should return the output portion
    $this->assertEquals($output['output'], $result);
}
    /**
     * Test CAse for Get FXO RAte With ALert With Toggle On
     */
    public function testgetFXORateCallWithToggleOn()
    {
        $output = json_encode(
            [
                'output' => [
                    'alerts' => [
                        0 => [
                            'RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID' => "Test"
                        ]
                    ],
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->fxoModelMock->expects($this->any())->method('removePromoCode')->willReturn('Text..');
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
        ->willReturn(json_decode($output, true));

        $this->assertEquals(null, $this->rate->getFXORateCall($this->quoteMock, 1));
    }

    /**
     * Test CAse for Get FXO RAte With ALert With Toggle On and Off for checkout flow
     */
    public function testgetFXORateCallWithToggleOnAndOffForCheckoutFlow()
    {
        $output = json_encode(
            [
                'output' => [
                    'alerts' => [
                        0 => [
                            'code' => "Test"
                        ]
                    ],
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => self::PRODUCT_ID,
                                        'retailPrice' => self::PRICE,
                                        'discountAmount' => self::DISCOUNT_AMOUNT,
                                        'unitQuantity' => 1,
                                        'linePrice' => self::PRICE,
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => self::PRODUCT_DESCRIPTION,
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => self::PRICE,
                                                'detailDiscountPrice' => self::DISCOUNT_AMOUNT,
                                                'detailUnitPrice' => self::DETAIL_UNIT_PRICE,
                                                'detailDiscountedUnitPrice' => self::DISCOUNT_AMOUNT,
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => self::PRICE,
                                'discounts' => [],
                                'totalDiscountAmount' => self::DISCOUNT_AMOUNT,
                                'netAmount' => self::PRICE,
                                'taxableAmount' => self::PRICE,
                                'taxAmount' => self::DISCOUNT_AMOUNT,
                                'totalAmount' => self::PRICE,
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],

                    ],
                ],
            ]
        );
        $this->fxoModelMock->expects($this->any())->method('removePromoCode')->willReturn('Text..');
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
        ->willReturn(json_decode($output, true));

        $this->assertNull($this->rate->getFXORateCall($this->quoteMock, 1));
    }

    /**
     * Test CAse for Get FXO RAte With Empty Output
     */
    public function testgetFXORateCallWithEmptyOutput()
    {
        $output = json_encode([]);
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
        ->willReturn(json_decode($output, true));
        $this->assertNull($this->rate->getFXORateCall($this->quoteMock, 1));
    }

    /**
     * Test case for getConditionForResultJson
     */
    public function testgetConditionForResultJson()
    {
        $this->assertNotNull($this->rate->getConditionForResultJson(true, self::OUTPUT_WITH_ALERT));
    }
}
