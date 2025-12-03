<?php

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Magento\Quote\Model\Quote;
use Fedex\FXOPricing\Model\FXOModel;
use Magento\Quote\Model\Quote\Item;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;
use Fedex\B2b\Model\Quote\Address;
use Fedex\ProductBundle\Api\ConfigInterface as ProductBundleConfigInterface;

class FXOModelTest extends TestCase
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     */
    protected $quoteItemCollectionFactory;
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;
    /**
     * @var \Magento\Quote\Model\Quote\Item
     */
    protected $item;
    /**
     * @var \Fedex\Cart\Helper\Data
     */
    protected $cartDataHelper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig
     */
    protected $toggleConfig;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Fedex\Cart\Api\CartIntegrationRepositoryInterface
     */
    protected $cartIntegrationRepositoryInterface;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var \Fedex\FXOPricing\Model\FXOModel
     */
    protected $fxoModel;
    /**
     * @var AddToCartPerformanceOptimizationToggle
     */
    protected $addToCartPerformanceOptimizationToggle;

    /**
     * @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $config;

    /**
     * JsonSerializer
     */
    protected $json;

    /**
     * ShippingRate
     */
    protected $shippingRate;

    /**
     * @var (ProductBundleConfigInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productBundleConfigInterface;

    /**
     * @var \Fedex\FXOPricing\Test\Unit\Model
     */
    protected $rateApiOutputdata = [
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => 0,
                                'productId' => '1508784838900',
                                'retailPrice' => '$0.49',
                                'discountAmount' => '$0.00',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.49',
                                'priceable' => 1,
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.49',
                                        'detailDiscountPrice' => '$0.00',
                                        'detailUnitPrice' => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.00',
                                    ],
                                ],
                                'productRetailPrice' => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice' => '0.49',
                                'editable' => '',
                            ],
                        ],
                        'grossAmount' => '$0.49',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.05)',
                                'type' => 'AR_CUSTOMERS',
                            ],
                        ],
                        'totalDiscountAmount' => '$0.00',
                        'deliveriesTotalAmount' => '$0.00',
                        'productsTotalAmount' => '$0.49',
                        'netAmount' => '$0.49',
                        'taxableAmount' => '$0.49',
                        'taxAmount' => '$0.00',
                        'totalAmount' => '$0.49',
                        'estimatedVsActual' => 'ACTUAL',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.05)',
                                'type' => 'AR_CUSTOMERS',
                            ],
                        ],
                    ],
                ],

            ],
        ],
    ];
     /**
      * @var \Fedex\FXOPricing\Test\Unit\Model
      */
    protected $rateApiOutputdataWithArCustomerDiscount = [
                'output' => [
                    'rateQuote' => [
                        'currency' => 'USD',
                        'rateQuoteDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => '1508784838900',
                                        'retailPrice' => '$0.49',
                                        'discountAmount' => '$0.00',
                                        'unitQuantity' => 1,
                                        'linePrice' => '$0.49',
                                        'priceable' => 1,
                                        'productLineDiscounts' => [
                                                            0=>[
                                                            'type' => 'AR_CUSTOMERS',
                                                            'amount' => "($45.00)"
                                                            ]
                                                        ],
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => 'Single Sided Color',
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => '$0.49',
                                                'detailDiscountPrice' => '$0.00',
                                                'detailUnitPrice' => '$0.4900',
                                                'detailDiscountedUnitPrice' => '$0.00',
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => '$0.49',
                                'discounts' => [
                                    0 => [
                                        'amount' => '($0.05)',
                                        'type' => 'COUPON',
                                    ],
                                ],
                                'totalDiscountAmount' => '$0.00',
                                'netAmount' => '$0.49',
                                'taxableAmount' => '$0.49',
                                'taxAmount' => '$0.00',
                                'totalAmount' => '$0.49',
                                'estimatedVsActual' => 'ACTUAL',
                                'discounts' => [
                                    0 => [
                                        'amount' => '($0.05)',
                                        'type' => 'COUPON',
                                    ],
                                ],
                            ],
                        ],

                    ],
                ],
            ];
     /**
      * @var \Fedex\FXOPricing\Test\Unit\Model
      */
    protected $rateApiOutputdataWithCorporateDiscount = [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD',
                    'rateQuoteDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId' => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDiscounts' => [0=>['type' => 'CORPORATE', 'amount' => "($23.00)"]],
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'COUPON',
                                ],
                            ],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'COUPON',
                                ],
                            ],
                        ],
                    ],

                ],
            ],
        ];
     /**
      * @var \Fedex\FXOPricing\Test\Unit\Model
      */
    protected $rateApiOutputdataWithCouponDiscount = [
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => 0,
                                'productId' => '1508784838900',
                                'retailPrice' => '$0.49',
                                'discountAmount' => '$0.00',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.49',
                                'priceable' => 1,
                                'productLineDiscounts' => [0=>['type' => 'COUPON', 'amount' => "($25.00)"]],
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.49',
                                        'detailDiscountPrice' => '$0.00',
                                        'detailUnitPrice' => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.00',
                                    ],
                                ],
                                'productRetailPrice' => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice' => '0.49',
                                'editable' => '',
                            ],
                        ],
                        'grossAmount' => '$0.49',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.05)',
                                'type' => 'COUPON',
                            ],
                        ],
                        'totalDiscountAmount' => '$0.00',
                        'netAmount' => '$0.49',
                        'taxableAmount' => '$0.49',
                        'taxAmount' => '$0.00',
                        'totalAmount' => '$0.49',
                        'estimatedVsActual' => 'ACTUAL',
                        'discounts' => [
                            0 => [
                                'amount' => '($0.05)',
                                'type' => 'COUPON',
                            ],
                        ],
                    ],
                ],

            ],
        ],
    ];
    /**
     * @var \Fedex\FXOPricing\Test\Unit\Model
     */
    protected $rateApiOutputdataWithQuantityDiscount = [
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD',
                    'rateQuoteDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId' => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDiscounts' => [0=>['type' => 'QUANTITY', 'amount' => "($35.00)"]],
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'COUPON',
                                ],
                            ],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'COUPON',
                                ],
                            ],
                        ],
                    ],

                ],
            ],
        ];
    /**
     * @var \Fedex\FXOPricing\Test\Unit\Model
     */
    protected $output = [
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'productLines' => [
                            0 => [
                                'instanceId' => 0,
                                'productId' => '1508784838900',
                                'retailPrice' => '$0.49',
                                'discountAmount' => '$0.00',
                                'unitQuantity' => 1,
                                'linePrice' => '$0.49',
                                'priceable' => 1,
                                'productLineDiscounts' => [[
                                            "type"=> "AR_CUSTOMERS",
                                            "amount"=> "($52.50)"
                                        ], [
                                            "type"=> "QUANTITY",
                                            "amount"=> "($250.00)"
                                        ]],
                                'productLineDetails' => [
                                    0 => [
                                        'detailCode' => '0173',
                                        'description' => 'Single Sided Color',
                                        'detailCategory' => 'PRINTING',
                                        'unitQuantity' => 1,
                                        'unitOfMeasurement' => 'EACH',
                                        'detailPrice' => '$0.49',
                                        'detailDiscountPrice' => '$0.00',
                                        'detailUnitPrice' => '$0.4900',
                                        'detailDiscountedUnitPrice' => '$0.00',
                                    ],
                                ],
                                'productRetailPrice' => 0.49,
                                'productDiscountAmount' => '0.00',
                                'productLinePrice' => '0.49',
                                'editable' => '',
                            ],
                        ],
                        'grossAmount' => '$0.49',
                        'discounts' => [
                                0 => [
                                'amount' => '($0.05)',
                                'type' => 'AR_CUSTOMERS',
                                 ],
                        ],
                        'totalDiscountAmount' => '$0.00',
                        'netAmount' => '$0.49',
                        'taxableAmount' => '$0.49',
                        'taxAmount' => '$0.00',
                        'totalAmount' => '$0.49',
                        'estimatedVsActual' => 'ACTUAL',
                    ],
                ],
            ],
            'alerts' => [
                '0' => [
                    'code' => 'ABC',
                    'message' => 'Error'
                ],
            ],
        ],
    ];

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cart = $this->getMockBuilder(Cart::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(['setFedexAccountWarning', 'setPromoErrorMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create', 'addFieldToSelect', 'addFieldToFilter', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods([
                'getId',
                'getAllVisibleItems',
                'deleteItem',
                'getData',
                'setData',
                'getCouponCode',
                'setDiscount',
                'setCouponCode',
                'setSubtotal',
                'setBaseSubtotal',
                'setGrandTotal',
                'setBaseGrandTotal',
                'save',
                'setFedexAccountNumber',
                'setTaxAmount',
                'setCustomTaxAmount',
                'getShippingAddress'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomPrice','setVolumeDiscount','save'])
            ->getMock();
        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData', 'formatPrice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCouponDiscountExist',
                'setWarningMessageFlag',
                'unsCouponDiscountExist',
                'getAccountDiscountExist',
                'unsAccountDiscountExist'
            ])
            ->getMock();
        $this->cartIntegrationRepositoryInterface = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByQuoteId', 'save'])
            ->getMockForAbstractClass();

        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isEnabledCartPricingFix'])
            ->getMockForAbstractClass();

        $this->addToCartPerformanceOptimizationToggle = $this->getMockBuilder(AddToCartPerformanceOptimizationToggle::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isActive'])
            ->getMock();

        $this->json = $this->getMockBuilder(JsonSerializer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['serialize', 'unserialize'])
            ->getMockForAbstractClass();

        $this->shippingRate = $this->getMockBuilder(ShippingRate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['collect'])
            ->getMock();

        $this->productBundleConfigInterface = $this->getMockBuilder(ProductBundleConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->fxoModel = $this->objectManager->getObject(
            FXOModel::class,
            [
                'cart' => $this->cart,
                'customerSession' => $this->customerSession,
                'quoteItemCollectionFactory' => $this->quoteItemCollectionFactory,
                'logger' => $this->logger,
                'checkoutSession' => $this->checkoutSession,
                'addToCartPerformanceOptimizationToggle' => $this->addToCartPerformanceOptimizationToggle,
                'config' => $this->config,
                'toggleConfig' => $this->toggleConfig,
                'json' => $this->json,
                'shippingRate' => $this->shippingRate,
                'cartIntegrationRepository' => $this->cartIntegrationRepositoryInterface,
                'productBundleConfigInterface' => $this->productBundleConfigInterface
            ]
        );
    }

    /**
     * Test Case for getDbItemsCount
     */
    public function testGetDbItemsCountIfCase()
    {
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->addToCartPerformanceOptimizationToggle->expects($this->any())->method('isActive')
            ->willReturn(true);
        $this->quoteItemCollectionFactory->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->quoteItemCollectionFactory->expects($this->any())->method('getSize')->willReturn(10);
        $this->assertEquals(10, $this->fxoModel->getDbItemsCount($this->quote));
    }

    /**
     * Test Case for getDbItemsCount
     */
    public function testGetDbItemsCount()
    {
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->quoteItemCollectionFactory->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->quoteItemCollectionFactory->expects($this->any())->method('getSize')->willReturn(10);
        $this->assertEquals(10, $this->fxoModel->getDbItemsCount($this->quote));
    }

    /**
     * Test case for removeQuoteItem
     */
    public function testRemoveQuoteItem()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getCustomPrice')->WillReturn(null);
        $this->quote->expects($this->any())->method('deleteItem')->willReturn($this);
        $this->cart->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull($this->fxoModel->removeQuoteItem($this->quote));
    }

    /**
     * Test case for removeReorderQuoteItem
     */
    public function testRemoveReorderQuoteItem()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getCustomPrice')->WillReturn(null);
        $this->quote->expects($this->any())->method('deleteItem')->willReturn($this);
        $this->cart->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNotNull($this->fxoModel->removeReorderQuoteItem($this->quote));
    }

    /**
     * Test case for removePromoCode
     */
    public function testRemovePromoCodeWithInvalid()
    {
        $rateResponse = [
          'alerts' => [
            0 => [
              'code' => 'RAQ.SERVICE.11'
            ]
          ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->customerSession->expects($this->any())->method('setPromoErrorMessage')->willReturn('aaa');
        $this->assertNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code for pruchase required
     * @return void
     */
    public function testRemovePromoCodeWithMinimumPurchaseRequired()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                'code' => 'MINIMUM.PURCHASE.REQUIRED'
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code invalid
     * @return void
     */
    public function testRemovePromoCodeWithInvalidProductCode()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'INVALID.PRODUCT.CODE',
                    'message' => 'promo code is invalid'
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code for coupon expire
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponExpired()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.EXPIRED'
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemed()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => 'COUPONS.CODE.REDEEMED'
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedAlert()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => 'COUPONS.CODE.REDEEMED'
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponInvalid()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => 'COUPONS.CODE.INVALID'
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedWithFalse()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => 'COUPONS.CODE.REDEEMED'
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote, false));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedWithNull()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => null
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn("UAT001");
        $this->assertNull($this->fxoModel->removePromoCode($rateResponse, $this->quote, false));
    }

    /**
     * Test case for removePromoCode
     */
    public function testRemovePromoCodeWithInvalidWithAlert()
    {
        $rateResponse = [
          'alerts' => [
            0 => [
              'code' => 'RAQ.SERVICE.11'
            ]
          ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->customerSession->expects($this->any())->method('setPromoErrorMessage')->willReturn('aaa');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code for pruchase required
     * @return void
     */
    public function testRemovePromoCodeWithMinimumPurchaseRequiredWithAlert()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                'code' => 'MINIMUM.PURCHASE.REQUIRED'
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code invalid
     * @return void
     */
    public function testRemovePromoCodeWithInvalidProductCodeWithAlert()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'INVALID.PRODUCT.CODE',
                    'message' => 'promo code is invalid'
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code for coupon expire
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponExpiredWithAlert()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.EXPIRED'
                ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedWithAlert()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => 'COUPONS.CODE.REDEEMED'
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponInvalidWithAlert()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => 'COUPONS.CODE.INVALID'
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->fxoModel->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedNull()
    {
        $rateResponse = [
            'alerts' => [
            0 => [
                'code' => null
            ]
            ]
        ];
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn("UAT001");
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNull($this->fxoModel->removePromoCode($rateResponse, $this->quote, false));
    }

    /**
     * Test case for resetCartDiscounts
     */
    public function testResetCartDiscounts()
    {
        $this->checkoutSession->expects($this->any())->method('getCouponDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setWarningMessageFlag')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCouponDiscountExist')->willReturnSelf();
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNull($this->fxoModel->resetCartDiscounts(
            $this->quote,
            $this->rateApiOutputdata,
            $this->cartDataHelper
        ));
    }

    /**
     * Test case for resetCartDiscountsWithCouponDiscount
     */
    public function testResetCartDiscountsWithCouponDiscount()
    {
        $this->checkoutSession->expects($this->any())->method('getAccountDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setWarningMessageFlag')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAccountDiscountExist')->willReturnSelf();

        $this->assertNull($this->fxoModel->resetCartDiscounts(
            $this->quote,
            $this->rateApiOutputdataWithCouponDiscount,
            $this->cartDataHelper
        ));
    }

    /**
     * Test case for ResetCartDiscountsWithAccountDiscount
     */
    public function testResetCartDiscountsWithAccountDiscount()
    {
        $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']['rateQuoteDetails'][0]['discounts'][0]['type'] =
            'CORPORATE';

        $this->checkoutSession->expects($this->any())->method('getAccountDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setWarningMessageFlag')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAccountDiscountExist')->willReturnSelf();

        $this->assertNull($this->fxoModel->resetCartDiscounts(
            $this->quote,
            $this->rateApiOutputdataWithCouponDiscount,
            $this->cartDataHelper
        ));
    }

    /**
     * Test case for updateQuoteDiscount
     */
    public function testUpdateQuoteDiscount()
    {
        $this->toggleConfig->expects($this->any())
                                ->method('getToggleConfigValue')
                                ->willReturn(true);
        $this->cartDataHelper->expects($this->any())->method('formatPrice')->willReturn(12.2);
        $this->quote->expects($this->any())->method('setDiscount')->willReturn($this);
        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setSubtotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setBaseSubtotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setGrandTotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setBaseGrandTotal')->willReturn($this);
        $this->quote->expects($this->any())->method('save')->willReturn($this->quote);
        $this->assertNotNull($this->fxoModel->updateQuoteDiscount(
            $this->quote,
            $this->rateApiOutputdata,
            'UAT001',
            $this->cartDataHelper
        ));
    }

    /**
     * Test case for updateQuoteDiscount
     */
    public function testUpdateQuoteDiscountPriceWithRaq()
    {
        $this->toggleConfig->expects($this->any())
                                ->method('getToggleConfigValue')
                                ->willReturn(true);
        $this->cartDataHelper->expects($this->any())->method('formatPrice')->willReturn(12.2);
        $this->quote->expects($this->any())->method('setDiscount')->willReturn($this);
        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setSubtotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setBaseSubtotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setGrandTotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setBaseGrandTotal')->willReturn($this);
        $this->quote->expects($this->once())->method('setTaxAmount')->willReturn($this);
        $this->quote->expects($this->once())->method('setCustomTaxAmount')->willReturn($this);
        $cartIntegrationItemInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRaqNetAmount'])
            ->getMockForAbstractClass();
        $cartIntegrationItemInterface->expects($this->once())->method('setRaqNetAmount');
        $this->quote->expects($this->once())->method('getId')->willReturn('8');
        $this->cartIntegrationRepositoryInterface->expects($this->once())->method('getByQuoteId')
            ->willReturn($cartIntegrationItemInterface);
        $this->cartIntegrationRepositoryInterface->method('save')
            ->willReturnOnConsecutiveCalls( $cartIntegrationItemInterface,  $cartIntegrationItemInterface);
        //$this->quote->expects($this->any())->method('save')->willReturn($this->quote);
        $this->config->expects($this->any())->method('isEnabledCartPricingFix')->willReturn(true);
        $cartIntegrationItemInterface->expects($this->any())->method('getDeliveryData')
            ->willReturn("{'shipping_price': '$0.0'}");
        $this->json->expects($this->any())->method('unserialize')->willReturn(['shipping_price'=> '$0.0']);
        $this->json->expects($this->any())->method('serialize')->willReturn("{'shipping_price': '$0.0'}");
        $cartIntegrationItemInterface->expects($this->any())->method('setDeliveryData')
            ->willReturnSelf();
        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $this->shippingRate->expects($this->any())->method('collect')->willReturnself();
        $this->assertNotNull($this->fxoModel->updateQuoteDiscount(
            $this->quote,
            $this->rateApiOutputdata,
            'UAT001',
            $this->cartDataHelper,
            true
        ));
    }

    /**
     * Test case for updateQuoteDiscount
     */
    public function testUpdateQuoteDiscountPriceWithRaqCase2()
    {
        $this->rateApiOutputdata['output']['rateQuote']['rateQuoteDetails'][0]['productsSubTotalAmount'] = '$0.49';
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->cartDataHelper->expects($this->any())->method('formatPrice')->willReturn(12.2);
        $this->quote->expects($this->any())->method('setDiscount')->willReturn($this);
        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setSubtotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setBaseSubtotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setGrandTotal')->willReturn($this);
        $this->quote->expects($this->any())->method('setBaseGrandTotal')->willReturn($this);
        $this->quote->expects($this->once())->method('setTaxAmount')->willReturn($this);
        $this->quote->expects($this->once())->method('setCustomTaxAmount')->willReturn($this);
        $cartIntegrationItemInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setRaqNetAmount'])
            ->getMockForAbstractClass();
        $cartIntegrationItemInterface->expects($this->once())->method('setRaqNetAmount');
        $this->quote->expects($this->once())->method('getId')->willReturn('8');
        $this->cartIntegrationRepositoryInterface->expects($this->once())->method('getByQuoteId')
            ->willReturn($cartIntegrationItemInterface);
        $this->cartIntegrationRepositoryInterface->method('save')
            ->willReturnOnConsecutiveCalls( $cartIntegrationItemInterface,  $cartIntegrationItemInterface);
        //$this->quote->expects($this->any())->method('save')->willReturn($this->quote);
        $this->config->expects($this->any())->method('isEnabledCartPricingFix')->willReturn(true);
        $cartIntegrationItemInterface->expects($this->any())->method('getDeliveryData')
            ->willReturn("{'shipping_price': '$0.0'}");
        $this->json->expects($this->any())->method('unserialize')->willReturn(['shipping_price'=> '$0.0']);
        $this->json->expects($this->any())->method('serialize')->willReturn("{'shipping_price': '$0.0'}");
        $cartIntegrationItemInterface->expects($this->any())->method('setDeliveryData')
            ->willReturnSelf();
        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $this->shippingRate->expects($this->any())->method('collect')->willReturnself();
        $this->assertNotNull($this->fxoModel->updateQuoteDiscount(
            $this->quote,
            $this->rateApiOutputdata,
            'UAT001',
            $this->cartDataHelper,
            true
        ));
    }

    /**
     * Test case for updateQuoteDiscount
     */
    public function testUpdateQuoteDiscountPriceWithoutIntegration()
    {
        $this->toggleConfig->expects($this->any())
                                ->method('getToggleConfigValue')
                                ->willReturn(true);
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->cartIntegrationRepositoryInterface->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);
        $this->logger->expects($this->any())
            ->method('error');

        $this->assertTrue($this->fxoModel->updateQuoteDiscount(
            $this->quote,
            $this->rateApiOutputdata,
            'UAT001',
            $this->cartDataHelper,
            true,
            false
        ));
    }

    /**
     * Test case for saveDiscountBreakdown
     */
    public function testSaveDiscountBreakdownEmpty()
    {
        $result = $this->fxoModel->saveDiscountBreakdown($this->quote, []);
            $this->toggleConfig->expects($this->any())
                        ->method('getToggleConfigValue')
                        ->willReturn(true);
            $this->assertNotNull($this->fxoModel->saveDiscountBreakdown($this->quote, []));
    }

    /**
     * Test case for saveDiscountBreakdownWithCoupon
     */
    public function testSaveDiscountBreakdownWithCoupon()
    {
        $resCoupon = $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithCouponDiscount);
                $this->toggleConfig->expects($this->any())
                            ->method('getToggleConfigValue')
                            ->willReturn(true);
                $this->assertNotNull(
                    $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithCouponDiscount)
                );

                $this->assertEquals(
                    'COUPON',
                    $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']
                    ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['type']
                );
                $this->assertEquals(
                    '($25.00)',
                    $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']
                    ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['amount']
                );
    }

    /**
     * Test case for saveDiscountBreakdownWithQuantity
     */
    public function testSaveDiscountBreakdownWithQty()
    {
        $resultQty = $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithQuantityDiscount);
            $this->toggleConfig->expects($this->any())
                    ->method('getToggleConfigValue')
                    ->willReturn(true);
                  $this->assertNotNull(
                      $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithQuantityDiscount)
                  );
                  $this->assertEquals(
                      'QUANTITY',
                      $this->rateApiOutputdataWithQuantityDiscount['output']['rateQuote']
                      ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['type']
                  );
                  $this->assertEquals(
                      '($35.00)',
                      $this->rateApiOutputdataWithQuantityDiscount['output']['rateQuote']
                      ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['amount']
                  );
    }

    /**
     * Test case for saveDiscountBreakdownWithQuantity
     */
    public function testSaveDiscountBreakdownWithQtyCase2()
    {
        $this->rateApiOutputdataWithQuantityDiscount['output']['rateQuote']
        ['rateQuoteDetails'][0]['discounts'][0]['type'] = 'QUANTITY';
        $resultQty = $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithQuantityDiscount);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->assertNotNull(
            $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithQuantityDiscount)
        );
        $this->assertEquals(
            'QUANTITY',
            $this->rateApiOutputdataWithQuantityDiscount['output']['rateQuote']
            ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['type']
        );
        $this->assertEquals(
            '($35.00)',
            $this->rateApiOutputdataWithQuantityDiscount['output']['rateQuote']
            ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['amount']
        );
    }

    /**
     * Test case for saveDiscountBreakdownWithCorporate
     */
    public function testSaveDiscountBreakdownWithCorporate()
    {
        $resultCorp = $this->fxoModel->saveDiscountBreakdown(
            $this->quote,
            $this->rateApiOutputdataWithCorporateDiscount
        );
            $this->toggleConfig->expects($this->any())
                    ->method('getToggleConfigValue')
                    ->willReturn(true);
              $this->assertNotNull(
                  $this->fxoModel->saveDiscountBreakdown($this->quote, $this->rateApiOutputdataWithCorporateDiscount)
              );
              $this->assertEquals(
                  'CORPORATE',
                  $this->rateApiOutputdataWithCorporateDiscount['output']['rateQuote']
                  ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['type']
              );
              $this->assertEquals(
                  '($23.00)',
                  $this->rateApiOutputdataWithCorporateDiscount['output']['rateQuote']
                  ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['amount']
              );
    }

    /**
     * Test case for SaveDiscountBreakdownWithArCustomers
     */
    public function testSaveDiscountBreakdownWithArCustomers()
    {
        $resultCustomer = $this->fxoModel->saveDiscountBreakdown(
            $this->quote,
            $this->rateApiOutputdataWithArCustomerDiscount
        );
            $this->toggleConfig->expects($this->any())
                    ->method('getToggleConfigValue')
                    ->willReturn(true);
              $this->assertNotNull($this->fxoModel->saveDiscountBreakdown(
                  $this->quote,
                  $this->rateApiOutputdataWithArCustomerDiscount
              ));
              $this->assertEquals(
                  'AR_CUSTOMERS',
                  $this->rateApiOutputdataWithArCustomerDiscount['output']['rateQuote']
                  ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['type']
              );
              $this->assertEquals(
                  '($45.00)',
                  $this->rateApiOutputdataWithArCustomerDiscount['output']['rateQuote']
                  ['rateQuoteDetails'][0]['productLines'][0]['productLineDiscounts'][0]['amount']
              );
    }

    /**
     * Test case for saveDiscountBreakdownWithQauntity
     */
    public function testSaveDiscountBreakdownWithDeliveryLinesDiscount()
    {
        $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']
        ['rateQuoteDetails'][0]['deliveryLines'][1]['deliveryLineDiscounts'][0]
        = [
            'amount' => '($0.05)',
            'type' => 'COUPON'
        ];
        $this->toggleConfig->expects($this->any())
                    ->method('getToggleConfigValue')
                    ->willReturn(true);
        $this->assertNotNull($this->fxoModel->saveDiscountBreakdown(
            $this->quote,
            $this->rateApiOutputdataWithCouponDiscount
        ));
    }

   /**
    * Test case for saveDiscountBreakdownWithQauntity
    */
    public function testSaveDiscountBreakdownWithDeliveryLinesDiscountasFloat()
    {
        $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']
        ['rateQuoteDetails'][0]['deliveryLines'][1]['deliveryLineDiscounts'][0]
        = [
           'amount' => 5.55,
           'type' => 'COUPON'
        ];
        $this->toggleConfig->expects($this->any())
                    ->method('getToggleConfigValue')
                    ->willReturn(true);
        $this->assertNotNull($this->fxoModel->saveDiscountBreakdown(
            $this->quote,
            $this->rateApiOutputdataWithCouponDiscount
        ));
    }
    /**
     * Test case for SaveDiscountBreakdownWithCustomer
     */
    public function testSaveDiscountBreakdownWithCustomer()
    {
        $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']['rateQuoteDetails'][0]['discounts'][0]
        = [
            'amount' => '($0.06)',
            'type' => 'AR_CUSTOMERS'
        ];
        $this->toggleConfig->expects($this->any())
                    ->method('getToggleConfigValue')
                    ->willReturn(true);
        $this->assertNotNull($this->fxoModel->saveDiscountBreakdown(
            $this->quote,
            $this->rateApiOutputdataWithCouponDiscount
        ));
    }

    /**
     * Test case for SaveDiscountBreakdownWithCustomer
     */
    public function testSaveDiscountBreakdownWithCustomerCase2()
    {
        $this->rateApiOutputdataWithCouponDiscount['output']['rateQuote']['rateQuoteDetails'][0]['discounts'][0]
            = [
            'amount' => '($0.06)',
            'type' => 'AR_CUSTOMERS'
        ];
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->assertNotNull($this->fxoModel->saveDiscountBreakdown(
            $this->quote,
            $this->rateApiOutputdataWithCouponDiscount
        ));
    }

    /**
     * Test case for isVolumeDiscountAppliedonItem
     */
    public function testIsVolumeDiscountAppliedonItem()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('setVolumeDiscount')->willReturn($this);
        $this->item->expects($this->any())->method('save')->willReturn($this->item);
        $this->assertNotNull($this->fxoModel->isVolumeDiscountAppliedonItem($this->quote, $this->output));
    }

    /**
     * Test case for isVolumeDiscountAppliedonItemWithNoLineItems
     */
    public function testIsVolumeDiscountAppliedonItemWithNoLineItems()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('setVolumeDiscount')->willReturn($this);
        $this->item->expects($this->any())->method('save')->willReturn($this->item);
        $this->assertNotNull(
            $this->fxoModel->isVolumeDiscountAppliedonItem($this->quote, $this->rateApiOutputdataWithCouponDiscount)
        );
    }

    /**
     * Test case for testHandlePromoAccountWarnings
     */
    public function testHandlePromoAccountWarnings()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'COUPONS.CODE.INVALID'
                ]
            ]
        ];

        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->fxoModel->handlePromoAccountWarnings($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testHandlePromoAccountWarnings
     */
    public function testHandlePromoAccountWarnings1()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED'
                ]
            ]
        ];

        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->fxoModel->handlePromoAccountWarnings($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testHandlePromoAccountWarnings
     */
    public function testHandlePromoAccountWarnings2()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'INVALID.PRODUCT.CODE',
                    'message' => 'Invalid product code.'
                ]
            ]
        ];

        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->fxoModel->handlePromoAccountWarnings($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testHandlePromoAccountWarnings
     */
    public function testHandlePromoAccountWarnings3()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'COUPONS.CODE.EXPIRED'
                ]
            ]
        ];

        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->fxoModel->handlePromoAccountWarnings($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testHandlePromoAccountWarnings
     */
    public function testHandlePromoAccountWarnings4()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'COUPONS.CODE.REDEEMED'
                ]
            ]
        ];

        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->fxoModel->handlePromoAccountWarnings($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testHandlePromoAccountWarnings
     */
    public function testHandlePromoAccountWarnings5()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'RATEREQUEST.FEDEXACCOUNTNUMBER.INVALID'
                ]
            ]
        ];

        $this->quote->expects($this->any())->method('setCouponCode')->willReturn($this);
        $this->quote->expects($this->any())->method('setFedexAccountNumber')->willReturnSelf();
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->assertNull(
            $this->fxoModel->handlePromoAccountWarnings($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testpromoCodeWarningOldHandling
     */
    public function testpromoCodeWarningOldHandling()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'COUPONS.CODE.INVALID'
                ]
            ]
        ];
        $this->assertNotNull(
            $this->fxoModel->promoCodeWarningOldHandling($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testpromoCodeWarningOldHandlingOne
     */
    public function testpromoCodeWarningOldHandlingOne()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED'
                ]
            ]
        ];
        $this->assertNotNull(
            $this->fxoModel->promoCodeWarningOldHandling($this->quote, $fxoRateResponse)
        );
    }

   /**
    * Test case for testpromoCodeWarningOldHandlingTwo
    */
    public function testpromoCodeWarningOldHandlingTwo()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'INVALID.PRODUCT.CODE',
                    'message' => ' Invalid product code.'
                ]
            ]
        ];
        $this->assertNotNull(
            $this->fxoModel->promoCodeWarningOldHandling($this->quote, $fxoRateResponse)
        );
    }

  /**
   * Test case for testpromoCodeWarningOldHandlingThree
   */
    public function testpromoCodeWarningOldHandlingThree()
    {
        $fxoRateResponse = [
        'alerts' => [
            [
                'code' => 'COUPONS.CODE.EXPIRED'
            ]
        ]
        ];
        $this->assertNotNull(
            $this->fxoModel->promoCodeWarningOldHandling($this->quote, $fxoRateResponse)
        );
    }
    /**
     * Test case for testpromoCodeWarningOldHandlingFour
     */
    public function testpromoCodeWarningOldHandlingFour()
    {
        $fxoRateResponse = [
            'alerts' => [
                [
                    'code' => 'COUPONS.CODE.REDEEMED'
                ]
            ]
        ];
        $this->assertNotNull(
            $this->fxoModel->promoCodeWarningOldHandling($this->quote, $fxoRateResponse)
        );
    }
    /**
     * Test case for testcheckErrorsAndRemoveFedexAccount
     */
    public function testcheckErrorsAndRemoveFedexAccount()
    {
        $fxoRateResponse = [
            'errors' => [
                [
                    'code' => 'INTERNAL.SERVER.FAILURE'
                ],
            ]
        ];
        $this->assertNull(
            $this->fxoModel->checkErrorsAndRemoveFedexAccount($this->quote, $fxoRateResponse)
        );
    }

    /**
     * Test case for testcheckErrorsAndRemoveDiscounts
     */
    public function testcheckErrorsAndRemoveDiscounts()
    {
        $this->rateApiOutputdata['output']['rateQuote']['rateQuoteDetails'][0]['discounts'][0]['type'] =
            'COUPON';
        $this->checkoutSession->expects($this->any())->method('getAccountDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('getCouponDiscountExist')->willReturn(false);
        $this->checkoutSession->expects($this->any())->method('setWarningMessageFlag')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAccountDiscountExist')->willReturnSelf();

         $this->assertNull(
             $this->fxoModel->checkErrorsAndRemoveDiscounts(
                 $this->quote,
                 $this->rateApiOutputdata,
                 $this->cartDataHelper
             )
         );
    }

    public function testSaveVolumeDiscountQuoteItemToggleOff()
    {
        $this->productBundleConfigInterface
            ->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $child1 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId'])
            ->getMock();
        $child2 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId'])
            ->getMock();

        $child1->expects($this->once())->method('getItemId')->willReturn(101);
        $child2->expects($this->once())->method('getItemId')->willReturn(102);

        $parent = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType','getParentItem','getChildren','setData','save'])
            ->getMock();

        $parent->expects($this->once())->method('getProductType')->willReturn('bundle');
        $parent->expects($this->once())->method('getParentItem')->willReturn(null);
        $parent->expects($this->once())->method('getChildren')->willReturn([$child1, $child2]);

        $productDiscountAmount = [
            101 => [3, 2],
        ];

        $parent->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['bundle_discount', 5.0],
                ['volume_discount', 0.0]
            )
            ->willReturnSelf();

        $parent->expects($this->once())->method('save')->willReturnSelf();

        $this->quote->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$parent]);

        $this->assertTrue($this->fxoModel->saveVolumeDiscountQuoteItem($this->quote, $productDiscountAmount));
    }

    public function testSaveVolumeDiscountQuoteItemToggleOn()
    {
        $this->productBundleConfigInterface
            ->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $child1 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId'])
            ->getMock();
        $child2 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItemId'])
            ->getMock();

        $child1->expects($this->once())->method('getItemId')->willReturn(101);
        $child2->expects($this->once())->method('getItemId')->willReturn(102);

        $parent = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType','getParentItem','getChildren','setData','save'])
            ->getMock();

        $parent->expects($this->once())->method('getProductType')->willReturn('bundle');
        $parent->expects($this->once())->method('getParentItem')->willReturn(null);
        $parent->expects($this->once())->method('getChildren')->willReturn([$child1, $child2]);

        $productDiscountAmount = [
            101 => [3, 2],
        ];

        $parent->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['bundle_discount', 5.0],
                ['volume_discount', 0.0]
            )
            ->willReturnSelf();

        $parent->expects($this->once())->method('save')->willReturnSelf();

        $this->quote->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$parent]);

        $this->assertTrue($this->fxoModel->saveVolumeDiscountQuoteItem($this->quote, $productDiscountAmount));
    }

    public function testSaveVolumeDiscountQuoteItemToggleOnNonBundleItem()
    {
        $this->productBundleConfigInterface
            ->expects($this->once())
            ->method('isTigerE468338ToggleEnabled')
            ->willReturn(true);

        $simple = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType','getItemId','setData','save'])
            ->getMock();

        $simple->expects($this->once())->method('getProductType')->willReturn('simple');
        $simple->expects($this->once())->method('getItemId')->willReturn(555);

        $productDiscountAmount = [
            555 => [1.2]
        ];

        $simple->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['volume_discount', 1.2],
                ['bundle_discount', 0.0]
            )
            ->willReturnSelf();

        $simple->expects($this->once())->method('save')->willReturnSelf();

        $this->quote->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn([$simple]);

        $this->assertTrue($this->fxoModel->saveVolumeDiscountQuoteItem($this->quote, $productDiscountAmount));
    }

    /**
     * Test case for testcheckErrorsAndRemoveDiscounts
     */
    public function testcheckErrorsAndAccountDiscounts()
    {
        $this->checkoutSession->expects($this->any())->method('getCouponDiscountExist')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('setWarningMessageFlag')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAccountDiscountExist')->willReturnSelf();

        $this->assertNull(
            $this->fxoModel->checkErrorsAndRemoveDiscounts(
                $this->quote,
                $this->rateApiOutputdata,
                $this->cartDataHelper
            )
        );
    }
}
