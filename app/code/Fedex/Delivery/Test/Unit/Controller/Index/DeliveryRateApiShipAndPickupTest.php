<?php

/**
 * Php file for test case of RateAPI.
 *
 * @author  Infogain <Team_Explorer@infogain.com>
 * @license Reserve For fedEx
 */

namespace Fedex\Delivery\Test\Unit\Controller\Index;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Controller\Index\DeliveryRateApiShipAndPickup;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Model\ShippingMessage;
use Fedex\Delivery\Model\ShippingMessage\TransportInterfaceFactory;
use Fedex\Delivery\Model\ShippingMessage\TransportInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Api\RateBuilderInterface;
use Fedex\FXOPricing\Api\RateQuoteBuilderInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\Pay\Helper\Data as PayHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Directory\Model\Region;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Fedex\InBranch\Model\InBranchValidation;

class DeliveryRateApiShipAndPickupTest extends TestCase
{
    protected $context;
    protected $cartFactoryMock;
    protected $cartMock;
    protected $inBranchValidationMock;
    protected $quoteMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Item & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $itemMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Item\Option & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $itemOptionMock;
    protected $customerSessionMock;
    protected $checkoutSessionMock;
    protected $companyRepositoryMock;
    protected $companyInterfaceMock;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterfaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $requestMock;
    protected $abstractModelMock;
    /**
     * @var (\Fedex\Punchout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $punchoutHelperMock;
    /**
     * @var (\Magento\Framework\HTTP\Client\Curl & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $curlMock;
    protected $regionFactoryMock;
    protected $jsonMock;
    /**
     * @var (\Fedex\Pay\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $payHelperMock;
    protected $resultJsonFactoryMock;
    protected $toggleFeatureMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Address & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressMock;
    protected $regionMock;
    protected $fxoPricingHelperMock;
    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelperMock;
    protected $deliveryHelperMock;
    protected $cartDataHelperMock;
    protected $sdeHelper;
    /**
     * @var (\Fedex\Delivery\Helper\QuoteDataHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteDataHelper;
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuote;
    /**
     * @var (\Fedex\MarketplaceCheckout\Model\QuoteOptions & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteOptions;
    /**
     * @var (\Fedex\Delivery\Model\ShippingMessage & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingMessageMock;
    /**
     * @var (\Fedex\FXOPricing\Api\RateBuilderInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rateBuilderInterface;
    /**
     * @var (\Fedex\FXOPricing\Api\RateQuoteBuilderInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rateQuoteBuilderMock;
    protected $transportFactoryMock;
    protected $rate;
    public const OUTPUT = [
        'output' => [
            'rate' => [
                'currency'    => 'USD',
                'rateDetails' => [
                    0 => [
                        'productLines'        => [
                            0 => [
                                'instanceId'            => 0,
                                'productId'             => '1508784838900',
                                'retailPrice'           => '$0.9',
                                'discountAmount'        => '$0.0',
                                'unitQuantity'          => 1,
                                'linePrice'             => '$0.479',
                                'priceable'             => 1,
                                'productLineDetails'    => [
                                    0 => [
                                        'detailCode'                => '0173',
                                        'description'               => 'Single Sided Color',
                                        'detailCategory'            => 'PRINTING',
                                        'unitQuantity'              => 1,
                                        'unitOfMeasurement'         => 'EACH',
                                        'detailPrice'               => '$0.79',
                                        'detailDiscountPrice'       => '$0.70',
                                        'detailUnitPrice'           => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.08',
                                    ],
                                ],
                                'productRetailPrice'    => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice'      => '0.49',
                                'editable'              => '',
                            ],
                        ],
                        'grossAmount'         => '$0.9',
                        'discounts'           => [],
                        'totalDiscountAmount' => '$0.005',
                        'netAmount'           => '$0.59',
                        'taxableAmount'       => '$0.69',
                        'taxAmount'           => '$0.0',
                        'totalAmount'         => '$0.59',
                        'estimatedVsActual'   => 'ACTUAL',
                    ],
                ],

            ],
        ],
    ];

    public const OUTPUT_WITH_RATEQUOTE = [
        'output' => [
            'rateQuote' => [
                'currency'    => 'USD',
                'rateDetails' => [
                    0 => [
                        'productLines'        => [
                            0 => [
                                'instanceId'            => 0,
                                'productId'             => '1508784838900',
                                'retailPrice'           => '$0.9',
                                'discountAmount'        => '$0.0',
                                'unitQuantity'          => 1,
                                'linePrice'             => '$0.479',
                                'priceable'             => 1,
                                'productLineDetails'    => [
                                    0 => [
                                        'detailCode'                => '0173',
                                        'description'               => 'Single Sided Color',
                                        'detailCategory'            => 'PRINTING',
                                        'unitQuantity'              => 1,
                                        'unitOfMeasurement'         => 'EACH',
                                        'detailPrice'               => '$0.79',
                                        'detailDiscountPrice'       => '$0.70',
                                        'detailUnitPrice'           => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.08',
                                    ],
                                ],
                                'productRetailPrice'    => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice'      => '0.49',
                                'editable'              => '',
                            ],
                        ],
                        'grossAmount'         => '$0.9',
                        'discounts'           => [],
                        'totalDiscountAmount' => '$0.005',
                        'netAmount'           => '$0.59',
                        'taxableAmount'       => '$0.69',
                        'taxAmount'           => '$0.0',
                        'totalAmount'         => '$0.59',
                        'estimatedVsActual'   => 'ACTUAL',
                    ],
                ],

            ],
        ],
    ];
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

    /**
     * Description Creating variable for defining the constuctor
     * {@inheritdoc}
     *
     * @var $objectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * Creating the Mock.
     *
     * @author  Infogain <Team_Explorer@infogain.com>
     * @license Reserve For fedEx
     * @return  MockBuilder
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->inBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getShippingAddress',
                    'getAllItems',
                    'getData',
                    'setGrandTotal',
                    'setBaseGrandTotal',
                    'setCustomTaxAmount',
                    'save',
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
                    'setRegionCode'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemMock            = $this->getMockBuilder(Item::class)
            ->setMethods(['getOptionByCode'])
            ->disableOriginalconstructor()
            ->getMock();
        $this->itemOptionMock      = $this->getMockBuilder(Option::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['getCustomer', 'getCustomerCompany', 'getApiAccessToken', 'getApiAccessType', 'getGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['getProductionLocationId','setProductionLocationId','unsProductionLocationId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyInterfaceMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowProductionLocation', 'getProductionLocationOption'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock          = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock         = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue', 'getContent', 'getPost'])
            ->getMockForAbstractClass();
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->abstractModelMock   = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();
        $this->punchoutHelperMock  = $this->getMockBuilder(PunchoutHelper::class)
            ->setMethods(['getTazToken', 'getGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlMock            = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionFactoryMock   = $this->getMockBuilder(RegionFactory::class)
            ->setMethods(['create', 'load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock      = $this->getMockBuilder(Json::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->payHelperMock = $this->getMockBuilder(PayHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleFeatureMock     = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock           = $this->getMockBuilder(Address::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock           = $this->getMockBuilder(Region::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoPricingHelperMock = $this->getMockBuilder(FXORate::class)
            ->setMethods(['getFXORate', 'removePromoCode', 'resetPromoCode'])
            ->disableOriginalconstructor()
            ->getMock();
        $this->companyHelperMock    = $this->getMockBuilder(CompanyHelper::class)
            ->setMethods(['getCompanyPaymentMethod', 'getFedexAccountNumber'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryHelperMock   = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(
                [
                    'callRateApi',
                    'isCommercialCustomer',
                    'getCompanySite',
                    'getApiToken',
                    'getGateToken',
                    'resetPromoCode'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData', 'encryptData', 'getAddressClassification'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteDataHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\QuoteDataHelper::class)
            ->setMethods(['resetPromoCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRateQuote = $this->getMockBuilder(\Fedex\FXOPricing\Model\FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteOptions = $this->getMockBuilder(QuoteOptions::class)
            ->setMethods(['setMktShipMethodDataItemOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper  = new ObjectManagerHelper($this);
        $this->shippingMessageMock = $this->createMock(ShippingMessage::class);
        $this->rateBuilderInterface = $this->createMock(RateBuilderInterface::class);
        $this->rateQuoteBuilderMock = $this->createMock(RateQuoteBuilderInterface::class);
        $this->transportFactoryMock = $this->createMock(TransportInterfaceFactory::class);
        $this->transportFactoryMock->method('create')->willReturn($this->objectManagerHelper->getObject(ShippingMessage\Transport::class));
        $this->rate = $this->objectManagerHelper->getObject(
            DeliveryRateApiShipAndPickup::class,
            [
                'context'           => $this->context,
                'cartFactory'       => $this->cartFactoryMock,
                'helper'            => $this->deliveryHelperMock,
                'companyHelper'     => $this->companyHelperMock,
                'logger'            => $this->loggerMock,
                'request'           => $this->requestMock,
                'gateTokenHelper'   => $this->punchoutHelperMock,
                'regionFactory'     => $this->regionFactoryMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'payHelper'         => $this->payHelperMock,
                'customerSession'   => $this->customerSessionMock,
                'checkoutSession'   => $this->checkoutSessionMock,
                'companyRepository' => $this->companyRepositoryMock,
                'toggleConfig'      => $this->toggleFeatureMock,
                'fxoPricingHelper'  => $this->fxoPricingHelperMock,
                'cartDataHelper'    => $this->cartDataHelperMock,
                'sdeHelper'         => $this->sdeHelper,
                'quoteDataHelper'   => $this->quoteDataHelper,
                'quoteOptions'      => $this->quoteOptions,
                'shippingMessage'   => $this->shippingMessageMock,
                'transportFactory'  => $this->transportFactoryMock,
                'rateBuilder'       => $this->rateBuilderInterface,
                'rateQuoteBuilder'  => $this->rateQuoteBuilderMock,
                'inbranchvalidation' => $this->inBranchValidationMock
            ]
        );
    } //end setUp()

    /**
     * Test Case for getProductionLocationId
     */
    public function testgetProductionLocationId()
    {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterfaceMock->expects($this->any())
            ->method('getProductionLocationOption')->willReturn('recommended_location_all_locations');
        $this->checkoutSessionMock->expects($this->any())->method('getProductionLocationId')->willReturn(null);
        $this->assertEquals(null, $this->rate->getProductionLocationId());
    }

