<?php

/**
 * Php file,Test case for FXORateQuote.
 *
 * @author  Attri Kumar <attri.kumar@infogain.com>
 * @license http://infogain.com Infogain License
 */

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrder as SubmitOrderModel;
use Fedex\FXOPricing\Model\FXORateQuoteDataArray;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Fedex\FXOPricing\Model\FXORequestBuilder;
use Fedex\FXOPricing\Model\FXOModel;
use Fedex\FXOPricing\Model\FXOProductDataModel;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Cart;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder as InStoreRecipientsBuilder;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Api\AttributeInterface;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\CoreApi\Model\LogHelperApi;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\CartGraphQl\Helper\LoggerHelper;

class FXORateQuoteTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var (\Fedex\CoreApi\Model\LogHelperApi & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $logHelperApi;

    /**
     * @var (\Fedex\FXOPricing\Test\Unit\Model\JsonFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     * Factory class for creating cart (quote) instances.
     */
    protected $cartFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     * Cart model instance.
     */
    protected $cart;

    /**
     * @var \Magento\Checkout\Model\Session
     * Checkout session instance.
     */
    protected $quote;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     * Mock instance of the Curl client used for HTTP requests in unit tests.
     */
    protected $curl;

    /**
     * @var \Magento\Framework\App\RequestInterface
     * Mock instance of the request interface used for unit tests.
     */
    protected $request;

    protected $dataObjectFactoryMock;
    protected $fXORateQuoteDataArrayMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\App\ResponseFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $responseFactoryFactory;
    protected $fxoRequestBuilder;
    protected $fxoModel;
    protected $fXOProductDataModel;
    protected $cartDataHelper;
    protected $submitOrderModelMock;
    protected $itemMock;
    protected $product;
    protected $toggleConfig;
    protected $requestQueryValidator;
    protected $cartIntegrationRepository;
    protected $cartIntegration;
    protected $inStoreRecipientsBuilder;
    protected $instoreConfig;
    protected $serializer;
    protected $addToCartPerformanceOptimizationToggle;
    protected $quoteHelper;
    protected $fuseBidHelper;
    protected $fuseBidViewModel;
    protected $storeManagerInterfaceMock;
    protected $productMock;
    protected $catalogMvpMock;
    protected $attributeInterfaceMock;
    protected $uploadToQuoteViewModel;
    protected $fXORateQuoteModel;
    protected $negotiableQuoteRepository;
    protected $marketplaceCheckoutHelper;
    protected LoggerHelper $loggerHelper;
    public const WEBHOOK_URL = 'https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/2010184916377923/status';

    public const RATE_OUTPUT = [
        'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => '0',
                                'productId' => '1447174746733',
                                'retailPrice' => '$0.59',
                                'discountAmount' => '($0.05)',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.54',
                                'lineDiscounts' => [
                                    0 => [
                                        'amount' => '($0.05)',
                                        'type' => 'ACCOUNT',
                                    ],
                                ],
                                'priceable' => true,
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.54',
                                        'detailDiscountPrice' => '($0.05)',
                                        'detailUnitPrice' => '$0.5900',
                                        'detailDiscountedUnitPrice' => '($0.05)',
                                        'detailDiscounts' => [
                                            0 => [
                                                'amount' => '($0.05)',
                                                'type' => 'AR_CUSTOMERS',
                                            ],
                                        ],
                                    ],
                                ],
                                'productRetailPrice' => '$0.59',
                                'productDiscountAmount' => '($0.05)',
                                'productLinePrice' => '$0.54',
                                'productLineDiscounts' => [
                                    0 => [
                                        'amount' => '($0.05)',
                                        'type' => 'ACCOUNT',
                                    ],
                                ],
                                'editable' => false,
                            ],
                        ],
                        'deliveryLines' => [
                            0 => [
                                'recipientReference' => '',
                                'linePrice' => '$0.00',
                                'lineType' => 'PACKING_AND_HANDLING',
                                'deliveryLinePrice' => '$0.00',
                                'deliveryLineType' => 'PACKING_AND_HANDLING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$0.00',
                                'deliveryDiscountAmount' => '$0.00',
                            ]
                        ],
                        'grossAmount' => '$20.58',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.05)',
                                'type' => 'ACCOUNT',
                            ],
                        ],
                        'totalDiscountAmount' => '($0.05)',
                        'netAmount' => '$20.53',
                        'taxableAmount' => '$20.53',
                        'taxAmount' => '$0.04',
                        'totalAmount' => '$20.57',
                        'estimatedVsActual' => 'ACTUAL',
                    ],
                    1 => [
                        'deliveryLines' => [
                            0 => [
                                'recipientReference' => '',
                                'linePrice' => '$19.99',
                                'estimatedDeliveryLocalTime' => '2021-06-22T12:00:00',
                                'estimatedShipDate' => '2021-06-21',
                                'lineType' => 'SHIPPING',
                                'deliveryLinePrice' => '$10.66',
                                'deliveryLineType' => 'SHIPPING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$19.99',
                                'deliveryDiscountAmount' => '$0.00',
                            ]
                        ],
                        "grossAmount" => '10.66',
                        "totalDiscountAmount" => '0.01',
                        "netAmount" => '10.65',
                        "taxableAmount" => '0',
                        "taxAmount" => '$0.00',
                        "totalAmount" => '10.65',
                        "estimatedVsActual" => "ESTIMATED",
                    ],
                ],
            ],
        ],
    ];

    protected $dataString  = [
        'rateRequest' => [
            'fedExAccountNumber' => null,
            'profileAccountId' => null,
            'site' => null,
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
                        0  => [
                            'id' => '1453242488328',
                            'name' => 'ZOOM_PERCENTAGE',
                            'value' => '50',
                        ],
                        1  => [
                            'id' => '1453243262198',
                            'name' => 'ENCODE_QUALITY',
                            'value' => '100',
                        ],
                        2  => [
                            'id' => '1453894861756',
                            'name' => 'LOCK_CONTENT_ORIENTATION',
                            'value' => false,
                        ],
                        3  => [
                            'id' => '1453895478444',
                            'name' => 'MIN_DPI',
                            'value' => '150.0',
                        ],
                        4  => [
                            'id' => '1454950109636',
                            'name' => 'USER_SPECIAL_INSTRUCTIONS',
                            'value' => null,
                        ],
                        5  => [
                            'id' => '1455050109636',
                            'name' => 'DEFAULT_IMAGE_WIDTH',
                            'value' => '8.5',
                        ],
                        6  => [
                            'id' => '1455050109631',
                            'name' => 'DEFAULT_IMAGE_HEIGHT',
                            'value' => '11',
                        ],
                        7  => [
                            'id' => '1464709502522',
                            'name' => 'PRODUCT_QTY_SET',
                            'value' => '50',
                        ],
                        8  => [
                            'id' => '1459784717507',
                            'name' => 'SKU',
                            'value' => '40005',
                        ],
                        9  => [
                            'id' => '1470151626854',
                            'name' => 'SYSTEM_SI',
                            'value' => 'ABC',
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
            'notificationRegistration' => [
                'webhook' => [
                    'url' => 'https://staging3.office.fedex.com/rest/V1/fedexoffice/orders/2010184916377923/status',
                    'auth' => null,
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
    protected $output = [
        'output' => [
            'rateQuote' => [
                'currency'    => 'USD',
                'rateQuoteDetails' => [
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
                                'productRetailPrice'    => '0.49',
                                'productDiscountAmount' => '0.00',
                                'productLinePrice'      => '0.49',
                                'editable'              => '',
                            ],
                        ],
                        'grossAmount'         => '$0.499',
                        'discounts'           => [
                            0 => [
                                'type' => "AR_CUSTOMERS",
                                'amount' => '$2.43'
                            ]
                        ],
                        'totalDiscountAmount' => '$0.00',
                        'netAmount'           => '$0.494',
                        'taxableAmount'       => '$0.495',
                        'taxAmount'           => '$0.00',
                        'totalAmount'         => '$0.49',
                        'estimatedVsActual'   => 'ACTUAL',
                    ],
                ],

            ]
        ],
    ];

    protected $outputPriceableFalse = [
        'output' => [
            'rateQuote' => [
                'currency'    => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => 0,
                                'productId' => '1508784838900',
                                'retailPrice' => '$0.09',
                                'discountAmount' => '$0.90',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.490',
                                'priceable' => 0,
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.493',
                                        'detailDiscountPrice' => '$0.000',
                                        'detailUnitPrice' => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.002',
                                    ],
                                ],
                                'productRetailPrice' => '0',
                                'productDiscountAmount' => '0',
                                'productLinePrice' => '0',
                                'editable' => '',
                            ],
                        ],
                        'grossAmount' => '$0.499',
                        'discounts' => [
                            0 => [
                                'type' => "AR_CUSTOMERS",
                                'amount' => '$2.43'
                            ]
                        ],
                        'totalDiscountAmount' => '$0.00',
                        'netAmount' => '$0.494',
                        'taxableAmount' => '$0.495',
                        'taxAmount' => '$0.00',
                        'totalAmount' => '$0.49',
                        'estimatedVsActual' => 'ACTUAL',
                    ],
                ],

            ]
        ],
    ];

    public $outputWithAlert = [
        'output' => [
            'alerts' => [
                0 => [
                    'code' => 'MAX.PRODUCT.COUNT'
                ]
            ],
            'rateQuote' => [
                'currency'    => 'USD',
                'rateQuoteDetails' => [
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
                                'productRetailPrice'    => '0.49',
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
            ]
        ],
    ];

    public $outputWithOutAlert = [
        'output' => [
            'rateQuote' => [
                'currency'    => 'USD',
                'rateQuoteDetails' => [
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
                                'productRetailPrice'    => '0.49',
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
            'alerts' => []
        ],
    ];

    public $outputWithMultipleAlert = [
        'output' => [
            'rateQuote' => [
                'currency'    => 'USD',
                'rateQuoteDetails' => [
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
                                'productRetailPrice'    => '0.49',
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
                    'code' => 'MAX.PRODUCT.COUNT'
                ],
                1 => [
                    'code' => 'INVALID.COUPON.CODE'
                ]
            ]
        ],
    ];

    public $outputWithError = [
        'errors' => [
            0 => [
                'code' => 'Something'
            ]
        ]
    ];

    protected $rateQuoteDataString = '{"rateQuoteRequest":{"sourceRetailLocationId":"DNEK",
        "previousQuoteId":"eyJxdW90ZUlkIjoiMTBmYzdiNzItNDBiNS00NjRlLTgzNTQtNjE4MTdhMDE3ZDUzIiwiY2FydElkIj
        oiZTFjNzExYmMtZDlhYS00MGE2LWI2YTItNTkzMzJlMTk3YTlkIn0=",
        "action":"SAVE","retailPrintOrder":{"fedExAccountNumber":null,
        "origin":{"orderNumber":"2020003409054345","orderClient":"FUSE","site":null,"siteName":null,
        "userReferences":null},"orderContact":{"contact":{"contactId":null,"personName":{"firstName":"Mary",
        "lastName":"Doe"},"company":{"name":"FXO"},"emailDetail":{"emailAddress":"mary.doe@mail.com"},
        "phoneNumberDetails":[{"phoneNumber":{"number":"0987654321","extension":null},"usage":"PRIMARY"}]}},
        "customerNotificationEnabled":false,"notificationRegistration":
        {"webhook":{
        "url":"https:\/\/shop-staging.fedex.com\/rest\/V1\/fedexoffice\/orders\/2020003409054345\/status",
        "auth":null}},"profileAccountId":null,"expirationDays":"30","products":[{"productionContentAssociations":[],
        "userProductName":"Posters","id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,
        "instanceId":"84164","proofRequired":false,"isOutSourced":false,"features":[{"id":"1448981549269",
        "name":"Sides","choice":{"id":"1448988124560","name":"Single-Sided","properties":[{"id":"1461774376168",
        "name":"SIDE","value":"SINGLE"},{"id":"1471294217799","name":"SIDE_VALUE","value":"1"}]}},
        {"id":"1464882763509",
        "name":"Product Type","choice":{"id":"1464884233958","name":"Poster Print","properties":[{
        "id":"1494365340946",
        "name":"PREVIEW_TYPE","value":"DYNAMIC"}]}},{"id":"1448981549109","name":"Size","choice":{
        "id":"1449002054022",
        "name":"24x36","properties":[{"id":"1449069906033","name":"MEDIA_HEIGHT","value":"36"},{
        "id":"1449069908929",
        "name":"MEDIA_WIDTH","value":"24"},{"id":"1571841122054","name":"DISPLAY_HEIGHT","value":"36"},
        {"id":"1571841164815","name":"DISPLAY_WIDTH","value":"24"}]}},{"id":"1448981549741","name":"Paper Type",
        "choice":{"id":"1448989269489","name":"Matte Paper","properties":[{"id":"1450324098012","name":"MEDIA_TYPE",
        "value":"ROL01"}]}},{"id":"1448984679218","name":"Orientation","choice":{"id":"1449000016192",
        "name":"Vertical",
        "properties":[{"id":"1453260266287","name":"PAGE_ORIENTATION","value":"PORTRAIT"}]}},{"id":"1448985337634",
        "name":"Grommets","choice":{"id":"1449001942938","name":"None","properties":[]}},{"id":"1448985622584",
        "name":"Mounting","choice":{"id":"1449002427826","name":"None","properties":[]}},{"id":"1448984679442",
        "name":"Lamination","choice":{"id":"1448999458409","name":"None","properties":[]}}],"pageExceptions":[],
        "contentAssociations":[{"parentContentReference":"13289794711201983910502662288000436463376",
        "contentReference":"13289794713034363585509782385531244844811","contentType":"IMAGE",
        "fileName":"images.jpg","contentReqId":"1455709847200","name":"Poster","desc":null,
        "purpose":"SINGLE_SHEET_FRONT","specialInstructions":"","printReady":true,
        "pageGroups":[{"start":1,"end":1,"width":24,"height":36,"orientation":"PORTRAIT"}]}],
        "properties":[{"id":"1453242488328","name":"ZOOM_PERCENTAGE","value":"60"},
        {"id":"1453243262198","name":"ENCODE_QUALITY","value":"100"},{"id":"1453894861756",
        "name":"LOCK_CONTENT_ORIENTATION","value":true},{"id":"1453895478444","name":"MIN_DPI","value":"150.0"},
        {"id":"1454606828294","name":"SPC_TYPE_ID","value":"12"},{"id":"1454606860996","name":"SPC_MODEL_ID",
        "value":"1"},{"id":"1454606876712","name":"SPC_VERSION_ID","value":"1"},{"id":"1454950109636",
        "name":"USER_SPECIAL_INSTRUCTIONS","value":null},{"id":"1470151626854","name":"SYSTEM_SI","value":null},
        {"id":"1455050109636","name":"DEFAULT_IMAGE_WIDTH","value":"24"},{"id":"1455050109631",
        "name":"DEFAULT_IMAGE_HEIGHT","value":"36"}],"preview_url":null,"isEditable":true,"isEdited":false,
        "fxoMenuId":"1534434635598-4"}],"recipients":[{"contact":{"contactId":null,"personName":{"firstName":"Mary",
        "lastName":"Doe"},"company":{"name":"FXO"},"emailDetail":{"emailAddress":"mary.doe@mail.com"},
        "phoneNumberDetails":[{"phoneNumber":{"number":"0987654321","extension":null},"usage":"PRIMARY"}]},
        "reference":"2181","pickUpDelivery":{"location":{"id":"0798"},"requestedPickupLocalTime":null},
        "productAssociations":[{"id":"84164","quantity":1}]}],"notes":[{"audit":{
        "creationTime":"2023-04-28T10:30:00Z","user":"Angel","userReference":{"reference":"Testing",
        "source":"MAGENTO"}},"text":"Irfan12"},{"audit":{"creationTime":"2023-04-28T10:30:00Z",
        "user":"Angel","userReference":{"reference":"Testing","source":"MAGENTO"}},"text":"Test 123"}]},
        "coupons":null,"teamMemberId":"5034118","validateContent":true}}';

    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    //Api token key
    protected $apiToken = [
        'token' => 'iuayqiuyeiuqwtyeiyqiuqywiueyqwiueyuqwi'
    ];

    protected $quoteMock;

    protected $responseFactory;

    protected $submitOrderHelper;

    protected $productPriceHandlerMock;

    protected $response;

    protected $expiredDataHelper;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->logHelperApi = $this->createMock(LogHelperApi::class);
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()->getMock();
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->cart = $this->getMockBuilder(Cart::class)->setMethods(['getQuote', 'save'])
            ->disableOriginalConstructor()->getMock();
        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods($this->quoteSetMethodsValues())
            ->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions', 'post'])->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)->setMethods(['getPostValue', 'getContent'])
            ->getMockForAbstractClass();
        $this->dataObjectFactoryMock = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'create',
                    'setQuoteObject',
                    'setFedExAccountNumber',
                    'setProductsData',
                    'setOrderNumber',
                    'setWebHookUrl',
                    'setPromoCodeArray',
                    'setSite',
                    'setSiteName',
                    'setRecipients',
                    'setIsGraphQlRequest',
                    'setQuoteLocationId',
                    'setValidateContent',
                    'setOrderNotes',
                    'setRetailCustomerId',
                    'setLteIdentifier'
                ]
            )
            ->getMock();
        $this->fXORateQuoteDataArrayMock = $this->createMock(FXORateQuoteDataArray::class);
        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getUrl'])->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->responseFactoryFactory = $this->getMockBuilder(ResponseFactory::class)
            ->setMethods(['create','setRedirect','sendResponse'])
            ->disableOriginalConstructor()->getMock();
        $this->fxoRequestBuilder = $this->createMock(FXORequestBuilder::class);
        $this->fxoModel = $this->getMockBuilder(FXOModel::class)
            ->setMethods([
                'getDbItemsCount',
                'removeReorderQuoteItem',
                'removeQuoteItem',
                'resetCartDiscounts',
                'saveDiscountBreakdown',
                'isVolumeDiscountAppliedonItem',
                'checkErrorsAndRemoveDiscounts',
                'updateQuoteDiscount'
            ])->disableOriginalConstructor()
            ->getMock();
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods([
                'isSendRetailLocationIdEnabled',
                'isFuseBidToggleEnabled'
            ])->disableOriginalConstructor()
            ->getMock();
        $this->fXOProductDataModel = $this->createMock(FXOProductDataModel::class);
        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData', 'encryptData', 'getRateQuoteApiUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->submitOrderModelMock = $this->getMockBuilder(SubmitOrderModel::class)
            ->setMethods([
                'getGTNNumber',
                'getWebHookUrl',
                'validateRateQuoteAPIErrors',
                'validateRateQuoteAPIWarnings'
            ])->disableOriginalConstructor()
            ->getMock();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getItemId',
                    'setDiscountAmount',
                    'setBaseDiscountAmount',
                    'setRowTotal',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setIsSuperMode',
                    'getOptionByCode',
                    'removeOption',
                    'getQty',
                    'setDiscount',
                    'getProduct',
                    'save',
                    'setBaseRowTotal',
                    'getId',
                    'getMiraklOfferId',
                    'setAdditionalData',
                    'getAdditionalData'
                ]
            )
            ->getMock();
        $this->product = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'setIsSuperMode'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->requestQueryValidator = $this->createMock(RequestQueryValidator::class);
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->cartIntegration = $this->createMock(CartIntegrationInterface::class);
        $this->inStoreRecipientsBuilder = $this->createMock(InStoreRecipientsBuilder::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getExpiredItemIds',
                    'unsValidateContentApiExpired',
                    'setValidateContentApiExpired',
                    'getCustomer',
                    'getGroupId'
                ]
            )
            ->getMock();

        $this->quoteHelper = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFullMiraklQuote'])
            ->getMockForAbstractClass();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'load',
                    'getCustomAttribute',
                    'getValue',
                    'getData',
                    'setData',
                    'save'
                ]
            )
            ->getMock();

        $this->catalogMvpMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'isMvpSharedCatalogEnable',
                    'isProductPodEditAbleById'
                ]
            )
            ->getMock();

        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->onlyMethods(['getValue', 'setValue', 'getAttributeCode', 'setAttributeCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUploadToQuoteEnable', 'updateItemsSI', 'excludeDeletedItem','updateItemsForFuse'])
            ->getMock();
        $this->negotiableQuoteRepository = $this->getMockBuilder(NegotiableQuoteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();
        $this->addToCartPerformanceOptimizationToggle = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActive'])
            ->getMock();
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSendRetailLocationIdEnabled'])
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getIsBid','getAllVisibleItems','getData','hasGtn','getAllItems','getLocationId','getIsAjaxRequest'])
            ->getMock();

        $this->fuseBidHelper = $this->getMockBuilder(FuseBidHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSendRetailLocationIdEnabled'])
            ->getMock();

        $this->marketplaceCheckoutHelper = $this->getMockBuilder(MarketplaceCheckoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEssendantToggleEnabled'])
            ->getMock();

        $this->submitOrderHelper = $this->getMockBuilder(SubmitOrderHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveRetailTransactionId','isQuoteOrderAvailable','getRetailTransactionId','getCustomerOnBehalfOf'])
            ->getMock();
        $this->loggerHelper = $this->createMock(LoggerHelper::class);
        $this->objectManager = new ObjectManager($this);
        $this->fXORateQuoteModel = $this->objectManager->getObject(
            FXORateQuote::class,
            [
                'logger'                        => $this->logger,
                'curl'                          => $this->curl,
                'cartFactory'                   => $this->cartFactory,
                'cartDataHelper'                => $this->cartDataHelper,
                'fxoRateQuoteDataArray' => $this->fXORateQuoteDataArrayMock,
                'dataObjectFactory'     => $this->dataObjectFactoryMock,
                'submitOrderModel'      => $this->submitOrderModelMock,
                'urlInterface'          => $this->urlInterfaceMock,
                'responseFactory'       => $this->responseFactoryFactory,
                'fXORequestBuilder'     => $this->fxoRequestBuilder,
                'fxoModel'              => $this->fxoModel,
                'fXOProductDataModel'   => $this->fXOProductDataModel,
                'toggleConfig'          => $this->toggleConfig,
                'requestQueryValidator' => $this->requestQueryValidator,
                'request'               => $this->request,
                'cartIntegrationRepository' => $this->cartIntegrationRepository,
                'inStoreRecipientsBuilder' => $this->inStoreRecipientsBuilder,
                'customerSession'          => $this->customerSession,
                'instoreConfig'         => $this->instoreConfig,
                'quoteHelper'           => $this->quoteHelper,
                'storeManager'          => $this->storeManagerInterfaceMock,
                'product'               => $this->productMock,
                'catalogMvpHelper'      => $this->catalogMvpMock,
                'serializer' => $this->serializer,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel,
                'newRelicHeaders'       => $this->newRelicHeaders,
                'logHelperApi'          => $this->logHelperApi,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'addToCartPerformanceOptimizationToggle' => $this->addToCartPerformanceOptimizationToggle,
                'marketplaceCheckoutHelper' => $this->marketplaceCheckoutHelper,
                'submitOrderHelper' => $this->submitOrderHelper,
                'loggerHelper' => $this->loggerHelper
            ]
        );
    }

    /**
     * Tests the `getFXORateQuote` method of the `FXORateQuote` model to ensure it returns
     * a cached result when the AddToCartPerformanceOptimizationToggle is active.
     *
     * Assertions:
     * - Ensures that the result returned by `getFXORateQuote` matches the expected cached result.
     */
    public function testGetFXORateQuoteReturnsCachedResultWhenOptimizationToggleIsActive()
    {
        $this->quoteMock->method('getId')->willReturn(123);

        // Mock AddToCartPerformanceOptimizationToggle to return true
        $this->addToCartPerformanceOptimizationToggle->method('isActive')->willReturn(true);

        $reflectedClass = new \ReflectionClass(FXORateQuote::class);
        $cacheProperty = $reflectedClass->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheKey = 'fxo_rate_quote_123';
        $expectedCachedResult = ['cached_rate' => 100];
        $cacheProperty->setValue(null, [$cacheKey => $expectedCachedResult]);
        $result = $this->fXORateQuoteModel->getFXORateQuote($this->quoteMock);
        $this->assertEquals($expectedCachedResult, $result);
    }

    /**
     * Test case for verifying that the FXO rate quote integration is fetched
     * when the Fuse Bid feature is enabled, the quote is marked as a bid,
     * and the request is not a GraphQL request.
     *
     *
     * Assertions:
     * - The result of the `getFXORateQuote` method is an array.
     */
    public function testQuoteIntegrationIsFetchedWhenFuseBidEnabledAndIsBidAndNotGraphQL()
    {
        $quoteId = 789;
        $quoteLocationId = 'loc_123';
        $retailCustomerId = 'cust_456';

        $this->quoteMock->method('getId')->willReturn($quoteId);
        $this->quoteMock->method('getIsBid')->willReturn(true);

        $this->requestQueryValidator
            ->method('isGraphQlRequest')
            ->with($this->request)
            ->willReturn(false);

        $this->fuseBidViewModel
            ->method('isSendRetailLocationIdEnabled')
            ->willReturn(true);

        $quoteIntegrationMock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['getLocationId', 'getRetailCustomerId'])
            ->getMock();
        $quoteIntegrationMock->method('getLocationId')->willReturn($quoteLocationId);
        $quoteIntegrationMock->method('getRetailCustomerId')->willReturn($retailCustomerId);

        $this->cartIntegrationRepository
            ->expects($this->any())
            ->method('getByQuoteId')
            ->with($quoteId)
            ->willReturn($quoteIntegrationMock);

        $reflectedClass = new \ReflectionClass(FXORateQuote::class);
        $method = $reflectedClass->getMethod('getFXORateQuote');
        $method->setAccessible(true);

        $result = $method->invoke($this->fXORateQuoteModel, $this->quoteMock);
        $this->assertIsArray($result);
    }

    /**
     * Quote setMethods values
     *
     * @return array
     */
    private function quoteSetMethodsValues()
    {
        return [
            'getIsBid',
            'getId',
            'getAllItems',
            'deleteItem',
            'getData',
            'getShippingAddress',
            'save',
            'getIsFromShipping',
            'getIsFromPickup',
            'getIsFromAccountScreen',
            'getAllVisibleItems',
            'getCustomerPickupLocationData',
            'getCustomerShippingAddress',
            'getCouponCode',
            'getProduct',
            'setData',
            'setDiscount',
            'setSubTotal',
            'setBaseSubTotal',
            'setGrandTotal',
            'setBaseGrandTotal',
            'setCustomTaxAmount',
            'setShippingCost',
            'create',
            'load',
            'getItemById',
            'hasGtn'
        ];
    }

    /**
     * Test case for getGTNNumber
     */
    public function testgetGTNNumber()
    {
        $this->quote->expects($this->any())->method('getData')->with('gtn')->willReturn('1234');
        $this->assertNotNull($this->fXORateQuoteModel->getGTNNumber($this->quote));
    }

    /**
     * Test case for getGTNNumberWith Null
     */
    public function testgetGTNNumberWithNull()
    {
        $this->quote->expects($this->any())->method('getData')->with('gtn')->willReturn('');
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('123');
        $this->assertNotNull($this->fXORateQuoteModel->getGTNNumber($this->quote));
    }

    /**
     * Test case for getInstoreConfigNotesCheck
     */
    public function testGetInstoreConfigNotesCheck()
    {
        $this->instoreConfig->expects($this->any())
            ->method('isEnabledAddNotes')
            ->willReturn(true);
        $this->assertTrue($this->fXORateQuoteModel->getInstoreConfigNotesCheck(false));
    }

    /**
     * Test case for updateQuoteInfo
     */
    public function testUpdateQuoteInfo()
    {
        $this->quote->expects($this->any())->method('setDiscount')->willReturnSelf();
        $this->quote->expects($this->any())->method('setSubTotal')->willReturnSelf();
        $this->quote->expects($this->any())->method('setBaseSubTotal')->willReturnSelf();
        $this->quote->expects($this->any())->method('setGrandTotal')->willReturnSelf();
        $this->quote->expects($this->any())->method('setBaseGrandTotal')->willReturnSelf();
        $this->quote->expects($this->any())->method('setCustomTaxAmount')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull($this->fXORateQuoteModel->updateQuoteInfo($this->quote, static::RATE_OUTPUT));
    }

    /**
     * Test case for updateRateForAccount
     */
    public function testUpdateRateForAccount()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->itemMock->expects($this->any())->method('getMiraklOfferId')->willReturn(true);
        $this->itemMock->expects($this->any())->method('getItemId')->willReturn(0);
        $this->itemMock->expects($this->any())->method('setDiscountAmount')->willReturn($this);
        $this->itemMock->expects($this->any())->method('setBaseDiscountAmount')->willReturn($this);
        $this->itemMock->expects($this->any())->method('setDiscount')->willReturn($this);
        $this->itemMock->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('setIsSuperMode')->willReturn(true);
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturn($this);
        $this->itemMock->expects($this->any())->method('save')->willReturn($this->itemMock);
        $this->assertNull($this->fXORateQuoteModel->updateRateForAccount($this->output, $this->quote));
    }

    /**
     * Test case for updateRateForAccount
     */
    public function testUpdateRateForAccountWithMiraklOfferId()
    {
        $this->itemMock->expects($this->any())->method('getItemId')->willReturn(0);
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willReturnSelf();
        $this->product->expects($this->any())->method('setIsSuperMode')->willReturn(true);
        $this->itemMock->expects($this->any())->method('getProduct')->willReturn($this->product);

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->assertNull($this->fXORateQuoteModel->updateRateForAccount($this->output, $this->quote));
    }

    /**
     * Test case for updateRateForAccountSdeDiscountFix
     */
    public function testUpdateRateForAccountSdeDiscountFix()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->itemMock->expects($this->any())->method('getMiraklOfferId')->willReturn(true);
        $this->itemMock->expects($this->any())->method('getItemId')->willReturn(0);
        $this->itemMock->expects($this->any())->method('setDiscountAmount')->willReturn($this);
        $this->itemMock->expects($this->any())->method('setBaseDiscountAmount')->willReturn($this);
        $this->itemMock->expects($this->any())->method('setDiscount')->willReturn($this);
        $this->itemMock->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('setIsSuperMode')->willReturn(true);
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturn($this);
        $this->itemMock->expects($this->any())->method('save')->willReturn($this->itemMock);
        $this->assertNull($this->fXORateQuoteModel->updateRateForAccountSdeDiscountFix($this->output, $this->quote));
    }

    /**
     * Test case for updateRateForAccountSdeDiscountFix
     */
    public function testUpdateRateForAccountSdeDiscountFixWithMiraklOfferId()
    {
        $this->itemMock->expects($this->any())->method('getItemId')->willReturn(0);
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willReturnSelf();
        $this->product->expects($this->any())->method('setIsSuperMode')->willReturn(true);
        $this->itemMock->expects($this->any())->method('getProduct')->willReturn($this->product);

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->assertNull($this->fXORateQuoteModel->updateRateForAccountSdeDiscountFix($this->output, $this->quote));
    }

    /**
     * Test case for updateCartItems
     */
    public function testupdateCartItemsWithMiraklOfferId()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];

        $this->assertNull($this->fXORateQuoteModel->updateCartItems(
            [$this->itemMock],
            $this->output,
            $itemsUpdatedData,
            123,
            123,
            false
        ));
    }

    /**
     * Test case for updateCartItems
     */
    public function testupdateCartItems()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->itemMock->expects($this->any())->method('getMiraklOfferId')->willReturn(true);
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setCustomPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setOriginalCustomPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willReturnSelf();
        $this->assertNull($this->fXORateQuoteModel->updateCartItems(
            [$this->itemMock],
            $this->output,
            $itemsUpdatedData,
            123,
            123
        ));
    }

    /**
     * Test case for updateCartItems
     */
    public function testupdateCartItemsRoundPricesValues()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->itemMock->expects($this->any())->method('getMiraklOfferId')->willReturn(true);
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setCustomPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setOriginalCustomPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willReturnSelf();
        $this->assertNull($this->fXORateQuoteModel->updateCartItems(
            [$this->itemMock],
            $this->output,
            $itemsUpdatedData,
            123,
            123,
            false
        ));
    }

    /**
     * Test case for testupdateCartItemsPricesFromRAQEnabled
     */
    public function testupdateCartItemsPricesFromRAQEnabled()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->requestQueryValidator->expects($this->any())
            ->method('isGraphQl')
            ->willReturn(true);

        $productRetailLinePriceMock = json_encode(['productLinePrice' => 0, 'productRetailPrice' => 0]);
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setCustomPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setOriginalCustomPrice')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willReturnSelf();
        $this->itemMock->expects($this->any())->method('getAdditionalData')
            ->willReturn($productRetailLinePriceMock, true);
        $this->itemMock->expects($this->any())->method('setAdditionalData')
            ->with($productRetailLinePriceMock)->willReturnSelf();
        $this->assertNull($this->fXORateQuoteModel->updateCartItems(
            [$this->itemMock],
            $this->outputPriceableFalse,
            $itemsUpdatedData,
            123,
            123,
            false
        ));
    }

    /**
     * Test case for manageOutputData
     */
    public function testmanageOutputData()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $this->assertNull($this->fXORateQuoteModel->manageOutputData(
            $this->quote,
            [$this->itemMock],
            $itemData,
            $this->output,
            'UAT001'
        ));
    }

    /**
     * Test case for manageOutputDataFromAccountScreen
     */
    public function testmanageOutputDataFromAccountScreen()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $this->quote->expects($this->any())->method('getIsFromAccountScreen')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(true);
        $this->testUpdateRateForAccount();
        $this->assertNull($this->fXORateQuoteModel->manageOutputData(
            $this->quote,
            [$this->itemMock],
            $itemData,
            $this->output,
            'UAT001'
        ));
    }

    /**
     * Test case for manageOutputDataFromShippingScreen
     */
    public function testmanageOutputDataFromShippingScreen()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(true);
        $this->assertNull($this->fXORateQuoteModel->manageOutputData(
            $this->quote,
            [$this->itemMock],
            $itemData,
            $this->output,
            'UAT001'
        ));
    }

    /**
     * Test case for callRateQuoteApi
     */
    public function testcallRateQuoteApi()
    {
        $requestContentSerialized = 'a:1:{s:5:"query";s:17:"addProductsToCart"}';
        $requestContentUnserialized = ['query' => "addProductsToCart"];
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $this->requestQueryValidator->expects($this->any())
            ->method('isGraphQlRequest')
            ->willReturn(true);
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(true);
        $this->request->expects($this->any())->method('getContent')->willReturn($requestContentSerialized);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($requestContentUnserialized);
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($this->output));
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('checkErrorsAndRemoveDiscounts')->willReturnSelf();
        $this->assertNotNull($this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            $this->rateQuoteDataString,
            $headers,
            'reorder'
        ));
    }

    public function testGetFXORateQuoteUnsetData()
    {
        $dataString = json_decode($this->rateQuoteDataString);
        $itemData = [
            'productAssociations' => [0=> ['test']],
            'rateApiProdRequestData' => $this->rateQuoteDataString
        ];

        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];

        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];

        $this->addToCartPerformanceOptimizationToggle->expects($this->any())
            ->method('isActive')
            ->willReturn(true);
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->requestQueryValidator->expects($this->any())
            ->method('isNegotiableQuoteGraphQlRequest')
            ->willReturn(true);
        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->fuseBidViewModel->expects($this->any())
            ->method('isSendRetailLocationIdEnabled')
            ->willReturn(true);
        $this->fuseBidHelper->expects($this->any())
            ->method('isSendRetailLocationIdEnabled')
            ->willReturn(true);
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);

        $this->quote->expects($this->any())
            ->method('getData')
            ->withConsecutive(
                ['coupon_code'],
                ['gtn']
            )->willReturnOnConsecutiveCalls(
                'UAT001',
                null
            );

        $this->quote->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['fjmp_quote_id'],
                ['coupon_code'],
                ['gtn']
            )->willReturnOnConsecutiveCalls(
                '1234',
                'UAT001',
                '2010184916377923'
            );

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->marketplaceCheckoutHelper->expects($this->any())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->itemMock]);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willReturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willReturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->with('12', 1, $itemData['productAssociations'], null)
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn(static::WEBHOOK_URL);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($dataString);

        $this->instoreConfig->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->testcallRateQuoteApi();
        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        ));
    }

    /**
     * Test case for callRateQuoteApiWithAlert
     */
    public function testcallRateQuoteApiWithAlert()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($this->outputWithAlert));
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();
        $this->assertNotNull($this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            json_encode($this->dataString),
            $headers,
            'reorder'
        ));
    }

    /**
     * Test case for callRateQuoteApiWithAlertWithShippingPage
     */
    public function testcallRateQuoteApiWithAlertWithShippignPage()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($this->outputWithAlert));
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willreturn(true);
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();
        $this->assertNotNull($this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            json_encode($this->dataString),
            $headers,
            'reorder',
            false
        ));
    }

    /**
     * Test case for callRateQuoteApiWithError
     */
    public function testcallRateQuoteApiWithError()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($this->outputWithError));
        $this->instoreConfig->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willreturn(true);
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();

        $this->expectException(GraphQlFujitsuResponseException::class);

        $this->assertNotNull($this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            json_encode($this->dataString),
            $headers,
            'reorder'
        ));
    }

    public function testCallRateQuoteApiWithGraphQlFujitsuResponseException(): void
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($this->outputWithError));
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willreturn(true);
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();
        $this->instoreConfig->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            json_encode($this->dataString),
            $headers,
            'reorder'
        );
    }

    public function testCallRateQuoteApiWithFujitsuWrongResponse(): void
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];

        $errorOutput = $this->outputWithError;
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($errorOutput));
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willreturn(true);
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();
        $this->instoreConfig->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            json_encode($this->dataString),
            $headers,
            'reorder'
        );
    }

    /**
     * Test case for callRateQuoteApiWithErrorNotReorder
     */
    public function testcallRateQuoteApiWithErrorNotReorder()
    {
        $itemsUpdatedData = [
            0 => [
                'test' => 'test'
            ]
        ];
        $itemData = [
            'itemsUpdatedData' => $itemsUpdatedData,
            'quoteObjectItemsCount' => 12,
            'dbQuoteItemCount' => 12
        ];
        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $this->curl->expects($this->any())->method('getBody')->willReturn(json_encode($this->outputWithError));
        $this->cartDataHelper->expects($this->any())->method('getRateQuoteApiUrl')->willReturn('https://fedex.com');
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willreturn(true);
        $this->fxoModel->expects($this->any())->method('saveDiscountBreakdown')->willReturnSelf();
        $this->fxoModel->expects($this->any())->method('isVolumeDiscountAppliedonItem')->willReturnSelf();
        $this->assertNotNull($this->fXORateQuoteModel->callRateQuoteApi(
            $this->quote,
            [$this->itemMock],
            $itemData,
            "UAT001",
            json_encode($this->dataString),
            $headers,
            ''
        ));
    }

    /**
     * Test case for getFXORateQuoteCartPerform
     */
    public function testgetFXORateQuoteCartPerfrom()
    {
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];

        $cache = [];

        $cachedResult = ["errors" => "System error, Please try again."];
        $this->addToCartPerformanceOptimizationToggle->expects($this->any())->method('isActive')
            ->willReturn(true);
        $this->fuseBidViewModel->expects($this->any())->method('isSendRetailLocationIdEnabled')
            ->willReturn(true);

        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['coupon_code'],
            ['requested_pickup_local_time'],
            ['gtn']
        )->willReturnOnConsecutiveCalls(
            'UAT001',
            "2023-05-31T00:00:00",
            null
        );
       // $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->with('12', 1, $itemData['productAssociations'], "2023-05-31T00:00:00")
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->with($this->quote)->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->with('2010184916377923')->willReturn(static::WEBHOOK_URL);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->assertEquals($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        ),$cachedResult);
    }

    /**
     * Test case for getFXORateQuote
     */
    public function testgetFXORateQuote()
    {
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->addToCartPerformanceOptimizationToggle->expects($this->any())
            ->method('isActive')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->quote->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);
        $this->fuseBidViewModel->expects($this->any())
            ->method('isSendRetailLocationIdEnabled')
            ->willReturn(true);
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->willReturn('2023-05-31T00:00:00');
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getGTNNumber')
            ->with($this->quote)
            ->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())
            ->method('getWebHookUrl')
            ->with('2010184916377923')
            ->willReturn(static::WEBHOOK_URL);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        ));
    }

    public function testgetFXORateQuoteForFuseRequestToggleEnable()
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $fuseRequest = [
            0 => ['quote_items'=> 'test','quote_action' => 'send_to_customer']
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->willReturn("2023-05-30T00:00:00");

        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->with('12', 1, $itemData['productAssociations'], "2023-05-31T00:00:00")
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->with($this->quote)->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->with('2010184916377923')->willReturn(static::WEBHOOK_URL);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsForFuse')->willReturn(['']);

        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            false,
            [],
            $fuseRequest,
            [],
            false
        );
        $this->assertEquals($expectedResult, $result);
    }

    public function testgetFXORateQuoteForFuseRequestToggleDisable()
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $fuseRequest = [
            0 => ['quote_items'=> 'test','quote_action' => 'send_to_customer']
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->willReturn("2023-05-30T00:00:00");

        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->with($this->quote)->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->with('2010184916377923')->willReturn(static::WEBHOOK_URL);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsForFuse')->willReturn(['']);

        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            false,
            [],
            $fuseRequest,
            [],
            false
        );
        $this->assertEquals($expectedResult, $result);
    }

    public function testgetFXORateQuoteForFuseRequestToggleWithGTN()
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $fuseRequest = [
            0 => ['quote_items'=> 'test','quote_action' => 'send_to_customer']
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->willReturn("2023-05-30T00:00:00");

        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsForFuse')->willReturn(['']);
        $this->quoteMock->expects($this->any())->method('hasGtn')->willReturn(1);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();

        $this->quote->expects($this->any())->method('getData')->willReturn('1234');

        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('2010184916377923');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();

        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            false,
            [],
            $fuseRequest,
            [],
            false
        );
        $this->assertEquals($expectedResult, $result);
    }

    public function testgetFXORateQuoteforExpiredItems()
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $fuseRequest = [
            0 => ['quote_items'=> 'test','quote_action' => 'send_to_customer']
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->willReturn("2023-05-30T00:00:00");

        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsForFuse')->willReturn(['']);
        $this->quoteMock->expects($this->any())->method('hasGtn')->willReturn(1);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();

        $this->quote->expects($this->any())->method('getData')->willReturn('1234');

        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('2010184916377923');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();

        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            false,
            [],
            $fuseRequest,
            [],
            false
        );
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test case for the `getFXORateQuote` method in the FXORateQuote model.
     *
     * Test Data:
     * - Authentication details, recipient data, item data, and fuse request data are mocked.
     * - Various methods are stubbed to simulate expected behavior.
     */
    public function testgetFXORateQuoteforExpiredItemsElse()
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => ''
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $fuseRequest = [
            0 => ['quote_items'=> 'test','quote_action' => 'send_to_customer']
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->willReturn("2023-05-30T00:00:00");

        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();

        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->submitOrderHelper->expects($this->any())->method('getCustomerOnBehalfOf')->willReturn([]);

        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsForFuse')->willReturn(['']);
        $this->quoteMock->expects($this->any())->method('hasGtn')->willReturn(1);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();

        $this->quote->expects($this->any())->method('getData')->willReturn('1234');

        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('2010184916377923');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();

        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            false,
            [],
            $fuseRequest,
            [],
            false
        );
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetFXORateQuoteWithGraphQlFujitsuResponseException(): void
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $exception = new GraphQlFujitsuResponseException(__("Some Message"));

        $this->fxoRequestBuilder->expects($this->any())
            ->method('getAuthenticationDetails')
            ->willThrowException($exception);

        $this->instoreConfig->expects($this->any())
            ->method('isEnabledThrowExceptionOnGraphqlRequests')
            ->willReturn(true);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        );
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test case for testgetFXORateQuoteInStore
     */
    public function testgetFXORateQuoteInStore()
    {
        $expectedResult = ["errors" => "System error, Please try again."];
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => null
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['coupon_code'],
            ['requested_pickup_local_time'],
            ['gtn']
        )->willReturnOnConsecutiveCalls(
            'UAT001',
            null,
            null
        );

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())
            ->method('getPickupLocationDate')
            ->willReturn("2023-05-31T00:00:00");

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');

        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->with('12', 1, $itemData['productAssociations'], "2023-05-31T00:00:00")
            ->willReturn($arrRecipientsData);
        $this->quote->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['fjmp_quote_id'],
                ['coupon_code'],
                ['gtn']
            )->willReturnOnConsecutiveCalls(
                '1234',
                'UAT001',
                '2010184916377923'
            );
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn(static::WEBHOOK_URL);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $result = $this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        );
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test case for testgetFXORateQuoteInStoreDisableWarningHandling
     */
    public function testgetFXORateQuoteInStoreDisableWarningHandling()
    {
        $itemData = [
            'productAssociations' => [0=> 'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => null
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);

        $this->quote->expects($this->any())
            ->method('getData')
            ->withConsecutive(
                ['coupon_code'],
                ['requested_pickup_local_time'],
                ['gtn']
            )->willReturnOnConsecutiveCalls(
                'UAT001',
                "2023-05-31T00:00:00",
                null
            );

        $this->quote->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['fjmp_quote_id'],
                ['coupon_code'],
                ['gtn']
            )->willReturnOnConsecutiveCalls(
                '1234',
                'UAT001',
                '2010184916377923'
            );

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegration);

        $this->quote->expects($this->any())->method('create')->willReturnSelf($this->quote);
        $this->quote->expects($this->any())->method('load')->willReturnSelf();

        $this->cartIntegration->expects($this->any())->method('getLocationId')->willReturn("12345");
        $this->cartIntegration->expects($this->any())->method('getRetailCustomerId')->willReturn("12345");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');

        $this->inStoreRecipientsBuilder->expects($this->any())
            ->method('build')
            ->with('12', 1, $itemData['productAssociations'], "2023-05-31T00:00:00")
            ->willReturn($arrRecipientsData);

        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn(static::WEBHOOK_URL);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        ));
    }

    /**
     * Test case for getFXORateQuoteWithExpiredQuote
     */
    public function testgetFXORateQuoteWithExpiredQuote()
    {
        $itemData = [
            'productAssociations' => [0=>'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];

        $uploadToQuoteRequestWithChangeRequest = [
            'action' => 'changeRequested',
            'items' => [
                [ 'item_id' => 123]
            ]
        ];
        $uploadToQuoteRequestWithDeletItem = [
            'action' => 'deleteItem',
            'item_id' => 123
        ];
        $fuseRequest = [
            'item_id' => 123,
            'product' => "test",
            'quote_action' => 'save',
            ['quote_items' => ['item_action' => 'save']]
        ];
        $cachedResult = ["errors" => "System error, Please try again."];

        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['coupon_code'],
            ['gtn']
        )->willReturnOnConsecutiveCalls(
            'UAT001',
            '1234354545'
        );

        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(false);

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $exception = new NoSuchEntityException(__('Test Error Message'));
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipDataUpdated')->willreturn($arrRecipientsData);
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNotes')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRetailCustomerId')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);


        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn(['1234', '1235']);
        $this->uploadToQuoteViewModel->expects($this->any())->method('isUploadToQuoteEnable')->willReturn(true);
        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsSI')->willReturn($arrRecipientsData);
        $this->uploadToQuoteViewModel->expects($this->any())
            ->method('excludeDeletedItem')->willReturn([0 => $this->itemMock]);
        $this->uploadToQuoteViewModel->expects($this->any())
            ->method('updateItemsForFuse')->willReturn([0 => $this->itemMock]);
        $this->quote->expects($this->any())
            ->method('hasGtn')
            ->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->with('1234354545')->willReturn(static::WEBHOOK_URL);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);

        $this->quote->expects($this->any())->method('getData')->willReturn('expired');

        $this->testcallRateQuoteApi();
        $this->assertEquals($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            null,
            $uploadToQuoteRequestWithChangeRequest,
            $fuseRequest
        ),$cachedResult);
    }

    /**
     * Test case for getFXORateQuoteWithToggle
     */
    public function testgetFXORateQuoteWithToggle()
    {
        $itemData = [
            'productAssociations' => [0=>'test'],
            'rateApiProdRequestData' => $this->dataString
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $uploadToQuoteRequestWithChangeRequest = [
            'action' => 'changeRequested',
            'items' => [
                [ 'item_id' => 123]
            ]
        ];
        $uploadToQuoteRequestWithDeletItem = [
            'action' => 'deleteItem',
            'item_id' => 123
        ];
        $fuseRequest = [
            'item_id' => 123,
            'product' => "test",
            'quote_action' => 'save',
            ['quote_items' => ['item_action' => 'save']]
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['coupon_code'],
            ['gtn']
        )->willReturnOnConsecutiveCalls(
            'UAT001',
            null
        );

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $exception = new NoSuchEntityException(__('Test Error Message'));
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())->method('build')->willReturn($arrRecipientsData);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(false);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn(static::WEBHOOK_URL);
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();
        $this->quote->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['fjmp_quote_id'],
                ['coupon_code'],
                ['gtn']
            )->willReturnOnConsecutiveCalls(
                '1234',
                'UAT001',
                '2010184916377923'
            );
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn(['1234', '1235']);
        $this->uploadToQuoteViewModel->expects($this->any())->method('isUploadToQuoteEnable')->willReturn(true);
        $this->uploadToQuoteViewModel->expects($this->any())->method('updateItemsSI')->willReturn($arrRecipientsData);
        $this->uploadToQuoteViewModel->expects($this->any())
            ->method('excludeDeletedItem')->willReturn([0 => $this->itemMock]);
        $this->uploadToQuoteViewModel->expects($this->any())
            ->method('updateItemsForFuse')->willReturn([0 => $this->itemMock]);
        $this->quote->expects($this->any())
            ->method('hasGtn')
            ->willReturn(true);

        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('2010184916377923');
        $this->quote->expects($this->any())
            ->method('setData')
            ->with('gtn')
            ->willReturn('2010184916377923');
        $this->quote->expects($this->any())->method('getData')->with('gtn')->willReturn('2010184916377923');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->with('2010184916377923')->willReturn(static::WEBHOOK_URL);


        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            null,
            $uploadToQuoteRequestWithChangeRequest
        ));
        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            null,
            $uploadToQuoteRequestWithDeletItem
        ));

        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null,
            null,
            $uploadToQuoteRequestWithDeletItem,
            $fuseRequest
        ));
    }

    /**
     * Test case for getFXORateQuoteWithrateApiProdRequestDataNull
     */
    public function testgetFXORateQuoteWithrateApiProdRequestDataNull()
    {
        $itemData = [
            'productAssociations' => [0=>'test'],
            'rateApiProdRequestData' => null
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['coupon_code'],
            ['gtn']
        )->willReturnOnConsecutiveCalls(
            'UAT001',
            null
        );

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $exception = new NoSuchEntityException(__('Test Error Message'));
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);
        $this->fxoRequestBuilder->expects($this->any())->method('getShipmentId')->willReturn('12');
        $this->inStoreRecipientsBuilder->expects($this->any())->method('build')->willReturn($arrRecipientsData);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();

        $this->assertNotNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        ));
    }

    /**
     * Test case for getFXORateQuoteWithNullItems
     */
    public function testgetFXORateQuoteWithNullItems()
    {
        $itemData = [
            'productAssociations' => 'test',
            'rateApiProdRequestData' => null
        ];
        $authenticationDetails = [
            'fedexAccountNumber' => '607876543',
            'site' => 'test',
            'siteName'=> 'Fedex',
            'gateWayToken' => 'test1',
            'accessToken' => 'test2'
        ];
        $arrRecipientsData = [
            'fedExAccountNumber' => '78656543',
            'arrRecipients' => [
                'fName' => 'Ayush'
            ]
        ];
        $this->fxoRequestBuilder->expects($this->any())->method('getAuthenticationDetails')
            ->willReturn($authenticationDetails);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['coupon_code'],
            ['gtn'],
        )->willReturnOnConsecutiveCalls(
            null,
            'UAT001',
            null
        );

        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $exception = new NoSuchEntityException(__('Test Error Message'));
        $this->cartIntegrationRepository->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->fxoModel->expects($this->any())->method('getDbItemsCount')->willReturn(123);
        $this->fXOProductDataModel->expects($this->any())->method('iterateItems')->willreturn($itemData);
        $this->fxoRequestBuilder->expects($this->any())->method('getPickShipData')->willreturn($arrRecipientsData);
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteObject')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductsData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSite')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setSiteName')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsGraphQlRequest')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteLocationId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setValidateContent')->willReturnSelf();
        $this->requestQueryValidator->expects($this->any())->method('isGraphQlRequest')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipients')->willReturnSelf();
        $this->fXORateQuoteDataArrayMock->expects($this->any())->method('getRateQuoteRequest')
            ->willReturn($this->dataString);
        $this->testcallRateQuoteApi();
        $this->assertNull($this->fXORateQuoteModel->getFXORateQuote(
            $this->quote,
            null
        ));
    }

    /**
     * Test method for manageCartWarnings
     * @return void
     */
    public function testManageCartWarnings()
    {
        $rateResponse = [
            'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
            'output' => [
                'alerts' => [
                    0 => [
                        'code' => 'ADDRESS_SERVICE_FAILURE'
                    ],
                    1 => [
                        'code' => 'MAX.PRODUCT.COUNT'
                    ]
                ]
            ],
        ];

        $this->assertNotNull($this->fXORateQuoteModel->manageCartWarnings($rateResponse));
    }

    /**
     * Test case for getDeliveryRatePrice
     */
    public function testGetDeliveryRatePrice()
    {
        $this->assertNotNull($this->fXORateQuoteModel->getDeliveryRatePrice(
            static::RATE_OUTPUT['output']['rateQuote']['rateQuoteDetails']
        ));
    }

    /**
     * Test case for calculateDeliveryLinePrice
     */
    public function testCalculateDeliveryLinePrice()
    {
        $this->assertNotNull($this->fXORateQuoteModel->calculateDeliveryLinePrice(
            static::RATE_OUTPUT['output']['rateQuote']['rateQuoteDetails'][1]
        ));
    }

    /**
     * Test case for calculateDeliveryLinePrice without shipping Account
     */
    public function testCalculateDeliveryLinePriceWithoutShippingAccount()
    {
        $rateQuoteOutput = [
            'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD',
                    'rateQuoteDetails' => [
                        0 => [
                            'deliveryLines' => [
                                0 => [
                                    'recipientReference' => '',
                                    'linePrice' => '$0.00',
                                    'lineType' => 'PACKING_AND_HANDLING',
                                    'deliveryLinePrice' => '$0.00',
                                    'deliveryLineType' => 'PACKING_AND_HANDLING',
                                    'priceable' => true,
                                    'deliveryRetailPrice' => '$0.00',
                                    'deliveryDiscountAmount' => '$0.00',
                                ],
                                1 => [
                                    'recipientReference' => "1",
                                    "estimatedDeliveryLocalTime" => "2023-05-20T23:59:00",
                                    "estimatedShipDate" => "2023-06-04",
                                    "priceable" => false,
                                    "deliveryLinePrice" => '0.01',
                                    "deliveryRetailPrice" => '9.99',
                                    "deliveryLineType" => "SHIPPING",
                                    "deliveryDiscountAmount" => 10,
                                ]
                            ],
                            'grossAmount' => '$20.58',
                            'totalDiscountAmount' => '($0.05)',
                            'netAmount' => '$20.53',
                            'taxableAmount' => '$20.53',
                            'taxAmount' => '$0.04',
                            'totalAmount' => '$20.57',
                            'estimatedVsActual' => 'ACTUAL'
                        ]
                    ]
                ]
            ]
        ];
        $this->assertNotNull($this->fXORateQuoteModel->calculateDeliveryLinePrice(
            $rateQuoteOutput['output']['rateQuote']['rateQuoteDetails'][0]
        ));
    }
}
