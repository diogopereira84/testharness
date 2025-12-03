<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\TransactionApi;

use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Helper\SubmitOrderOptimizedHelper;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\HTTP\LaminasClient;
use Laminas\Http\Response;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Fedex\SubmitOrderSidebar\Model\BillingAddressBuilder;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\InStoreRequestBuilder;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\RateQuoteAndTransactionApiHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InStoreConfigInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\CoreApi\Model\LogHelperApi;

class RateQuoteAndTransactionApiHandlerTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var (\Fedex\CartGraphQl\Helper\LoggerHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerHelperMock;
    /**
     * @var (\Magento\Directory\Model\Region & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $regionMock;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime\TimezoneInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $timezoneInterfaceMock;
    /**
     * @var (\Magento\Framework\Stdlib\DateTime\DateTime & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dateTimeMock;
    protected $quoteMock;
    protected $addressMock;
    protected $dataObjectFactory;
    /**
     * Quote id
     */
    public const QUOTE_ID = 238;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected CartRepositoryInterface|MockObject $quoteRepositoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected ScopeConfigInterface|MockObject $configInterfaceMock;

    /**
     * @var DeliveryHelper|MockObject
     */
    protected DeliveryHelper|MockObject $deliveryHelperMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected LoggerInterface|MockObject $loggerMock;

    /**
     * @var RegionFactory|MockObject
     */
    protected RegionFactory|MockObject $regionFactoryMock;

    /**
     * @var Curl|MockObject
     */
    protected Curl|MockObject $curlMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected ToggleConfig|MockObject $toggleConfigMock;

    /**
     * @var CompanyHelper|MockObject
     */
    protected CompanyHelper|MockObject $companyHelperMock;

    /**
     * @var SdeHelper|MockObject
     */
    protected SdeHelper|MockObject $sdeHelperMock;

    /**
     * @var DataObjectFactory|MockObject
     */
    private DataObjectFactory|MockObject $dataObjectFactoryMock;

    /**
     * @var PunchoutHelper|MockObject
     */
    private PunchoutHelper|MockObject $punchoutHelperMock;

    /**
     * @var SubmitOrderHelper|MockObject
     */
    private SubmitOrderHelper|MockObject $submitOrderHelperMock;

    /**
     * @var SubmitOrderOptimizedHelper|MockObject
     */
    private SubmitOrderOptimizedHelper|MockObject $submitOrderOptimizedHelperMock;

    /**
     * @var SubmitOrderDataArray|MockObject
     */
    private SubmitOrderDataArray|MockObject $submitOrderDataArrayMock;

    /**
     * @var EnhancedProfile|MockObject
     */
    protected EnhancedProfile|MockObject $enhancedProfileMock;

    /**
     * @var LaminasClientFactory|MockObject
     */
    protected LaminasClientFactory|MockObject $laminasClientFactoryMock;

    /**
     * @var LaminasClient|MockObject
     */
    protected LaminasClient|MockObject $httpClientMock;

    /**
     * @var Response|MockObject
     */
    protected Response|MockObject $responseClientMock;

    /**
     * @var BillingAddressBuilder|MockObject
     */
    protected BillingAddressBuilder|MockObject $billingAddressBuilderMock;

    /**
     * @var InStoreRequestBuilder|MockObject
     */
    protected InStoreRequestBuilder|MockObject $inStoreRequestBuilderMock;

    /**
     * @var InStoreConfigInterface|MockObject
     */
    private InStoreConfigInterface|MockObject $inStoreConfigMock;

    /**
     * @var RateQuoteAndTransactionApiHandler
     */
    private RateQuoteAndTransactionApiHandler $apiHandlerMock;

    private LogHelperApi|MockObject $logHelperApiMock;

    /**
     * @var DataObject|MockObject
     */
    private DataObject $dataObjectForFujitsu;

    /**
     * @var array|array[]
     */
    protected array $apiData = [
        'rateRequest' => [
            'fedExAccountNumber' => null,
            'profileAccountId' => null,
            'site' => null,
            'siteName' => null,
            'products' => [
                0 => [
                    'productionContentAssociations' => [],
                    'userProductName' => 'Flyers',
                    'id' => '1463680545590',
                    'version' => 1,
                    'name' => 'Flyer',
                    'qty' => '50',
                    'priceable' => true,
                    'instanceId' => '0',
                    'proofRequired' => false,
                    'isOutSourced' => false,
                    'features' => [
                        0 => [
                            'id' => '1448981549109',
                            'name' => 'Paper Size',
                            'choice' => [
                                'id' => '1448986650332',
                                'name' => '8.5x11',
                                'properties' => [
                                    0 => [
                                        'id' => '1449069906033',
                                        'name' => 'MEDIA_HEIGHT',
                                        'value' => '11',
                                    ],
                                ],
                            ],
                        ],
                        1 => [
                            'id' => '1448981549581',
                            'name' => 'Print Color',
                            'choice' => [
                                'id' => '1448988600611',
                                'name' => 'Full Color',
                                'properties' => [
                                    0 => [
                                        'id' => '1453242778807',
                                        'name' => 'PRINT_COLOR',
                                        'value' => 'COLOR',
                                    ],
                                ],
                            ],
                        ],
                        2 => [
                            'id' => '1448981549269',
                            'name' => 'Sides',
                            'choice' => [
                                'id' => '1448988124560',
                                'name' => 'Single-Sided',
                                'properties' => [
                                    0 => [
                                        'id' => '1470166759236',
                                        'name' => 'SIDE_NAME',
                                        'value' => 'Single Sided',
                                    ],
                                    1 => [
                                        'id' => '1461774376168',
                                        'name' => 'SIDE',
                                        'value' => 'SINGLE',
                                    ],
                                ],
                            ],
                        ],
                        3 => [
                            'id' => '1448984679218',
                            'name' => 'Orientation',
                            'choice' => [
                                'id' => '1449000016327',
                                'name' => 'Horizontal',
                                'properties' => [
                                    0 => [
                                        'id' => '1453260266287',
                                        'name' => 'PAGE_ORIENTATION',
                                        'value' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        4 => [
                            'id' => '1448981549741',
                            'name' => 'Paper Type',
                            'choice' => [
                                'id' => '1448988664295',
                                'name' => 'Laser(32 lb.)',
                                'properties' => [
                                    0 => [
                                        'id' => '1450324098012',
                                        'name' => 'MEDIA_TYPE',
                                        'value' => 'E32',
                                    ],
                                    1 => [
                                        'id' => '1453234015081',
                                        'name' => 'PAPER_COLOR',
                                        'value' => '#FFFFFF',
                                    ],
                                    2 => [
                                        'id' => '1470166630346',
                                        'name' => 'MEDIA_NAME',
                                        'value' => '32lb',
                                    ],
                                    3 => [
                                        'id' => '1471275182312',
                                        'name' => 'MEDIA_CATEGORY',
                                        'value' => 'RESUME',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pageExceptions' => [],
                    'contentAssociations' => [
                        0 => [
                            'parentContentReference' => '12860750446056166911617735200641029290568',
                            'contentReference' => '12860750448095622616502696333961844939287',
                            'contentType' => 'IMAGE',
                            'fileName' => 'nature1.jpeg',
                            'contentReqId' => '1455709847200',
                            'name' => 'Front_Side',
                            'desc' => null,
                            'purpose' => 'SINGLE_SHEET_FRONT',
                            'specialInstructions' => '',
                            'printReady' => true,
                            'pageGroups' => [
                                0 => [
                                    'start' => 1,
                                    'end' => 1,
                                    'width' => 11,
                                    'height' => 8.5,
                                    'orientation' => 'LANDSCAPE',
                                ],
                            ],
                        ],
                    ],
                    'properties' => [
                        0 => [
                            'id' => '1453242488328',
                            'name' => 'ZOOM_PERCENTAGE',
                            'value' => '50',
                        ],
                        1 => [
                            'id' => '1453243262198',
                            'name' => 'ENCODE_QUALITY',
                            'value' => '100',
                        ],
                        2 => [
                            'id' => '1453894861756',
                            'name' => 'LOCK_CONTENT_ORIENTATION',
                            'value' => false,
                        ],
                        3 => [
                            'id' => '1453895478444',
                            'name' => 'MIN_DPI',
                            'value' => '150.0',
                        ],
                        4 => [
                            'id' => '1454950109636',
                            'name' => 'USER_SPECIAL_INSTRUCTIONS',
                            'value' => null,
                        ],
                        5 => [
                            'id' => '1455050109636',
                            'name' => 'DEFAULT_IMAGE_WIDTH',
                            'value' => '8.5',
                        ],
                        6 => [
                            'id' => '1455050109631',
                            'name' => 'DEFAULT_IMAGE_HEIGHT',
                            'value' => '11',
                        ],
                        7 => [
                            'id' => '1464709502522',
                            'name' => 'PRODUCT_QTY_SET',
                            'value' => '50',
                        ],
                        8 => [
                            'id' => '1459784717507',
                            'name' => 'SKU',
                            'value' => '40005',
                        ],
                        9 => [
                            'id' => '1470151626854',
                            'name' => 'SYSTEM_SI',
                            'value' => 'ATTENTION TEAM MEMBER: Use the following instructions to produce this order.
                            DO NOT use the Production Instructions listed above.
                            Flyer Package specifications: Yield 50, Single Sided Color 8.5x11 32lb (E32),
                            Full Page, Add Retail: SKU 40005',
                        ],
                        10 => [
                            'id' => '1494365340946',
                            'name' => 'PREVIEW_TYPE',
                            'value' => 'DYNAMIC',
                        ],
                        11 => [
                            'id' => '1470151737965',
                            'name' => 'TEMPLATE_AVAILABLE',
                            'value' => 'YES',
                        ],
                        12 => [
                            'id' => '1459784776049',
                            'name' => 'PRICE',
                            'value' => null,
                        ],
                        13 => [
                            'id' => '1490292304798',
                            'name' => 'MIGRATED_PRODUCT',
                            'value' => 'true',
                        ],
                        14 => [
                            'id' => '1558382273340',
                            'name' => 'PNI_TEMPLATE',
                            'value' => 'NO',
                        ],
                        15 => [
                            'id' => '1602530744589',
                            'name' => 'CONTROL_ID',
                            'value' => '4',
                        ],
                    ],
                    'preview_url' => null,
                    'fxo_product' => null,
                ],
            ],
            'recipients' => [
                0 => [
                    'contact' => null,
                    'reference' => '',
                    'attention' => null,
                    'pickUpDelivery' => [
                        'location' => ['id' => '1786'],
                        'requestedPickupLocalTime' => '',
                    ],
                    'productAssociations' => [
                        0 => [
                            'id' => 0,
                            'quantity' => 50,
                        ],
                    ],
                ],
            ],
            'loyaltyCode' => null,
            'specialInstructions' => null,
            'coupons' => null,
        ],
    ];

    /**
     * @var string
     */
    protected $checkoutResponse = '{
        "output": {
           "rateQuote": {
              "currency": "USD",
              "rateQuoteDetails": [
                 {
                    "grossAmount": 56.79,
                    "totalDiscountAmount": 0,
                    "netAmount": 56.79,
                    "taxableAmount": 56.79,
                    "taxAmount": 2.89,
                    "totalAmount": 59.68,
                    "estimatedVsActual": "ACTUAL",
                    "productLines": [
                       {
                          "instanceId": "0",
                          "productId": "1463680545590",
                          "unitQuantity": 50,
                          "priceable": true,
                          "unitOfMeasurement": "EACH",
                          "productRetailPrice": 34.99,
                          "productDiscountAmount": 0,
                          "productLinePrice": 34.99,
                          "productLineDetails": [
                             {
                                "detailCode": "40005",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "description": "Full Pg Clr Flyr 50",
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": 34.99,
                                "detailDiscountPrice": 0,
                                "detailUnitPrice": 34.99,
                                "detailDiscountedUnitPrice": 0,
                                "detailCategory": "PRINTING"
                             }
                          ],
                          "name": "Fast Order Flyer",
                          "userProductName": "Fast Order Flyer",
                          "type": "PRINT_ORDER"
                       }
                    ],
                    "deliveryLines": [
                       {
                          "recipientReference": "1",
                          "priceable": true,
                          "deliveryLinePrice": 0,
                          "deliveryRetailPrice": 0,
                          "deliveryLineType": "PACKING_AND_HANDLING",
                          "deliveryDiscountAmount": 0
                       },
                       {
                          "recipientReference": "1",
                          "estimatedDeliveryLocalTime": "2021-12-23T16:30:00",
                          "estimatedShipDate": "2021-12-21",
                          "priceable": false,
                          "deliveryLinePrice": 21.8,
                          "deliveryRetailPrice": 21.8,
                          "deliveryLineType": "SHIPPING",
                          "deliveryDiscountAmount": 0,
                          "recipientContact": {
                             "personName": {
                                "firstName": "Attri",
                                "lastName": "Kumar"
                             },
                             "company": {
                                "name": "FXO"
                             },
                             "emailDetail": {
                                "emailAddress": "attri.kumar@infogain.com"
                             },
                             "phoneNumberDetails": [
                                {
                                   "phoneNumber": {
                                      "number": "9354554555"
                                   },
                                   "usage": "PRIMARY"
                                }
                             ]
                          },
                          "shipmentDetails": {
                             "address": {
                                "streetLines": [
                                   "234",
                                   null
                                ],
                                "city": "plano",
                                "stateOrProvinceCode": "75024",
                                "postalCode": "75024",
                                "countryCode": "US"
                             }
                          }
                       }
                    ],
                    "rateQuoteId": "bmw123"
                 }
              ]
           },
           "alerts": [
               "code" => ABC,
               "message" => "test"
           ],
           "checkout": {
              "transactionHeader": {
                 "retailTransactionId": "yogesh2"
              },
              "lineItems": [
                 {
                    "retailPrintOrderDetails": [
                       {
                          "productLines": "test lines"
                       }
                    ]
                 }
              ]
           }
        }
    }';

    /**
     * @var string
     */
    protected $checkoutResponseWithResponse = '{
        "response":{
            "output": {
                "rateQuote": {
                   "currency": "USD",
                   "rateQuoteDetails": [
                      {
                         "grossAmount": 56.79,
                         "totalDiscountAmount": 0,
                         "netAmount": 56.79,
                         "taxableAmount": 56.79,
                         "taxAmount": 2.89,
                         "totalAmount": 59.68,
                         "estimatedVsActual": "ACTUAL",
                         "productLines": [
                            {
                               "instanceId": "0",
                               "productId": "1463680545590",
                               "unitQuantity": 50,
                               "priceable": true,
                               "unitOfMeasurement": "EACH",
                               "productRetailPrice": 34.99,
                               "productDiscountAmount": 0,
                               "productLinePrice": 34.99,
                               "productLineDetails": [
                                  {
                                     "detailCode": "40005",
                                     "priceRequired": false,
                                     "priceOverridable": false,
                                     "description": "Full Pg Clr Flyr 50",
                                     "unitQuantity": 1,
                                     "quantity": 1,
                                     "detailPrice": 34.99,
                                     "detailDiscountPrice": 0,
                                     "detailUnitPrice": 34.99,
                                     "detailDiscountedUnitPrice": 0,
                                     "detailCategory": "PRINTING"
                                  }
                               ],
                               "name": "Fast Order Flyer",
                               "userProductName": "Fast Order Flyer",
                               "type": "PRINT_ORDER"
                            }
                         ],
                         "deliveryLines": [
                            {
                               "recipientReference": "1",
                               "priceable": true,
                               "deliveryLinePrice": 0,
                               "deliveryRetailPrice": 0,
                               "deliveryLineType": "PACKING_AND_HANDLING",
                               "deliveryDiscountAmount": 0
                            },
                            {
                               "recipientReference": "1",
                               "estimatedDeliveryLocalTime": "2021-12-23T16:30:00",
                               "estimatedShipDate": "2021-12-21",
                               "priceable": false,
                               "deliveryLinePrice": 21.8,
                               "deliveryRetailPrice": 21.8,
                               "deliveryLineType": "SHIPPING",
                               "deliveryDiscountAmount": 0,
                               "recipientContact": {
                                  "personName": {
                                     "firstName": "Attri",
                                     "lastName": "Kumar"
                                  },
                                  "company": {
                                     "name": "FXO"
                                  },
                                  "emailDetail": {
                                     "emailAddress": "attri.kumar@infogain.com"
                                  },
                                  "phoneNumberDetails": [
                                     {
                                        "phoneNumber": {
                                           "number": "9354554555"
                                        },
                                        "usage": "PRIMARY"
                                     }
                                  ]
                               },
                               "shipmentDetails": {
                                  "address": {
                                     "streetLines": [
                                        "234",
                                        null
                                     ],
                                     "city": "plano",
                                     "stateOrProvinceCode": "75024",
                                     "postalCode": "75024",
                                     "countryCode": "US"
                                  }
                               }
                            }
                         ],
                         "rateQuoteId": "bmw123"
                      }
                   ]
                },
                "alerts": {
                  "code":"Test"
                },
                "checkout": {
                   "transactionHeader": {
                      "retailTransactionId": "yogesh2"
                   },
                   "lineItems": [
                      {
                         "retailPrintOrderDetails": [
                            {
                               "productLines": "test lines"
                            }
                         ]
                      }
                   ]
                }
            }
        }
    }';

    /**
     * Postal code
     */
    public const POSTAL_CODE = '75024';

    /**
     * Street address
     */
    public const STREET_ADDRESS = '234 Home';

    /**
     * Main set up method
     */
    public function setUp() : void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->logHelperApiMock = $this->createMock(LogHelperApi::class);
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->configInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);
        $this->punchoutHelperMock = $this->createMock(PunchoutHelper::class);
        $this->submitOrderHelperMock = $this->createMock(SubmitOrderHelper::class);
        $this->submitOrderOptimizedHelperMock = $this->createMock(SubmitOrderOptimizedHelper::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->regionFactoryMock = $this->createMock(RegionFactory::class);
        $this->regionMock = $this->getMockBuilder(Region::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlMock = $this->createMock(Curl::class);
        $this->laminasClientFactoryMock = $this->getMockBuilder(LaminasClientFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->responseClientMock = $this->getMockBuilder(Response::class)
            ->setMethods(['getStatusCode', 'getReasonPhrase', 'getMessage', 'getBody'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->httpClientMock = $this->createMock(LaminasClient::class);
        $this->httpClientMock->method('send')->willReturn($this->responseClientMock);
        $this->laminasClientFactoryMock->method('create')->willReturn($this->httpClientMock);
        $this->inStoreRequestBuilderMock = $this->createMock(InStoreRequestBuilder::class);
        $this->dataObjectFactoryMock = $this->createMock(DataObjectFactory::class);
        $this->submitOrderDataArrayMock = $this->createMock(SubmitOrderDataArray::class);
        $this->timezoneInterfaceMock = $this->getMockBuilder(TimezoneInterface::class)
            ->setMethods(['date'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->setMethods(['gmtDate', 'format'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectForFujitsu = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData', 'setData', 'getOrderNumber'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->billingAddressBuilderMock = $this->createMock(BillingAddressBuilder::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->companyHelperMock = $this->createMock(CompanyHelper::class);
        $this->sdeHelperMock = $this->createMock(SdeHelper::class);
        $this->enhancedProfileMock = $this->getMockBuilder(EnhancedProfile::class)
            ->setMethods(['updateCreditCard'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getShippingAddress',
                    'setData',
                    'getData',
                    'save',
                    'getId',
                    'setBillingAddress',
                    'getAllItems',
                    'getPayment',
                    'getBillingAddress',
                    'getCustomerId',
                    'getCustomerEmail',
                    'setFjmpQuoteId',
                    'setEstimatedPickupTime'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->getMockForAbstractClass();

        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->setMethods(['create','getQuoteData','getPaymentData','getEncCCData',
                'getIsPickup','getShipmentId','getEstimatePickupTime',
                'getUseSiteCreditCard','getOrderData','getQuoteId','getOrderNumber',
                'getNumDiscountPrice','getShippingAccount','getRequestedAmount'
                ,'getCcToken','getNameOnCard','getExpirationMonth',
                'getExpirationYear','getNumTotal','getState',
                'getAccNo','getCondition','getPaymentMethod','getStateCode',
                'setDate','setFjmpRateQuoteId','setFname','setLname','setCompanyName',
                'setEmail','setPhNumber','setExtension','setNumDiscountPrice',
                'setShippingAccount','setRequestedAmount','setEncCCData',
                'setCcToken','setNameOnCard','setStreetAddress','setCity',
                'setShipperRegion','setStateCode','setZipCode','setAddressClassification',
                'setExpirationMonth','setExpirationYear','setPoReferenceId','setNumTotal',
                'setState','setAccNo','setCondition','setPaymentMethod', 'setQuoteData'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inStoreConfigMock = $this->getMockBuilder(InStoreConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->apiHandlerMock = (new ObjectManager($this))->getObject(
            RateQuoteAndTransactionApiHandler::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'configInterface' => $this->configInterfaceMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'companyHelper' => $this->companyHelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'submitOrderHelper' => $this->submitOrderHelperMock,
                'submitOrderOptimizedHelper' => $this->submitOrderOptimizedHelperMock,
                'logger' => $this->loggerMock,
                'regionFactory' => $this->regionFactoryMock,
                'curl' => $this->curlMock,
                'timezoneInterface' => $this->timezoneInterfaceMock,
                'enhancedProfile' => $this->enhancedProfileMock,
                'dataObjectFactory' => $this->dataObjectFactoryMock,
                'submitOrderDataArray' => $this->submitOrderDataArrayMock,
                'toggleConfig' => $this->toggleConfigMock,
                'httpClientFactory' => $this->laminasClientFactoryMock,
                'billingAddressBuilder' => $this->billingAddressBuilderMock,
                'inStoreRequestBuilder' => $this->inStoreRequestBuilderMock,
                'instoreConfig' => $this->inStoreConfigMock,
                'loggerHelperMock' => $this->loggerHelperMock,
                'newRelicHeaders' => $this->newRelicHeaders,
                'logHelperApiMock' => $this->logHelperApiMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetHeaders():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $gateWayToken = '';
        $tokenStr = ['token'=> ''];
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . $gateWayToken,
            "Cookie: Bearer=" . $tokenStr['token'],
            "X-On-Behalf-Of: 1"
        ];

        $this->submitOrderHelperMock->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn($headers);
        $this->assertEquals($headers, $this->apiHandlerMock->getHeaders($tokenStr));
    }

    /**
     * @return void
     */
    public function testGetHeadersFalse():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('');
        $gateWayToken = '';
        $tokenStr = ['token'=> ''];
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . $gateWayToken,
            "Cookie: Bearer=" . $tokenStr['token'],
            "X-On-Behalf-Of: 1"
        ];

        $this->submitOrderHelperMock->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn($headers);
        $this->assertEquals($headers, $this->apiHandlerMock->getHeaders($tokenStr));
    }
    /**
     * @return void
     */
    public function testGetHeadersCustomerSession():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $gateWayToken = '';
        $tokenStr = ['token'=> ''];
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . $gateWayToken,
            "Cookie: Bearer=" . $tokenStr['token']
        ];

        $this->submitOrderHelperMock->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn($headers);
        $this->assertEquals($headers, $this->apiHandlerMock->getHeaders($tokenStr));
    }

    /**
     * @return void
     */
    public function testGetHeadersCustomerSessionFalse():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('');
        $gateWayToken = '';
        $tokenStr = ['token'=> ''];
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "client_id: " . $gateWayToken,
            "Cookie: Bearer=" . $tokenStr['token']
        ];

        $this->submitOrderHelperMock->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn($headers);
        $this->assertEquals($headers, $this->apiHandlerMock->getHeaders($tokenStr));
    }

    /**
     * @return void
     */
    public function testPlaceOrderProcessing()
    {
        $quoteId = 123;
        $checkoutResponse = ['error' => 0, 'msg' => 'Success', 'response' => json_encode($this->checkoutResponse)];
        $boolCardAuthorizationStatus = 1;
        $retailTransectionId = 'bmw12345';
        $productLineDetailsAttributes = '{}';
        $rateQuoteResponse = [];
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->testFinalizeCheckoutResponse();

        $this->assertNotNull(
            $this->apiHandlerMock->placeOrderProcessing(
                $this->quoteMock,
                $checkoutResponse,
                $boolCardAuthorizationStatus,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * @return void
     */
    public function testPlaceOrderProcessingWithDifferentError()
    {
        $quoteId = 123;
        $boolCardAuthorizationStatus = 1;
        $retailTransectionId = 'bmw12345';
        $productLineDetailsAttributes = '{}';
        $rateQuoteResponse = [];
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->testFinalizeCheckoutResponse();

        $this->assertNotNull(
            $this->apiHandlerMock->placeOrderProcessing(
                $this->quoteMock,
                json_decode($this->checkoutResponse, true),
                $boolCardAuthorizationStatus,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * Test case with Exception
     */
    public function testPlaceOrderProcessingWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $quoteId = 123;
        $paymentData = (object) [
            "paymentMethod" => "instore",
            "nameOnCard" => "Yogesh",
            "year" => "2022",
            "expire" => "2028",
            "fedexAccountNumber" => "12345678",
            "encCCData" => "eyJxdW90ZUlkIjoiMDVkMTkwOWMtYzBmZC00OTI5LTgwZm",
            "isBillingAddress" => (Object) [
                "address" => "Home",
                "city" => "Plano",
                "zip" => "75024",
            ],
            "billingAddress" => (Object) [
                "address" => "Home",
                "addressTwo" => "Home",
                "city" => "Plano",
                "zip" => "75024",
                "state" => "TX",
            ],
        ];
        $checkoutResponse = ['error' => 0, 'msg' => 'Success', 'response' => json_encode($this->checkoutResponse)];
        $boolCardAuthorizationStatus = 1;
        $retailTransectionId = 'bmw12345';
        $productLineDetailsAttributes = '{}';
        $rateQuoteResponse = [];
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->testFinalizeCheckoutResponse();
        $this->submitOrderHelperMock->expects($this->any())->method('placeOrder')->willThrowException($exception);
        $this->quoteMock->expects($this->any())->method('getCustomerId')->willReturn(1);
        $this->quoteMock->expects($this->any())->method('getCustomerEmail')->willReturn('ayush.sood@infogain.com');
        $this->quoteMock->expects($this->any())->method('getPayment')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getData')->willReturn($paymentData);
        $this->quoteMock->expects($this->any())->method('getBillingAddress')->willReturn($this->addressMock);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->willReturn('test');
        $this->assertNotNull(
            $this->apiHandlerMock->placeOrderProcessing(
                $this->quoteMock,
                $checkoutResponse,
                $boolCardAuthorizationStatus,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * @return void
     */
    public function testFinalizeCheckoutResponse()
    {
        $quoteId = 123;
        $orderNumber = 123456;
        $shipmentId = 987654;
        $paymentData = (object) [
            "paymentMethod" => "instore",
            "nameOnCard" => "Yogesh",
            "year" => "2022",
            "expire" => "2028",
            "fedexAccountNumber" => "12345678",
            "encCCData" => "eyJxdW90ZUlkIjoiMDVkMTkwOWMtYzBmZC00OTI5LTgwZm",
            "isBillingAddress" => (Object) [
                "address" => "Home",
                "city" => "Plano",
                "zip" => "75024",
            ],
            "billingAddress" => (Object) [
                "address" => "Home",
                "addressTwo" => "Home",
                "city" => "Plano",
                "zip" => "75024",
                "state" => "TX",
            ],
        ];
        $checkoutResponse = ['error' => 0, 'msg' => 'Success', 'response' => json_encode($this->checkoutResponse)];
        $retailTransectionId = 'bmw12345';
        $productLineDetailsAttributes = '{}';
        $rateQuoteResponse = [];
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn($orderNumber);
        $this->dataObjectFactory->expects($this->any())->method('getShipmentId')->willReturn($shipmentId);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn($paymentData);
        $this->submitOrderHelperMock->expects($this->any())->method('isSetOrderId')->willReturn(true);

        $this->assertNotNull(
            $this->apiHandlerMock->finalizeCheckoutResponse(
                $this->quoteMock,
                $checkoutResponse,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * @return void
     */
    public function testFinalizeCheckoutResponseWithoutReserveOrderId()
    {
        $quoteId = 123;
        $orderNumber = 123456;
        $shipmentId = 987654;
        $paymentData = (object) [
            "paymentMethod" => "instore",
            "nameOnCard" => "Yogesh",
            "year" => "2022",
            "expire" => "2028",
            "fedexAccountNumber" => "12345678",
            "encCCData" => "eyJxdW90ZUlkIjoiMDVkMTkwOWMtYzBmZC00OTI5LTgwZm",
            "isBillingAddress" => (Object) [
                "address" => "Home",
                "city" => "Plano",
                "zip" => "75024",
            ],
            "billingAddress" => (Object) [
                "address" => "Home",
                "addressTwo" => "Home",
                "city" => "Plano",
                "zip" => "75024",
                "state" => "TX",
            ],
        ];
        $checkoutResponse = ['error' => 0, 'msg' => 'Success', 'response' => json_encode($this->checkoutResponse)];
        $retailTransectionId = 'bmw12345';
        $productLineDetailsAttributes = '{}';
        $rateQuoteResponse = [];
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn($orderNumber);
        $this->dataObjectFactory->expects($this->any())->method('getShipmentId')->willReturn($shipmentId);
        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn($paymentData);
        $this->submitOrderHelperMock->expects($this->any())->method('isSetOrderId')->willReturn(false);

        $this->assertNotNull(
            $this->apiHandlerMock->finalizeCheckoutResponse(
                $this->quoteMock,
                $checkoutResponse,
                $retailTransectionId,
                $productLineDetailsAttributes,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * Test case for validateCheckoutResponse
     */
    public function testValidateCheckoutResponse()
    {
        $rateQuoteResponse = [];
        $this->assertNotNull(
            $this->apiHandlerMock->validateCheckoutResponse(
                $this->quoteMock,
                $this->checkoutResponse,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * Test case for validateCheckoutResponse
     */
    public function testValidateCheckoutResponseWithResponse()
    {
        $checkoutResponse = '{
            "transactionId": "41e54f57-5e49-493b-911a-4d5fbce4e194",
            "output": {
              "checkout": {
                "transactionHeader": {
                  "guid": "707d1bfa-8011-4a7b-9438-73844d0e0445",
                  "type": "SALE",
                  "requestDateTime": "2023-02-14 02:36:35",
                  "transactionDateTime": "2023-02-14T10:43Z",
                  "retailTransactionId": "ADSKDCDF63EB65B404X",
                  "fedExCartId": "ebb87666-f564-483b-9534-ac16cf4a4ae1",
                  "rateQuoteId": "eyJxdW90ZUlkIjoiMzhmMmZlYmEtNjQwNy00NTg1LWI3NTYtZWRmMDQxNjk4YWNmIiwiY2FydElkIjoiZWJiODc2NjYtZjU2NC00ODNiLTk1MzQtYWMxNmNmNGE0YWUxIn0=",
                  "virtualTill": false
                },
                "lineItems": [
                  {
                    "type": "PRINT_PRODUCT",
                    "retailPrintOrderDetails": [
                      {
                        "customerNotificationEnabled": false,
                        "orderContact": {
                          "contact": {
                            "contactId": "1000010812",
                            "personName": {
                              "firstName": "Nidhi",
                              "lastName": "Singh"
                            },
                            "company": {
                              "name": "FXO"
                            },
                            "emailDetail": {
                              "emailAddress": "nidhi.singh@infogain.com"
                            },
                            "phoneNumberDetails": [
                              {
                                "phoneNumber": {
                                  "number": "9999999999"
                                },
                                "usage": "PRIMARY"
                              }
                            ]
                          }
                        },
                        "responsibleCenterDetail": [
                          {
                            "locationId": "ADSKD",
                            "address": {
                              "streetLines": [
                                "5901 Winthrop Street",
                                "Ste K-140"
                              ],
                              "city": "Plano",
                              "stateOrProvinceCode": "TX",
                              "postalCode": "75024-0101",
                              "countryCode": "US"
                            },
                            "emailDetail": {
                              "emailAddress": "usa5747@fedex.com"
                            },
                            "phoneNumberDetails": [
                              {
                                "phoneNumber": {
                                  "number": "972.324.3017"
                                }
                              }
                            ]
                          }
                        ],
                        "productLines": [
                          {
                            "instanceId": "0",
                            "productId": "1463680545590",
                            "unitQuantity": 50,
                            "unitOfMeasurement": "EACH",
                            "productRetailPrice": "76.00",
                            "productDiscountAmount": "0.0",
                            "productLinePrice": "76.00",
                            "productLineDetails": [
                              {
                                "instanceId": "1",
                                "detailCode": "40005",
                                "hasTermsAndConditions": true,
                                "description": "Full Pg Clr Flyr 50",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "76.00",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "76.000000",
                                "detailDiscountedUnitPrice": "76.000000"
                              }
                            ],
                            "name": "Fast Order Flyer",
                            "userProductName": "B-1491628-Code-Assertion-Report",
                            "type": "PRINT_PRODUCT",
                            "priceable": true
                          }
                        ],
                        "deliveryLines": [
                          {
                            "deliveryLineId": "9842",
                            "recipientReference": "9842",
                            "estimatedDeliveryLocalTime": "2023-02-14T17:00:00",
                            "deliveryLineType": "PICKUP",
                            "recipientContact": {
                              "personName": {
                                "firstName": "Nidhi",
                                "lastName": "Singh"
                              },
                              "company": {
                                "name": "FXO"
                              },
                              "emailDetail": {
                                "emailAddress": "nidhi.singh@infogain.com"
                              },
                              "phoneNumberDetails": [
                                {
                                  "phoneNumber": {
                                    "number": "9999999999"
                                  },
                                  "usage": "PRIMARY"
                                }
                              ]
                            },
                            "pickupDetails": {
                              "locationName": "5747",
                              "requestedPickupLocalTime": "2023-02-14T17:00:00"
                            },
                            "productAssociation": [
                              {
                                "productRef": "0",
                                "quantity": "50.0"
                              }
                            ]
                          }
                        ],
                        "orderTotalDiscountAmount": "0.0",
                        "orderGrossAmount": "76.00",
                        "orderNonTaxableAmount": "0.00",
                        "orderTaxExemptableAmount": "76.00",
                        "orderNetAmount": "76.00",
                        "orderTaxableAmount": "0.00",
                        "orderTaxAmount": "0.0",
                        "orderTotalAmount": "76.00",
                        "notificationRegistration": {
                          "webhook": {
                            "url": "https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/2010310392576573/status"
                          }
                        },
                        "retailCustomerId": "1000010812",
                        "origin": {
                          "orderNumber": "2010310392576573",
                          "orderClient": "MAGENTO",
                          "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                        }
                      }
                    ]
                  }
                ],
                "contact": {
                  "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                  },
                  "company": {
                    "name": "FXO"
                  },
                  "emailDetail": {
                    "emailAddress": "nidhi.singh@infogain.com"
                  },
                  "phoneNumberDetails": [
                    {
                      "phoneNumber": {
                        "number": "9999999999"
                      },
                      "usage": "PRIMARY"
                    }
                  ]
                },
                "tenders": [
                  {
                    "id": "1",
                    "paymentType": "CREDIT_CARD",
                    "requestedAmount": "76.0",
                    "tenderedAmount": "76.00",
                    "balanceDueAmount": "0.00",
                    "creditCard": {
                      "type": "VISA",
                      "maskedAccountNumber": "411111xxxxxx1111",
                      "authResponse": "APPROVED",
                      "accountLast4Digits": "xxxxxxxxxxxx1111"
                    },
                    "currency": "USD"
                  }
                ],
                "transactionTotals": {
                  "currency": "USD",
                  "grossAmount": "76.00",
                  "totalDiscountAmount": "0.0",
                  "netAmount": "76.00",
                  "taxAmount": "0.0",
                  "totalAmount": "76.00"
                }
              }
            }
          }';
        $rateQuoteResponse = [];
        $this->assertNotNull(
            $this->apiHandlerMock->validateCheckoutResponse(
                $this->quoteMock,
                $checkoutResponse,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * Test case for validateCheckoutResponse With Alert Case
     */
    public function testValidateCheckoutResponseWithAlertCase()
    {
        $rateQuoteResponse = [];
        $checkoutResponse = '{
            "transactionId": "41e54f57-5e49-493b-911a-4d5fbce4e194",
            "output": {
              "checkout": {
                "transactionHeader": {
                  "guid": "707d1bfa-8011-4a7b-9438-73844d0e0445",
                  "type": "SALE",
                  "requestDateTime": "2023-02-14 02:36:35",
                  "transactionDateTime": "2023-02-14T10:43Z",
                  "retailTransactionId": "ADSKDCDF63EB65B404X",
                  "fedExCartId": "ebb87666-f564-483b-9534-ac16cf4a4ae1",
                  "rateQuoteId": "eyJxdW90ZUlkIjoiMzhmMmZlYmEtNjQwNy00NTg1LWI3NTYtZWRmMDQxNjk4YWNmIiwiY2FydElkIjoiZWJiODc2NjYtZjU2NC00ODNiLTk1MzQtYWMxNmNmNGE0YWUxIn0=",
                  "virtualTill": false
                },
                "lineItems": [
                  {
                    "type": "PRINT_PRODUCT",
                    "retailPrintOrderDetails": [
                      {
                        "customerNotificationEnabled": false,
                        "orderContact": {
                          "contact": {
                            "contactId": "1000010812",
                            "personName": {
                              "firstName": "Nidhi",
                              "lastName": "Singh"
                            },
                            "company": {
                              "name": "FXO"
                            },
                            "emailDetail": {
                              "emailAddress": "nidhi.singh@infogain.com"
                            },
                            "phoneNumberDetails": [
                              {
                                "phoneNumber": {
                                  "number": "9999999999"
                                },
                                "usage": "PRIMARY"
                              }
                            ]
                          }
                        },
                        "responsibleCenterDetail": [
                          {
                            "locationId": "ADSKD",
                            "address": {
                              "streetLines": [
                                "5901 Winthrop Street",
                                "Ste K-140"
                              ],
                              "city": "Plano",
                              "stateOrProvinceCode": "TX",
                              "postalCode": "75024-0101",
                              "countryCode": "US"
                            },
                            "emailDetail": {
                              "emailAddress": "usa5747@fedex.com"
                            },
                            "phoneNumberDetails": [
                              {
                                "phoneNumber": {
                                  "number": "972.324.3017"
                                }
                              }
                            ]
                          }
                        ],
                        "productLines": [
                          {
                            "instanceId": "0",
                            "productId": "1463680545590",
                            "unitQuantity": 50,
                            "unitOfMeasurement": "EACH",
                            "productRetailPrice": "76.00",
                            "productDiscountAmount": "0.0",
                            "productLinePrice": "76.00",
                            "productLineDetails": [
                              {
                                "instanceId": "1",
                                "detailCode": "40005",
                                "hasTermsAndConditions": true,
                                "description": "Full Pg Clr Flyr 50",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "76.00",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "76.000000",
                                "detailDiscountedUnitPrice": "76.000000"
                              }
                            ],
                            "name": "Fast Order Flyer",
                            "userProductName": "B-1491628-Code-Assertion-Report",
                            "type": "PRINT_PRODUCT",
                            "priceable": true
                          }
                        ],
                        "deliveryLines": [
                          {
                            "deliveryLineId": "9842",
                            "recipientReference": "9842",
                            "estimatedDeliveryLocalTime": "2023-02-14T17:00:00",
                            "deliveryLineType": "PICKUP",
                            "recipientContact": {
                              "personName": {
                                "firstName": "Nidhi",
                                "lastName": "Singh"
                              },
                              "company": {
                                "name": "FXO"
                              },
                              "emailDetail": {
                                "emailAddress": "nidhi.singh@infogain.com"
                              },
                              "phoneNumberDetails": [
                                {
                                  "phoneNumber": {
                                    "number": "9999999999"
                                  },
                                  "usage": "PRIMARY"
                                }
                              ]
                            },
                            "pickupDetails": {
                              "locationName": "5747",
                              "requestedPickupLocalTime": "2023-02-14T17:00:00"
                            },
                            "productAssociation": [
                              {
                                "productRef": "0",
                                "quantity": "50.0"
                              }
                            ]
                          }
                        ],
                        "orderTotalDiscountAmount": "0.0",
                        "orderGrossAmount": "76.00",
                        "orderNonTaxableAmount": "0.00",
                        "orderTaxExemptableAmount": "76.00",
                        "orderNetAmount": "76.00",
                        "orderTaxableAmount": "0.00",
                        "orderTaxAmount": "0.0",
                        "orderTotalAmount": "76.00",
                        "notificationRegistration": {
                          "webhook": {
                            "url": "https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/2010310392576573/status"
                          }
                        },
                        "retailCustomerId": "1000010812",
                        "origin": {
                          "orderNumber": "2010310392576573",
                          "orderClient": "MAGENTO",
                          "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                        }
                      }
                    ]
                  }
                ],
                "contact": {
                  "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                  },
                  "company": {
                    "name": "FXO"
                  },
                  "emailDetail": {
                    "emailAddress": "nidhi.singh@infogain.com"
                  },
                  "phoneNumberDetails": [
                    {
                      "phoneNumber": {
                        "number": "9999999999"
                      },
                      "usage": "PRIMARY"
                    }
                  ]
                },
                "tenders": [
                  {
                    "id": "1",
                    "paymentType": "CREDIT_CARD",
                    "requestedAmount": "76.0",
                    "tenderedAmount": "76.00",
                    "balanceDueAmount": "0.00",
                    "creditCard": {
                      "type": "VISA",
                      "maskedAccountNumber": "411111xxxxxx1111",
                      "authResponse": "APPROVED",
                      "accountLast4Digits": "xxxxxxxxxxxx1111"
                    },
                    "currency": "USD"
                  }
                ],
                "transactionTotals": {
                  "currency": "USD",
                  "grossAmount": "76.00",
                  "totalDiscountAmount": "0.0",
                  "netAmount": "76.00",
                  "taxAmount": "0.0",
                  "totalAmount": "76.00"
                }
              }
            }
          }';
        $this->assertNotNull(
            $this->apiHandlerMock->validateCheckoutResponse(
                $this->quoteMock,
                json_decode($checkoutResponse, true),
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * Test case for validateCheckoutResponse With Alert Case
     */
    public function testValidateCheckoutResponseWithNull()
    {
        $rateQuoteResponse = [];
        $this->assertNotNull(
            $this->apiHandlerMock->validateCheckoutResponse(
                $this->quoteMock,
                null,
                $this->dataObjectFactory,
                $rateQuoteResponse
            )
        );
    }

    /**
     * Test case for callTransactionApiClientRequest
     */
    public function testCallTransactionApiClientRequest()
    {
        $this->laminasClientFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(500);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn("{}");
        $this->testGetHeaders();
        $this->assertNotNull(
            $this->apiHandlerMock->callTransactionApiClientRequest(json_encode($this->apiData))
        );
    }

    /**
     * Test case for callTransactionApiClientRequestWithStatus
     */
    public function testCallTransactionApiClientRequestWithStatus400()
    {
        $this->laminasClientFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(400);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn("{}");

        $this->testGetHeaders();
        $this->assertNotNull(
            $this->apiHandlerMock->callTransactionApiClientRequest(json_encode($this->apiData))
        );
    }

    /**
     * Test case for callTransactionApiClientRequestWithStatus200
     */
    public function testCallTransactionApiClientRequestWithStatus200()
    {
        $this->laminasClientFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(200);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn("{}");

        $this->testGetHeaders();
        $this->assertNotNull(
            $this->apiHandlerMock->callTransactionApiClientRequest(json_encode($this->apiData))
        );
    }

    /**
     * Test case for callTransactionApiClientRequestWithException
     */
    public function testCallTransactionApiClientRequestWithException()
    {
        $this->testGetHeaders();
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(200);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn('{}');
        $this->assertNotNull(
            $this->apiHandlerMock->callTransactionApiClientRequest(json_encode($this->apiData))
        );
    }

    /**
     * Test case for callCurlPost
     * @return void
     */
    public function testCallCurlPost():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('is_optimize_configuration')
            ->willReturn(true);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::GENERAL_TRANSACTION_POST_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->laminasClientFactoryMock->method('create')->willReturn($this->httpClientMock);
        $this->assertEquals(null, $this->apiHandlerMock->callCurlPost('', 'transaction'));
    }

    /**
     * @return void
     */
    public function testCallCurlPostFalse():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('is_optimize_configuration')
            ->willReturn(false);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::TRANSACTION_POST_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->laminasClientFactoryMock->method('create')->willReturn($this->httpClientMock);
        $this->assertEquals(null, $this->apiHandlerMock->callCurlPost('', 'transaction'));
    }

    /**
     * @return void
     */
    public function testCallCurlPostRate():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('is_optimize_configuration')
            ->willReturn(true);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::RATE_POST_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->laminasClientFactoryMock->method('create')->willReturn($this->httpClientMock);
        $this->assertEquals(null, $this->apiHandlerMock->callCurlPost('', 'rate'));
    }

    /**
     * @return void
     */
    public function testCallCurlPostRateFalse():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('is_optimize_configuration')
            ->willReturn(false);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::RATE_QUOTE_POST_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->assertEquals(null, $this->apiHandlerMock->callCurlPost('', 'rate'));
    }

    /**
     * @return void
     */
    public function testCallCurlPostWithSearch():void
    {
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->with('is_optimize_configuration')
            ->willReturn(false);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::TRANSACTION_SEARCH_POST_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->assertEquals(null, $this->apiHandlerMock->callCurlPost('', 'search'));
    }

    /**
     * Test case for getPaymentDetails
     */
    public function testGetPaymentDetailsWithCCPaymentMethod()
    {
        $paymentMethod = 'cc';
        $useSiteCreditCard = 1;
        $encCCData = null;
        $nameOnCard = "Braj Mohan";
        $paymentData = (object) [
            "profileCreditCardId" => "q6y0PKLOS4wYR"
        ];
        $shippingAccount = '653243286';
        $isPickup = true;
        $shipMethod = "fedexshipping_LOCAL_DELIVERY_AM";
        $creditCardData = ['token' => "13442323", 'data' => ["test"]];
        $creditCardDetails = ['ccToken' => "13442323", 'nameOnCard' => "Braj Mohan"];
        $data = (object) [
            "output" => (object) [
                "creditCard" => (object) [
                    "creditCardToken" => "13442323",
                    "cardHolderName" => "Braj Mohan"
                ]
            ]
        ];

        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCompanyCreditCardData')
            ->willReturn($creditCardData);

        $this->enhancedProfileMock->expects($this->any())->method('updateCreditCard')->willReturn($data);
        $this->billingAddressBuilderMock->expects($this->any())->method('getUpdatedCreditCardDetail')
            ->willReturn($creditCardDetails);

        $result = $this->apiHandlerMock->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
        );
        $this->assertNotNull($result);
        $this->assertEquals([
            'ccToken'       => '13442323',
            'nameOnCard'    => 'Braj Mohan',
            'encCCData'     => null,
            'condition'     => 0
        ], $result);
    }

    /**
     * Test case for getPaymentDetails
     */
    public function testGetPaymentDetailsWithCCPaymentMethodAndShippingLocalDelivery()
    {
        $paymentMethod = 'cc';
        $useSiteCreditCard = 1;
        $encCCData = null;
        $nameOnCard = "Braj Mohan";
        $paymentData = (object) [
            "profileCreditCardId" => "q6y0PKLOS4wYR"
        ];
        $shippingAccount = '653243286';
        $isPickup = false;
        $shipMethod = "fedexshipping_LOCAL_DELIVERY_AM";
        $creditCardData = ['token' => "13442323", 'data' => ["test"]];
        $creditCardDetails = ['ccToken' => "13442323", 'nameOnCard' => "Braj Mohan"];
        $data = (object) [
            "output" => (object) [
                "creditCard" => (object) [
                    "creditCardToken" => "13442323",
                    "cardHolderName" => "Braj Mohan"
                ]
            ]
        ];

        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCompanyCreditCardData')
            ->willReturn($creditCardData);

        $this->enhancedProfileMock->expects($this->any())->method('updateCreditCard')->willReturn($data);
        $this->billingAddressBuilderMock->expects($this->any())->method('getUpdatedCreditCardDetail')
            ->willReturn($creditCardDetails);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_D209388_ePro_order_fix')
            ->willReturn(true);

        $result = $this->apiHandlerMock->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
        );
        $this->assertNotNull($result);
        $this->assertEquals([
            'ccToken'       => '13442323',
            'nameOnCard'    => 'Braj Mohan',
            'encCCData'     => null,
            'condition'     => 0
        ], $result);
    }

    /**
     * Test case for getPaymentDetails
     */
    public function testGetPaymentDetailsWithCCPaymentMethodAndShippingGroundUs()
    {
        $paymentMethod = 'cc';
        $useSiteCreditCard = 1;
        $encCCData = null;
        $nameOnCard = "Braj Mohan";
        $paymentData = (object) [
            "profileCreditCardId" => "q6y0PKLOS4wYR"
        ];
        $shippingAccount = '653243286';
        $isPickup = false;
        $shipMethod = "fedexshipping_GROUND_US";
        $creditCardData = ['token' => "13442323", 'data' => ["test"]];
        $creditCardDetails = ['ccToken' => "13442323", 'nameOnCard' => "Braj Mohan"];
        $data = (object) [
            "output" => (object) [
                "creditCard" => (object) [
                    "creditCardToken" => "13442323",
                    "cardHolderName" => "Braj Mohan"
                ]
            ]
        ];

        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCompanyCreditCardData')
            ->willReturn($creditCardData);

        $this->enhancedProfileMock->expects($this->any())->method('updateCreditCard')->willReturn($data);
        $this->billingAddressBuilderMock->expects($this->any())->method('getUpdatedCreditCardDetail')
            ->willReturn($creditCardDetails);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_D209388_ePro_order_fix')
            ->willReturn(true);

        $result = $this->apiHandlerMock->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
        );
        $this->assertNotNull($result);
        $this->assertEquals([
            'ccToken'       => '13442323',
            'nameOnCard'    => 'Braj Mohan',
            'encCCData'     => null,
            'condition'     => true
        ], $result);
    }

    /**
     * @return void
     */
    public function testGetPaymentDetailsWithFedexPaymentMethod()
    {
        $paymentMethod = 'fedex';
        $useSiteCreditCard = 1;
        $encCCData = null;
        $nameOnCard = "Braj Mohan";
        $paymentData = (object) [
            "profileCreditCardId" => "q6y0PKLOS4wYR"
        ];
        $shippingAccount = '653243286';
        $isPickup = true;
        $shipMethod = "fedexshipping_LOCAL_DELIVERY_PM";

        $result = $this->apiHandlerMock->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
        );
        $this->assertNotNull($result);
        $this->assertEquals([
            'ccToken'       => null,
            'nameOnCard'    => 'Braj Mohan',
            'encCCData'     => null,
            'condition'     => false
        ], $result);
    }

    /**
     * @return void
     */
    public function testGetPaymentDetailsWithFedexPaymentMethodAndShippingLocalDelivery()
    {
        $paymentMethod = 'fedex';
        $useSiteCreditCard = 1;
        $encCCData = null;
        $nameOnCard = "Braj Mohan";
        $paymentData = (object) [
            "profileCreditCardId" => "q6y0PKLOS4wYR"
        ];
        $shippingAccount = '653243286';
        $isPickup = false;
        $shipMethod = "fedexshipping_LOCAL_DELIVERY_PM";

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_D209388_ePro_order_fix')
            ->willReturn(true);

        $result = $this->apiHandlerMock->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
        );
        $this->assertNotNull($result);
        $this->assertEquals([
            'ccToken'       => null,
            'nameOnCard'    => 'Braj Mohan',
            'encCCData'     => null,
            'condition'     => false
        ], $result);
    }

    /**
     * @return void
     */
    public function testGetPaymentDetailsWithFedexPaymentMethodAndShippingGroundUs()
    {
        $paymentMethod = 'fedex';
        $useSiteCreditCard = 1;
        $encCCData = null;
        $nameOnCard = "Braj Mohan";
        $paymentData = (object) [
            "profileCreditCardId" => "q6y0PKLOS4wYR"
        ];
        $shippingAccount = '653243286';
        $isPickup = false;
        $shipMethod = "fedexshipping_GROUND_US";

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('mazegeeks_D209388_ePro_order_fix')
            ->willReturn(true);

        $result = $this->apiHandlerMock->getPaymentDetails(
            $paymentMethod,
            $useSiteCreditCard,
            $encCCData,
            $nameOnCard,
            $paymentData,
            $shippingAccount,
            $isPickup,
            $shipMethod,
        );
        $this->assertNotNull($result);
        $this->assertEquals([
            'ccToken'       => null,
            'nameOnCard'    => 'Braj Mohan',
            'encCCData'     => null,
            'condition'     => 1
        ], $result);
    }

    /**
     * Test case for getRequestedAmounts
     */
    public function testGetRequestedAmounts()
    {
        $shippingAccount = '653243286';
        $rateQuoteResponse = [
            'output' => [
                'rateQuote'=> [
                    'rateQuoteDetails' => [
                        0 => "test data"
                    ]
                ]
            ]
        ];

        $numTotal = 12;
        $numDiscountPrice = 5;

        $this->assertNotNull(
            $this->apiHandlerMock->getRequestedAmounts(
                $shippingAccount,
                $rateQuoteResponse,
                $numTotal,
                $numDiscountPrice
            )
        );
    }

    /**
     * @return void
     */
    public function testGetRequestedAmountsWithElse()
    {
        $shippingAccount = null;
        $rateQuoteResponse = [
            'output' => [
                'rateQuote'=> [
                    'rateQuoteDetails' => [
                        0 => "test data"
                    ]
                ]
            ]
        ];

        $numTotal = 12;
        $numDiscountPrice = 5;

        $this->assertNotNull(
            $this->apiHandlerMock->getRequestedAmounts(
                $shippingAccount,
                $rateQuoteResponse,
                $numTotal,
                $numDiscountPrice
            )
        );
    }

    /**
     * Test case for getCustomerAddressInformation
     */
    public function testGetCustomerAddressInformation()
    {
        $paymentData = [
            'isBillingAddress' => true,
            'billingAddress' => [
                "address" => "Home",
                "addressTwo" => null,
                "city" => "Plano",
                "zip" => self::POSTAL_CODE,
                "state" => "TX"
            ]
        ];
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->with('street')->willReturn(self::STREET_ADDRESS);
        $this->addressMock->expects($this->any())->method('getData')->with('city')->willReturn('Plano');
        $this->addressMock->expects($this->any())->method('getData')->with('region_id')->willReturn('TX');
        $this->addressMock->expects($this->any())->method('getData')->with('postcode')->willReturn(self::POSTAL_CODE);

        $this->assertNotNull(
            $this->apiHandlerMock->getCustomerAddressInformation(
                true,
                'cc',
                json_decode(json_encode($paymentData)),
                $this->quoteMock
            )
        );
    }

    /**
     * Test case for getCustomerAddressInformation
     * IsPick => false
     */
    public function testGetCustomerAddressInformationWithFalsepickup()
    {
        $paymentData = [
            'isBillingAddress' => true,
            'billingAddress' => [
                "address" => "Home",
                "addressTwo" =>"home",
                "city" => "Plano",
                "zip" => self::POSTAL_CODE,
                "state" => "TX"
            ]
        ];

        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->with('street')->willReturn(self::STREET_ADDRESS);
        $this->addressMock->expects($this->any())->method('getData')->with('city')->willReturn('Plano');
        $this->addressMock->expects($this->any())->method('getData')->with('region_id')->willReturn('TX');
        $this->addressMock->expects($this->any())->method('getData')->with('postcode')->willReturn(self::POSTAL_CODE);

        $this->assertNotNull(
            $this->apiHandlerMock->getCustomerAddressInformation(
                false,
                'cc',
                json_decode(json_encode($paymentData)),
                $this->quoteMock
            )
        );
    }

    /**
     * Test case for getCustomerAddressInformation
     * IsPick => false
     * Pay => fedex
     */
    public function testGetCustomerAddressInformationWithFalsepickupPayFedEx()
    {
        $paymentData = [
            'isBillingAddress' => true,
            'billingAddress' => [
                "address" => "Home",
                "addressTwo" =>"home",
                "city" => "Plano",
                "zip" => self::POSTAL_CODE,
                "state" => "TX"
            ]
        ];
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->will($this->onConsecutiveCalls(
            self::STREET_ADDRESS,
            'Plano',
            'TX',
            self::POSTAL_CODE
        ));
        $this->assertNotNull(
            $this->apiHandlerMock->getCustomerAddressInformation(
                false,
                'fedex',
                json_decode(json_encode($paymentData)),
                $this->quoteMock
            )
        );
    }

    /**
     * Test case for getCustomerAddressInformation
     * IsPick => false
     * Pay => cc
     * IsBillable => False
     */
    public function testGetCustomerAddressInformationWithNotBillableAddress()
    {
        $paymentData = [
            'isBillingAddress' => false,
            'billingAddress' => [
                "address" => "Home",
                "addressTwo" =>"home",
                "city" => "Plano",
                "zip" => self::POSTAL_CODE,
                "state" => ""
            ]
        ];

        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->addressMock->expects($this->any())->method('getData')->will($this->onConsecutiveCalls(
            self::STREET_ADDRESS,
            'Plano',
            '',
            self::POSTAL_CODE
        ));
        $this->assertNotNull(
            $this->apiHandlerMock->getCustomerAddressInformation(
                false,
                'cc',
                json_decode(json_encode($paymentData)),
                $this->quoteMock
            )
        );
    }

    /**
     * Test case for isTransactionTimeout
     */
    public function testIsTransactionTimeout()
    {
        $transactionResponseData = [
            "errors" => [
                [
                    "code" => "TIMEOUT",
                    "message" => "Internal Server Timeout"
                ]
            ]
        ];
        $output = '{}';
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('unsetOrderInProgress')->willReturnSelf();
        $this->assertNotNull(
            $this->apiHandlerMock->isTransactionTimeout(
                $transactionResponseData,
                $output,
                123
            )
        );
    }

    /**
     * @return void
     */
    public function testIsTransactionTimeoutWithoutTimeout()
    {
        $transactionResponseData = [
            "errors" => [
                [
                    "code" => "error",
                    "message" => "Transaction CXS API Failed"
                ]
            ]
        ];
        $output = '{}';
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('unsetOrderInProgress')->willReturnSelf();
        $this->assertNotNull(
            $this->apiHandlerMock->isTransactionTimeout(
                $transactionResponseData,
                $output,
                123
            )
        );
    }

    /**
     * Test case for callTransactionAPI
     */
    public function testCallTransactionAPI()
    {
        $quoteId = 123;

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
        ->willReturn(true);

        $this->inStoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(false);

        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('10');
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->testCallTransactionApiClientRequestWithException();
        $this->apiHandlerMock->callTransactionAPI($this->apiData, $quoteId);
    }

    /**
     * Test case for callTransactionAPI
     */
    public function testCallTransactionAPIWithToggleOnWithNoError()
    {
        $quoteId = 123;
        $result = [
            'output' =>[
                'rateQuote'=> [
                    'currency' => 'USD',
                    'rateQuoteDetails' =>[
                        0=>[
                            'grossAmount' => 56.79,
                            'totalDiscountAmount' => 0,
                            'netAmount' => 56.79,
                            'taxableAmount' => 56.79,
                            'taxAmount' => 2.89,
                            'totalAmount' => 59.68,
                            'estimatedVsActual' => 'ACTUAL',
                            'productLines' => [
                                '0' =>[
                                    'instanceId' => '0',
                                    'productId' => '1463680545590',
                                    'unitQuantity' => '50',
                                    'priceable' => '1',
                                    'unitOfMeasurement'=> 'EACH',
                                    'productRetailPrice' => '34.99',
                                    'productDiscountAmount' => '0',
                                    'productLinePrice' => '34.99',
                                    'productLineDetails' => [
                                        '0' => ['detailCode' => '40005',
                                            'priceRequired' => null,
                                            'priceOverridable' => null,
                                            'description' => 'Full Pg Clr Flyr 50',
                                            'unitQuantity' => '1',
                                            'quantity' => '1',
                                            'detailPrice' => 34.99,
                                            'detailDiscountPrice' => '0',
                                            'detailUnitPrice' => '34.99',
                                            'detailDiscountedUnitPrice' => '0',
                                            'detailCategory' => 'PRINTING'
                                        ]],
                                    'name' => 'Fast Order Flyer',
                                    'userProductName' => 'Fast Order Flyer',
                                    'type' => 'PRINT_ORDER'
                                ]],
                            'deliveryLines' => [
                                '0' =>['recipientReference' => '1',
                                    'priceable' => '1',
                                    'deliveryLinePrice' => '0',
                                    'deliveryRetailPrice' => '0',
                                    'deliveryLineType' => 'PACKING_AND_HANDLING',
                                    'deliveryDiscountAmount' => '0'],
                                '1' => [
                                    'recipientReference' => '1',
                                    'estimatedDeliveryLocalTime' => '2021-12-23T16:30:00',
                                    'estimatedShipDate' => '2021-12-21',
                                    'priceable' => false,
                                    'deliveryLinePrice' => 21.8,
                                    'deliveryRetailPrice' => 21.8,
                                    'deliveryLineType' => 'SHIPPING',
                                    'deliveryDiscountAmount' => 0,
                                    'recipientContact' => [
                                        'personName' => [
                                            'firstName' => 'Attri',
                                            'lastName' => 'Kumar'
                                        ],

                                        'company' => [ 'name' => 'FXO'] ,
                                        'emailDetail' => ['emailAddress' => 'attri.kumar@infogain.com' ],
                                        'phoneNumberDetails' => [
                                            0 => ['phoneNumber' => ['number' => '9354554555'],'usage' => 'PRIMARY'
                                            ]
                                        ]
                                    ],
                                    'shipmentDetails' => [
                                        'address' =>
                                            [
                                                'streetLines' =>
                                                    ['0' => '234','1' => null],
                                                'city' => 'plano',
                                                'stateOrProvinceCode' => '75024',
                                                'postalCode' => '75024',
                                                'countryCode' => 'US'
                                            ]

                                    ],
                                ]],
                            'rateQuoteId' => 'bmw123'
                        ],
                    ],
                ],
                'alerts' => [],
                'checkout'=>[
                    'transactionHeader' =>
                        ['retailTransactionId'=> 'yogesh2'],
                    'lineItems'=>[
                        0 =>[ 'retailPrintOrderDetails'
                        =>
                            [ 0=>
                                ['productLines'=>'test lines']]
                        ]
                    ]
                ]
            ]
        ];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->inStoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(false);

        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('10');
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(500);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn(json_encode($result));
        $this->apiHandlerMock->callTransactionAPI($this->apiData, $quoteId);
    }

    public function testHandleInstoreTransactionAPI() {
        $result = [
            'output' =>[
                'rateQuote'=> [
                    'currency' => 'USD',
                    'rateQuoteDetails' =>[]
                ],
                'alerts' => [],
                'checkout'=>[
                    'transactionHeader' =>
                        ['retailTransactionId'=> 'yogesh2'],
                        ['orderReferences' =>
                            [
                                'name' => "FUSE",
                                'value' => '1234567890'
                            ]
                        ],
                    'lineItems'=>[
                        0 =>[ 'retailPrintOrderDetails'
                        =>
                            [ 0=>
                                ['productLines'=>'test lines']]
                        ]
                    ]
                ]
            ]
        ];

        $fjmpRateQuoteId = 'eyJxdW90ZUlkIjoiZTg1MDViNWMtYWZkYi00MTA2LThlMmItMGM0NThkNjVj';
        $quoteId = '4057';
        $rateQuoteResponse = [];

        $this->dataObjectForFujitsu->expects($this->once())
            ->method('getOrderNumber')
            ->willReturn('1234567890');
        $this->inStoreRequestBuilderMock->expects($this->any())
            ->method('build')
            ->willReturn($this->apiData);
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnMap([
                ['explorers_D179263_fix', false],
                ['explorers_toas_mapping_redesign', true],
            ]);
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('10');
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(500);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn(json_encode($result));
        $this->inStoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->apiHandlerMock->handleInstoreTransactionAPI($fjmpRateQuoteId, $quoteId, $rateQuoteResponse, $this->dataObjectForFujitsu);
    }

    public function testHandleInstoreTransactionAPIWithError() {
        $result = [
            'output' => [
            ],
            'errors' => [
                0 => [
                    "code" => "EXAMPLE CODE",
                    "message" => "EXAMPLE MESSAGE"
                ]
            ]
        ];

        $fjmpRateQuoteId = 'eyJxdW90ZUlkIjoiZTg1MDViNWMtYWZkYi00MTA2LThlMmItMGM0NThkNjVj';
        $quoteId = '4057';
        $rateQuoteResponse = [];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('10');
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('post')->willReturn(null);
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(['token'=> 'api:12']);
        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setParameterPost')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(500);
        $this->responseClientMock->expects($this->any())->method('getReasonPhrase')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getMessage')->willReturn("Test");
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn(json_encode($result));
        $this->inStoreConfigMock->expects($this->any())
            ->method('isCheckoutRetryImprovementEnabled')
            ->willReturn(true);
        $this->inStoreConfigMock->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectExceptionMessage('Transaction CXS API Response Error: EXAMPLE MESSAGE');

        $this->apiHandlerMock->handleInstoreTransactionAPI($fjmpRateQuoteId, $quoteId, $rateQuoteResponse, $this->dataObjectForFujitsu);
    }

    public function testCallTransactionAPIUnsetData()
    {
        $quoteId = 123;
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('10');
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->testCallTransactionApiClientRequestWithException();
        $this->apiHandlerMock->callTransactionAPI($this->apiData, $quoteId);
    }

    /**
     * @return void
     */
    public function testGetCheckoutResponseDataWithInStorePaymentMethod()
    {
        $estimatePickupTime = '00:00:00';
        $quoteId = 123;
        $fjmpRateQuoteId = "bmw123";
        $orderNumber = 123456;
        $paymentData = (object) [
            "paymentMethod" => "instore",
            "nameOnCard" => "Yogesh",
            "year" => "2022",
            "expire" => "2028",
            "fedexAccountNumber" => "12345678",
            "encCCData" => "eyJxdW90ZUlkIjoiMDVkMTkwOWMtYzBmZC00OTI5LTgwZm",
            "isBillingAddress" => (Object) [
                "address" => "Home",
                "city" => "Plano",
                "zip" => "75024",
            ],
            "billingAddress" => (Object) [
                "address" => "Home",
                "addressTwo" => "Home",
                "city" => "Plano",
                "zip" => "75024",
                "state" => "TX",
            ],
        ];

        $result = [
            'output' =>[
                'rateQuote'=> [
                    'currency' => 'USD',
                    'rateQuoteDetails' =>[
                        0=>[
                            'grossAmount' => 56.79,
                            'totalDiscountAmount' => 0,
                            'netAmount' => 56.79,
                            'taxableAmount' => 56.79,
                            'taxAmount' => 2.89,
                            'totalAmount' => 59.68,
                            'estimatedVsActual' => 'ACTUAL',
                            'productLines' => [
                                '0' =>[
                                    'instanceId' => '0',
                                    'productId' => '1463680545590',
                                    'unitQuantity' => '50',
                                    'priceable' => '1',
                                    'unitOfMeasurement'=> 'EACH',
                                    'productRetailPrice' => '34.99',
                                    'productDiscountAmount' => '0',
                                    'productLinePrice' => '34.99',
                                    'productLineDetails' => [
                                        '0' => ['detailCode' => '40005',
                                            'priceRequired' => null,
                                            'priceOverridable' => null,
                                            'description' => 'Full Pg Clr Flyr 50',
                                            'unitQuantity' => '1',
                                            'quantity' => '1',
                                            'detailPrice' => 34.99,
                                            'detailDiscountPrice' => '0',
                                            'detailUnitPrice' => '34.99',
                                            'detailDiscountedUnitPrice' => '0',
                                            'detailCategory' => 'PRINTING'
                                        ]],
                                    'name' => 'Fast Order Flyer',
                                    'userProductName' => 'Fast Order Flyer',
                                    'type' => 'PRINT_ORDER'
                                ]],
                            'deliveryLines' => [
                                '0' =>['recipientReference' => '1',
                                    'priceable' => '1',
                                    'deliveryLinePrice' => '0',
                                    'deliveryRetailPrice' => '0',
                                    'deliveryLineType' => 'PACKING_AND_HANDLING',
                                    'deliveryDiscountAmount' => '0'],
                                '1' => [
                                    'recipientReference' => '1',
                                    'estimatedDeliveryLocalTime' => '2021-12-23T16:30:00',
                                    'estimatedShipDate' => '2021-12-21',
                                    'priceable' => false,
                                    'deliveryLinePrice' => 21.8,
                                    'deliveryRetailPrice' => 21.8,
                                    'deliveryLineType' => 'SHIPPING',
                                    'deliveryDiscountAmount' => 0,
                                    'recipientContact' => [
                                        'personName' => [
                                            'firstName' => 'Attri',
                                            'lastName' => 'Kumar'
                                        ],

                                        'company' => [ 'name' => 'FXO'] ,
                                        'emailDetail' => ['emailAddress' => 'attri.kumar@infogain.com' ],
                                        'phoneNumberDetails' => [
                                            0 => ['phoneNumber' => ['number' => '9354554555'],'usage' => 'PRIMARY'
                                            ]
                                        ]
                                    ],
                                    'shipmentDetails' => [
                                        'address' =>
                                            [
                                                'streetLines' =>
                                                    ['0' => '234','1' => null],
                                                'city' => 'plano',
                                                'stateOrProvinceCode' => '75024',
                                                'postalCode' => '75024',
                                                'countryCode' => 'US'
                                            ]

                                    ],
                                ]],
                            'rateQuoteId' => 'bmw123'
                        ],
                    ],
                ],
                'alerts' => [],
                'checkout'=>[
                    'transactionHeader' =>
                        ['retailTransactionId'=> 'yogesh2'],
                    'lineItems'=>[
                        0 =>[ 'retailPrintOrderDetails'
                        =>
                            [ 0=>
                                ['productLines'=>'test lines']]
                        ]
                    ]
                ]
            ]
        ];

        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn($paymentData);
        $this->dataObjectFactory->expects($this->any())->method('getEstimatePickupTime')
            ->willReturn($estimatePickupTime);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn($orderNumber);
        $this->submitOrderHelperMock->expects($this->any())->method('getRateQuoteId')->willReturn('bmw123');

        $this->quoteRepositoryMock->expects($this->any())->method('getActive')->with($quoteId)
            ->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->inStoreRequestBuilderMock->expects($this->any())->method('build')->with($fjmpRateQuoteId)
            ->willReturn($this->apiData);
        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->configInterfaceMock->expects($this->any())->method('getValue')->willReturn('https://www.staging3.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');

        $this->laminasClientFactoryMock->method('create')->willReturn($this->httpClientMock);

        $this->httpClientMock->expects($this->any())->method('setOptions')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setMethod')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('setRawBody')->willReturnSelf();
        $this->httpClientMock->expects($this->any())->method('send')->willReturn($this->responseClientMock);
        $this->responseClientMock->expects($this->any())->method('getStatusCode')->willReturn(200);
        $this->responseClientMock->expects($this->any())->method('getBody')->willReturn("{}");

        $this->apiHandlerMock->getCheckoutResponseData($this->dataObjectFactory, $result);
    }

    /**
     * @return void
     */
    public function testGetCheckoutResponseDataWithInStorePaymentMethodEmptyResponse()
    {
        $estimatePickupTime = '00:00:00';
        $quoteId = 123;
        $orderNumber = 123456;
        $paymentData = (object) [
            "paymentMethod" => "instore",
            "nameOnCard" => "Yogesh",
            "year" => "2022",
            "expire" => "2028",
            "fedexAccountNumber" => "12345678",
            "encCCData" => "eyJxdW90ZUlkIjoiMDVkMTkwOWMtYzBmZC00OTI5LTgwZm",
            "isBillingAddress" => (Object) [
                "address" => "Home",
                "city" => "Plano",
                "zip" => "75024",
            ],
            "billingAddress" => (Object) [
                "address" => "Home",
                "addressTwo" => "Home",
                "city" => "Plano",
                "zip" => "75024",
                "state" => "TX",
            ],
        ];

        $result = [
            'errors' => ['test']
        ];

        $this->dataObjectFactory->expects($this->any())->method('getPaymentData')->willReturn($paymentData);
        $this->dataObjectFactory->expects($this->any())->method('getEstimatePickupTime')
            ->willReturn($estimatePickupTime);
        $this->dataObjectFactory->expects($this->any())->method('getQuoteId')->willReturn($quoteId);
        $this->dataObjectFactory->expects($this->any())->method('getOrderNumber')->willReturn($orderNumber);
        $this->apiHandlerMock->getCheckoutResponseData($this->dataObjectFactory, $result);
    }

    /**
     * Test case for getTransactionIdAndProductLinesAttributes
     */
    public function testgetTransactionIdAndProductLinesAttributes()
    {
        $checkoutResponse = '{
            "transactionId": "41e54f57-5e49-493b-911a-4d5fbce4e194",
            "output": {
              "checkout": {
                "transactionHeader": {
                  "guid": "707d1bfa-8011-4a7b-9438-73844d0e0445",
                  "type": "SALE",
                  "requestDateTime": "2023-02-14 02:36:35",
                  "transactionDateTime": "2023-02-14T10:43Z",
                  "retailTransactionId": "ADSKDCDF63EB65B404X",
                  "fedExCartId": "ebb87666-f564-483b-9534-ac16cf4a4ae1",
                  "rateQuoteId": "eyJxdW90ZUlkIjoiMzhmMmZlYmEtNjQwNy00NTg1LWI3NTYtZWRmMDQxNjk4YWNmIiwiY2FydElkIjoiZWJiODc2NjYtZjU2NC00ODNiLTk1MzQtYWMxNmNmNGE0YWUxIn0=",
                  "virtualTill": false
                },
                "lineItems": [
                  {
                    "type": "PRINT_PRODUCT",
                    "retailPrintOrderDetails": [
                      {
                        "customerNotificationEnabled": false,
                        "orderContact": {
                          "contact": {
                            "contactId": "1000010812",
                            "personName": {
                              "firstName": "Nidhi",
                              "lastName": "Singh"
                            },
                            "company": {
                              "name": "FXO"
                            },
                            "emailDetail": {
                              "emailAddress": "nidhi.singh@infogain.com"
                            },
                            "phoneNumberDetails": [
                              {
                                "phoneNumber": {
                                  "number": "9999999999"
                                },
                                "usage": "PRIMARY"
                              }
                            ]
                          }
                        },
                        "responsibleCenterDetail": [
                          {
                            "locationId": "ADSKD",
                            "address": {
                              "streetLines": [
                                "5901 Winthrop Street",
                                "Ste K-140"
                              ],
                              "city": "Plano",
                              "stateOrProvinceCode": "TX",
                              "postalCode": "75024-0101",
                              "countryCode": "US"
                            },
                            "emailDetail": {
                              "emailAddress": "usa5747@fedex.com"
                            },
                            "phoneNumberDetails": [
                              {
                                "phoneNumber": {
                                  "number": "972.324.3017"
                                }
                              }
                            ]
                          }
                        ],
                        "productLines": [
                          {
                            "instanceId": "0",
                            "productId": "1463680545590",
                            "unitQuantity": 50,
                            "unitOfMeasurement": "EACH",
                            "productRetailPrice": "76.00",
                            "productDiscountAmount": "0.0",
                            "productLinePrice": "76.00",
                            "productLineDetails": [
                              {
                                "instanceId": "1",
                                "detailCode": "40005",
                                "hasTermsAndConditions": true,
                                "description": "Full Pg Clr Flyr 50",
                                "priceRequired": false,
                                "priceOverridable": false,
                                "unitQuantity": 1,
                                "quantity": 1,
                                "detailPrice": "76.00",
                                "detailDiscountPrice": "0.0",
                                "detailUnitPrice": "76.000000",
                                "detailDiscountedUnitPrice": "76.000000"
                              }
                            ],
                            "name": "Fast Order Flyer",
                            "userProductName": "B-1491628-Code-Assertion-Report",
                            "type": "PRINT_PRODUCT",
                            "priceable": true
                          }
                        ],
                        "deliveryLines": [
                          {
                            "deliveryLineId": "9842",
                            "recipientReference": "9842",
                            "estimatedDeliveryLocalTime": "2023-02-14T17:00:00",
                            "deliveryLineType": "PICKUP",
                            "recipientContact": {
                              "personName": {
                                "firstName": "Nidhi",
                                "lastName": "Singh"
                              },
                              "company": {
                                "name": "FXO"
                              },
                              "emailDetail": {
                                "emailAddress": "nidhi.singh@infogain.com"
                              },
                              "phoneNumberDetails": [
                                {
                                  "phoneNumber": {
                                    "number": "9999999999"
                                  },
                                  "usage": "PRIMARY"
                                }
                              ]
                            },
                            "pickupDetails": {
                              "locationName": "5747",
                              "requestedPickupLocalTime": "2023-02-14T17:00:00"
                            },
                            "productAssociation": [
                              {
                                "productRef": "0",
                                "quantity": "50.0"
                              }
                            ]
                          }
                        ],
                        "orderTotalDiscountAmount": "0.0",
                        "orderGrossAmount": "76.00",
                        "orderNonTaxableAmount": "0.00",
                        "orderTaxExemptableAmount": "76.00",
                        "orderNetAmount": "76.00",
                        "orderTaxableAmount": "0.00",
                        "orderTaxAmount": "0.0",
                        "orderTotalAmount": "76.00",
                        "notificationRegistration": {
                          "webhook": {
                            "url": "https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/2010310392576573/status"
                          }
                        },
                        "retailCustomerId": "1000010812",
                        "origin": {
                          "orderNumber": "2010310392576573",
                          "orderClient": "MAGENTO",
                          "apiCustomer": "l7e4acbdd6b7d341b0b59234bbdbd4e82e"
                        }
                      }
                    ]
                  }
                ],
                "contact": {
                  "personName": {
                    "firstName": "Nidhi",
                    "lastName": "Singh"
                  },
                  "company": {
                    "name": "FXO"
                  },
                  "emailDetail": {
                    "emailAddress": "nidhi.singh@infogain.com"
                  },
                  "phoneNumberDetails": [
                    {
                      "phoneNumber": {
                        "number": "9999999999"
                      },
                      "usage": "PRIMARY"
                    }
                  ]
                },
                "tenders": [
                  {
                    "id": "1",
                    "paymentType": "CREDIT_CARD",
                    "requestedAmount": "76.0",
                    "tenderedAmount": "76.00",
                    "balanceDueAmount": "0.00",
                    "creditCard": {
                      "type": "VISA",
                      "maskedAccountNumber": "411111xxxxxx1111",
                      "authResponse": "APPROVED",
                      "accountLast4Digits": "xxxxxxxxxxxx1111"
                    },
                    "currency": "USD"
                  }
                ],
                "transactionTotals": {
                  "currency": "USD",
                  "grossAmount": "76.00",
                  "totalDiscountAmount": "0.0",
                  "netAmount": "76.00",
                  "taxAmount": "0.0",
                  "totalAmount": "76.00"
                }
              }
            }
          }';
        $this->assertNotNull(
            $this->apiHandlerMock->getTransactionIdAndProductLinesAttributes(
                json_decode($checkoutResponse)
            )
        );
    }

    /**
     * @return void
     */
    public function testGetTransactionResponse()
    {
        $retailTransectionId = 'bmw12345';

        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::GET_TRANSACTION_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');

        $transactionResponse = [
            'transactionId' => '9c6bef2a-7f4a-4e25-9112-d0844ccbfd23',
            'output' => [
                'transaction' => [
                    'transactionHeader' => [
                        'guid' => 'e34b1e0e-fb72-4870-892b-48a5fe64ed62'
                    ]
                ]
            ]
        ];

        $this->curlMock->expects($this->any())->method('get')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($transactionResponse));
        $this->inStoreRequestBuilderMock->expects($this->any())->method('prepareGetTransactionResponse')
            ->willReturn([]);
        $this->assertNotNull($this->apiHandlerMock->getTransactionResponse($this->quoteMock, $retailTransectionId));
    }

    /**
     * @return void
     */
    public function testGetTransactionResponseWithElse()
    {
        $retailTransectionId = 'bmw12345';
        $transactionResponse = [];

        $this->deliveryHelperMock->expects($this->any())->method('getApiToken')->willReturn(["token"=>'apitoken']);
        $this->configInterfaceMock->expects($this->any())->method('getValue')
            ->with(RateQuoteAndTransactionApiHandler::GET_TRANSACTION_API_URL)
            ->willReturn('https://www.staging3.fedex.com');
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('getGateToken')->willReturn('');
        $this->curlMock->expects($this->any())->method('get')->willReturnSelf();
        $this->curlMock->expects($this->any())->method('getBody')->willReturn(json_encode($transactionResponse));
        $this->submitOrderOptimizedHelperMock->expects($this->any())->method('unsetOrderInProgress')->willReturnSelf();
        $this->assertNotNull($this->apiHandlerMock->getTransactionResponse($this->quoteMock, $retailTransectionId));
    }
}