    /**
     * Test Case for getProductionLocationId With Valid ProductionLocationId
     */
    public function testGetProductionLocationIdForCommercialCustomerWithValidProductionLocationId()
    {
        $this->deliveryHelperMock->method('isCommercialCustomer')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterfaceMock->method('getProductionLocationOption')->willReturn('recommended_location_all_locations');
        $this->checkoutSessionMock->method('getProductionLocationId')->willReturn('1234');

        $result = $this->rate->getProductionLocationId();
        $this->assertNull($result);
    }

    /**
     * Test Case for getProductionLocationId For Non-CommercialCustomer
     */
    public function testGetProductionLocationIdForNonCommercialCustomer()
    {
        $this->deliveryHelperMock->method('isCommercialCustomer')->willReturn(false);
        $result = $this->rate->getProductionLocationId();
        $this->assertNull($result);
    }

    /**
     * Test Case for getProductionLocationId For InBranchUser with toggle
     */
    public function testGetProductionLocationIdForInBranchUserWithProductionLocationFixToggle()
    {
        $this->deliveryHelperMock->method('isCommercialCustomer')->willReturn(true);
        $this->inBranchValidationMock->method('isInBranchUser')->willReturn(true);
        $this->toggleFeatureMock->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterfaceMock->method('getProductionLocationOption')->willReturn('recommended_location_all_locations');
        $this->checkoutSessionMock->method('getProductionLocationId')->willReturn('5678');
        $this->inBranchValidationMock->method('getAllowedInBranchLocation')->willReturn('');
        $result = $this->rate->getProductionLocationId();
        $this->assertNull($result);
    }

    /**
     * Test Case for getProductionLocationId For InBranchUser without toggle
     */
    public function testGetProductionLocationIdForInBranchUserWithoutProductionLocationFixToggle()
    {
        $this->deliveryHelperMock->method('isCommercialCustomer')->willReturn(true);
        $this->inBranchValidationMock->method('isInBranchUser')->willReturn(true);
        $this->toggleFeatureMock->method('getToggleConfigValue')->willReturn(false);

        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterfaceMock);
        $this->companyInterfaceMock->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->companyInterfaceMock->method('getProductionLocationOption')->willReturn('recommended_location_all_locations');
        $this->checkoutSessionMock->method('getProductionLocationId')->willReturn('9012');
        $this->inBranchValidationMock->method('getAllowedInBranchLocation')->willReturn('7890');
        $result = $this->rate->getProductionLocationId();
        $this->assertNull($result);
    }

    /**
     * Test case for setCouponData
     */
    public function testsetCouponData()
    {
        $requestDecodedData = [
            'coupon_code' => 'MGT001',
            'remove_coupon' => 1
        ];
        $this->assertEquals(null, $this->rate->setCouponData($this->quoteMock, $requestDecodedData));
    }

    /**
     * Test case for getRegionData
     */
    public function testgetRegionData()
    {
        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('load')->willReturn($this->abstractModelMock);

        $this->assertEquals($this->abstractModelMock, $this->rate->getRegionData('TX'));
    }

    /**
     * Test case for setPickupData
     */
    public function testsetPickupData()
    {
        $requestDecodedData = [
            'coupon_code' => 'MGT001',
            'remove_coupon' => 1,
            'locationId' => 75024
        ];
        $fedexAccountNumber = '654532431';
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->testsetCouponData();
        $this->assertEquals(
            null,
            $this->rate->setPickupData($this->quoteMock, 75024, $requestDecodedData, $fedexAccountNumber)
        );
    }

    /**
     * Test case for setShippingData
     */
    public function testsetShippingData()
    {
        $requestPostData = (object)
        [
            'ship_method' => 'EXPRESS_SAVER',
            'zipcode' => 33324,
            'region_id' => 52,
            'city' => 'Plantation',
            'street' =>
            [
                '0' => 234
            ],
            'company' => 'Infogain',
            'fedEx_account_number' => '12345678',
            'is_residence_shipping' => 'true'
        ];
        $requestDecodedData = [
            'coupon_code' => 'MGT001',
            'remove_coupon' => 1,
            'ship_method' => 'Express_SAVER',
            'zipcode' => 75024,
            'region_id' => 'TX',
            'street' => 'PLANO',
            'city' => 'PLANO'
        ];
        $fedexAccountNumber = '654532431';
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->testgetRegionData();
        $this->assertEquals(
            null,
            $this->rate->setShippingData(
                $this->quoteMock,
                $requestPostData,
                $requestDecodedData,
                $fedexAccountNumber,
                false
            )
        );
    }

    /**
     * Test case for setShippingData with productionLocationFix toggle Enabled
     */
    public function testSetShippingDataProductionLocationFixToggleEnabledWithLocationId()
    {
        $requestPostData = (object)[
            'location_id' => '1234'
        ];

        $this->toggleFeatureMock->method('getToggleConfigValue')->willReturn(true);

        $this->checkoutSessionMock->expects($this->any())
            ->method('setProductionLocationId')
            ->with('1234');

        $this->checkoutSessionMock->expects($this->never())
            ->method('unsProductionLocationId');

        $this->rate->setShippingData(
            $this->quoteMock,
            $requestPostData,
            [],
            '',
            false
        );
    }

    /**
     * Test case for setShippingData with productionLocationFix toggle Enabled
     */
    public function testSetShippingDataProductionLocationFixToggleEnabledWithoutLocationId()
    {
        $requestPostData = (object)[];

        $this->toggleFeatureMock->method('getToggleConfigValue')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->never())
            ->method('setProductionLocationId');

        $this->checkoutSessionMock->expects($this->any())
            ->method('unsProductionLocationId');

        $this->rate->setShippingData(
            $this->quoteMock,
            $requestPostData,
            [],
            '',
            false
        );
    }

    /**
     * Test case for setShippingData with productionLocationFix toggle sisabled
     */
    public function testSetShippingDataProductionLocationFixToggleDisabled()
    {
        $requestPostData = (object)[
            'location_id' => '1234'
        ];

        $this->toggleFeatureMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->checkoutSessionMock->expects($this->never())
            ->method('setProductionLocationId');

        $this->checkoutSessionMock->expects($this->never())
            ->method('unsProductionLocationId');

        $this->rate->setShippingData(
            $this->quoteMock,
            $requestPostData,
            [],
            '',
            false
        );
    }

    /**
     * Test case for Execute
     */
    public function testExecute()
    {
        $requestData = [
            'ship_method' => 'YZ',
            'zipcode' => '24801',
            'region_id' =>  'TX',
            'city' => 'Texas',
            'street' => '720DR',
            'shipfedexAccountNumber' => '1234678',
            'coupon_code' => 'MGT001',
            'remove_coupon' => 0,
            'isShippingPage' => true,
            'isPickupPage' => true,
            'locationId' => '121',
            'coupon_flag' => 0
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->requestMock->expects($this->any())->method('getContent')->willReturn(json_encode($requestData));
        $this->requestMock->expects($this->any())->method('getPost')->willReturn($requestData);
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5m3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(self::OUTPUT_WITH_RATEQUOTE);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willreturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for Execute With Pickup Page null
     */
    public function testExecuteWithNoPickupPage()
    {
        $requestData = [
            'ship_method' => 'YZ',
            'zipcode' => '24001',
            'region_id' =>  'TX',
            'city' => 'Texas',
            'street' => '20 DR',
            'shipfedexAccountNumber' => '2345678',
            'coupon_code' => 'MGT001',
            'remove_coupon' => 0,
            'isShippingPage' => true,
            'isPickupPage' => false,
            'locationId' => null,
            'coupon_flag' => 0
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->requestMock->expects($this->any())->method('getContent')->willReturn(json_encode($requestData));
        $this->requestMock->expects($this->any())->method('getPost')->willReturn($requestData);
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5mx3jYOJmr+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(self::OUTPUT);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willreturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->testsetShippingData();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for Execute
     */
    public function testExecuteWithNoOutput()
    {
        $requestData = [
            'ship_method' => 'YZ',
            'zipcode' => '248001',
            'region_id' =>  'TX',
            'city' => 'Texas',
            'street' => '720 DR',
            'shipfedexAccountNumber' => '12345678',
            'coupon_code' => 'MGT001',
            'remove_coupon' => 0,
            'isShippingPage' => true,
            'isPickupPage' => true,
            'locationId' => '121',
            'coupon_flag' => 0
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->requestMock->expects($this->any())->method('getContent')->willReturn(json_encode($requestData));
        $this->requestMock->expects($this->any())->method('getPost')->willReturn($requestData);
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5mx3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(null);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willreturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for Execute
     */
    public function testExecuteWithAlertOutput()
    {
        $requestData = [
            'ship_method' => 'YZ',
            'zipcode' => '248001',
            'region_id' =>  'TX',
            'city' => 'Texas',
            'street' => '720 DR',
            'shipfedexAccountNumber' => '12345678',
            'coupon_code' => 'MGT001',
            'remove_coupon' => 0,
            'isShippingPage' => true,
            'isPickupPage' => true,
            'locationId' => '121',
            'coupon_flag' => 0
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->requestMock->expects($this->any())->method('getContent')->willReturn(json_encode($requestData));
        $this->requestMock->expects($this->any())->method('getPost')->willReturn($requestData);
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn('0:3:7D96hHguKjU5P5mx3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==');
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')->willReturn(12345678);
        $this->fxoPricingHelperMock->expects($this->any())->method('getFXORate')
            ->willReturn(self::OUTPUT_WITH_ALERT);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(0);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willreturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for Execute with Exception
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willreturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for Execute with Exception With toggle OFF
     */
    public function testExecuteWithExceptionToggleOFF()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(0);
        $this->cartFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->resultJsonFactoryMock->expects($this->any())->method('create')->willreturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertEquals($this->jsonMock, $this->rate->execute());
    }

    /**
     * Test case for setErrorReturnData
     */
    public function testsetErrorReturnData()
    {
        $this->assertNotNull($this->rate->setErrorReturnData(false, json_encode(self::OUTPUT)));
    }

    /**
     * Test for getCouponCodeFromSidebar
     */
    public function testgetCouponCodeFromSidebar()
    {
        $requestDataDecoded = [
            'couponAppliedFromSidebar' => true
        ];
        $this->assertNotNull($this->rate->getCouponCodeFromSidebar($requestDataDecoded));
    }

    /**
     * Test for getCouponCodeFromSidebar
     */
    public function testgetCouponCodeFromSidebarWithFalse()
    {
        $requestDataDecoded = [
            'couponAppliedFromSidebar' => false
        ];
        $this->assertNotNull($this->rate->getCouponCodeFromSidebar($requestDataDecoded));
    }

    /**
     * Test case for returnJsonData
     */
    public function testreturnJsonData()
    {
        $this->assertNull(
            $this->rate->returnJsonData($this->resultJsonFactoryMock, self::OUTPUT)
        );
    }

    /**
     * Test case for returnJsonData
     */
    public function testreturnJsonDataWithFalse()
    {
        $this->assertNull(
            $this->rate->returnJsonData($this->resultJsonFactoryMock, self::OUTPUT)
        );
    }

    /**
     * Test case for handlingDataWhenCouponNotAppliedFromSidebar
     */
    public function testhandlingDataWhenCouponNotAppliedFromSidebar()
    {
        $this->rate->getConditionForResultJson(true, self::OUTPUT_WITH_ALERT);
        $this->fxoPricingHelperMock->expects($this->any())->method('removePromoCode')->willReturn('Some Text');
        $this->assertNull($this->rate->handlingDataWhenCouponNotAppliedFromSidebar(
            true,
            true,
            self::OUTPUT_WITH_ALERT,
            false,
            $this->quoteMock
        ));
    }

    /**
     * Test case for getConditionForResultJson
     */
    public function testgetConditionForResultJson()
    {
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->assertNotNull($this->rate->getConditionForResultJson(true, self::OUTPUT_WITH_ALERT));
    }

    /**
     * Test case for handlingAlertCase
     */
    public function testhandlingAlertCase()
    {
        $this->assertNull($this->rate->handlingAlertCase(
            self::OUTPUT_WITH_ALERT,
            $this->quoteMock,
            false,
            $this->jsonMock
        ));
    }

    /**
     * Test case for handlingAlertCase
     */
    public function testhandlingAlertCaseWithToggleOn()
    {
        $this->fxoPricingHelperMock->expects($this->any())->method('removePromoCode')->willReturn(true);
        $this->assertNull($this->rate->handlingAlertCase(
            self::OUTPUT_WITH_ALERT,
            $this->quoteMock,
            false,
            $this->jsonMock
        ));
    }

    /**
     * Test case for handlingAlertCaseWithCouponAppliedOnSidebar
     */
    public function testhandlingAlertCaseWithCouponAppliedOnSidebar()
    {
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->assertNull($this->rate->handlingAlertCase(
            self::OUTPUT_WITH_ALERT,
            $this->quoteMock,
            true,
            $this->jsonMock
        ));
    }

    /**
     * Test case for handlingAlertCaseWithCouponAppliedOnSidebarWithElse
     */
    public function testhandlingAlertCaseWithCouponAppliedOnSidebarWithElse()
    {
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(0);
        $this->assertNull($this->rate->handlingAlertCase(
            self::OUTPUT_WITH_ALERT,
            $this->quoteMock,
            true,
            $this->jsonMock
        ));
    }

    /**
     * Test case for SetContactInformation
     */
    public function testSetContactInformation()
    {
        $requestPostData = (object)
        [
            'firstname' => 'Attri',
            'lastname' => 'Kumar',
            'email' => 'attri.kumar@infogain.com',
            'telephone' => '3324323432'
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);

        $this->assertEquals(
            null,
            $this->rate->setContactInformation(
                $this->quoteMock,
                $requestPostData,
                false
            )
        );
    }

    /**
     * Test case for setPickupPageLocation
     */
    public function testSetPickupPageLocation()
    {
        $requestDecodedData = [
            'coupon_code' => 'MGT001',
            'remove_coupon' => 1,
            'pickupPageLocation' => 'true'
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn('true');
        $this->assertNull($this->rate->setPickupPageLocation($this->quoteMock, $requestDecodedData, false));
    }

    /**
     * Test case for setPickupPageLocation
     */
    public function testSetPickupPageLocationWithPickupPageLocation()
    {
        $requestDecodedData = [
            'coupon_code' => 'MGT001',
            'remove_coupon' => 1,
            'pickupPageLocation' => 'true',
        ];
        $this->toggleFeatureMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->requestMock->expects($this->any())->method('getPostValue')->willReturn(null);
        $this->assertNull($this->rate->setPickupPageLocation($this->quoteMock, $requestDecodedData, false));
    }
}
