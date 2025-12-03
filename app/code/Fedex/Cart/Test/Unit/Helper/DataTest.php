<?php
namespace Fedex\Cart\Test\Unit\Helper;

use Fedex\Cart\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Fedex\EnhancedProfile\Helper\Account;

class DataTest extends TestCase
{
    protected $deliveryHelper;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterface;
    protected $encryptor;
    protected $enhancedProfile;
    protected $item;
    protected $product;
    protected $accountHelper;
    protected $data;
    protected $output = [
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
                            ],
                            1 => [
                                'recipientReference' => '',
                                'linePrice' => '$19.99',
                                'estimatedDeliveryLocalTime' => '2021-06-22T12:00:00',
                                'estimatedShipDate' => '2021-06-21',
                                'lineType' => 'SHIPPING',
                                'deliveryLinePrice' => '$19.99',
                                'deliveryLineType' => 'SHIPPING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$19.99',
                                'deliveryDiscountAmount' => '$0.00',
                            ],
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
                ],
            ],
        ],
    ];
    protected $outputWithRateQuoteId = [
        'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
        'output' => [
            'rateQuote' => [
                'currency' => 'USD',
                'rateQuoteDetails' => [
                    0 => [
                        'rateQuoteId' => 122,
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
                            ],
                            1 => [
                                'recipientReference' => '',
                                'linePrice' => '$19.99',
                                'estimatedDeliveryLocalTime' => '2021-06-22T12:00:00',
                                'estimatedShipDate' => '2021-06-21',
                                'lineType' => 'SHIPPING',
                                'deliveryLinePrice' => '$19.99',
                                'deliveryLineType' => 'SHIPPING',
                                'priceable' => true,
                                'deliveryRetailPrice' => '$19.99',
                                'deliveryDiscountAmount' => '$0.00',
                            ],
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
                ],
            ],
        ],
    ];
    protected const IS_COMMERCIAL_CUSTOMER = 'isCommercialCustomer';
    protected const GET_VALUE = 'getValue';

    /**
     * @var SsoConfiguration|MockObject
     */
    protected $ssoConfiguration;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSession;

    /**
     * @var Quote|MockObject
     */
    protected $quoteMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var CheckoutSession|MockObject
     */
    protected $checkoutSession;

    protected function setUp(): void
    {
        $this->deliveryHelper = $this
            ->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->setMethods([self::IS_COMMERCIAL_CUSTOMER], 'isCommercialCustomer')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configInterface = $this
            ->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->setMethods([self::GET_VALUE])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->encryptor = $this
            ->getMockBuilder(EncryptorInterface::class)
            ->setMethods(['encrypt', 'decrypt'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->ssoConfiguration = $this
            ->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['isFclCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this
            ->getMockBuilder(CustomerSession::class)
            ->setMethods(['getProfileSession'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->enhancedProfile = $this
            ->getMockBuilder(EnhancedProfile::class)
            ->setMethods(['getAccountSummary', 'isQuotePriceableDisable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = $this
            ->getMockBuilder(Item::class)
            ->setMethods(['getItemId', 'getQty', 'getMiraklOfferId', 'getAdditionalData', 'getProduct'])
            ->disableOriginalConstructor()->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setData', 'save'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoveFedexAccountNumber', 'getAppliedFedexAccNumber', 'setAddressClassification', 'getAddressClassification'])
            ->getMock();
        $this->product = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct','getId','getData'])
            ->getMock();

        $this->accountHelper = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->setMethods(['getActivePersonalAccountList','getActiveCompanyAccountList'])
            ->getMock();


        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            Data::class,
            [
                'deliveryHelper' => $this->deliveryHelper,
                'configInterface' => $this->configInterface,
                'encryptor' => $this->encryptor,
                'customerSession' => $this->customerSession,
                'enhancedProfile' => $this->enhancedProfile,
                'ssoConfiguration' => $this->ssoConfiguration,
                'checkoutSession' => $this->checkoutSession,
                'toggleConfig' => $this->toggleConfig,
                'accountHelper' => $this->accountHelper
            ]
        );
    }

    /**
     * @test getMaxCartLimitValue
     *
     */
    public function testGetMaxCartLimitValue()
    {
        $maxCartItemLimit = null;
        $minCartItemThreshold =null;
        $cartLimit = [
            'maxCartItemLimit' => $maxCartItemLimit,
            'minCartItemThreshold' => $minCartItemThreshold
        ];
        $data = $this->createMock(Data::class);
        $data->method('getMaxCartLimitValue')
        ->willreturn($cartLimit);
        $this->assertEquals($cartLimit, $this->data->getMaxCartLimitValue());
    }

    /**
     * @test setEproClass when it will be a epro customer
     *
     */
    public function testSetEproClass()
    {
        $this->deliveryHelper->expects($this->any())->method(self::IS_COMMERCIAL_CUSTOMER)->willReturn(true);
        $this->assertEquals(true, $this->data->setEproClass());
    }

    /**
     * @test setEproClass when it will be not a epro customer
     *
     */
    public function testSetEproClassFalse()
    {
        $this->deliveryHelper->expects($this->any())->method(self::IS_COMMERCIAL_CUSTOMER)->willReturn(false);
        $this->assertEquals(false, $this->data->setEproClass());
    }

    /**
     * @test encryptData
     */
    public function testEncryptData()
    {
        $data = 'aBCd';
        $encrypt = 'AbcD';
        $this->encryptor->expects($this->any())->method('encrypt')->with($data)->willReturn($encrypt);
        $this->assertEquals($encrypt, $this->data->encryptData($data));
    }

    /**
     * @test decryptData
     */
    public function testDecryptData()
    {
        $data = 'aBCd';
        $decrypt = 'AbcD';
        $this->encryptor->expects($this->any())->method('decrypt')->with($data)->willReturn($decrypt);
        $this->assertEquals($decrypt, $this->data->decryptData($data));
    }

    /**
     * Test getDefaultFedexAccountNumber
     *
     * @return void
     */
    public function testGetDefaultFedexAccountNumber()
    {
        $profileInfo = json_decode($this->getProfileInfo());
        $accountSummary = ['account_status' => 'active'];

        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($profileInfo);
        $this->enhancedProfile->expects($this->any())->method('getAccountSummary')->willReturn($accountSummary);
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);

        $this->assertEquals(false, $this->data->getDefaultFedexAccountNumber());
    }

    /**
     * Test getDefaultFedexAccountNumber
     *
     * @return void
     */
    public function testGetDefaultFedexAccountNumber2()
    {
        $profileInfo = json_decode($this->getProfileInfo());
        $accountSummary = ['account_status' => 'active'];

        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($profileInfo);
        $this->enhancedProfile->expects($this->any())->method('getAccountSummary')->willReturn($accountSummary);
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(0);
        $this->accountHelper->expects($this->any())
            ->method('getActivePersonalAccountList')
            ->willReturn(['610977553' => 'My Account-553']);
        $this->accountHelper->expects($this->any())
            ->method('getActiveCompanyAccountList')
            ->willReturn(['610977553' => 'My Account-553']);
        $this->assertEquals('', $this->data->getDefaultFedexAccountNumber());
    }

    /**
     * Test getDefaultFedexAccountNumber
     *
     * @return void
     */
    public function testGetDefaultFedexAccountNumber3()
    {
        $profileInfo = json_decode($this->getProfileInfo());
        $accountSummary = ['account_status' => 'active'];

        $this->customerSession->expects($this->any())->method('getProfileSession')->willReturn($profileInfo);
        $this->enhancedProfile->expects($this->any())->method('getAccountSummary')->willReturn($accountSummary);
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(0);
        $this->accountHelper->expects($this->any())
            ->method('getActivePersonalAccountList')
            ->willReturn(['610977553' => 'My Account-553']);
        $this->accountHelper->expects($this->any())
            ->method('getActiveCompanyAccountList')
            ->willReturn([]);
        $this->assertEquals('', $this->data->getDefaultFedexAccountNumber());
    }

    /**
     * Get profile info data
     *
     * @return JSON
     */
    public function getProfileInfo()
    {
        return '{
            "transactionId": "106f4966-f8aa-4fd2-a34a-a6a5b74e473f",
            "output": {
              "profile": {
                "accounts": [
                  {
                    "accountNumber": "610977553",
                    "maskedAccountNumber": "*7553",
                    "accountLabel": "My Account-553",
                    "accountType": "PRINTING",
                    "primary": true,
                    "accountValid": false
                  }
                ]
              }
            }
          }';
    }

    /**
     * Test case for formatPrice
     */
    public function testformatPrice()
    {
        $this->assertNotNull($this->data->formatPrice('12.00'));
    }

    /**
     * Test case for formatPrice with Float
     */
    public function testformatPriceWithFloat()
    {
        $this->assertNotNull($this->data->formatPrice(12.23));
    }

    /**
     * Test case for formatPrice with Int
     */
    public function testformatPriceWithInt()
    {
        $this->assertNotNull($this->data->formatPrice(12));
    }

    /**
     * Test case for getRateQuoteApiUrl
     */
    public function testgetRateQuoteApiUrl()
    {
        $this->assertNull($this->data->getRateQuoteApiUrl());
    }

    /**
     * Test case for getRateQuoteId
     */
    public function testgetRateQuoteId()
    {
        $this->assertNull($this->data->getRateQuoteId($this->output));
    }

    /**
     * Test case for getRateQuoteIdWithRateQuoteId
     */
    public function testgetRateQuoteIdWithId()
    {
        $this->assertNotNull($this->data->getRateQuoteId($this->outputWithRateQuoteId));
    }

    /**
     * Test case for setFxoProductNull
     */
    public function testSetFxoProductNull()
    {
        $decodedValue = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => ''
                    ],
                ],
            ],
        ];
        $this->assertNotNull($this->data->setFxoProductNull($decodedValue, $decodedValue));
    }

    /**
     * Test case for getProductAssociation
     */
    public function testGetProductAssociation()
    {
        $this->item->expects($this->any())->method('getItemId')->willReturn(1);
        $this->item->expects($this->any())->method('getQty')->willReturn(5);
        $this->assertNotNull($this->data->getProductAssociation($this->item, 1, 12, 12));
    }

    /**
     * Test case for getProductAssociation
     */
    public function testGetProductAssociationWithoutAnyCondition()
    {
        $expectedResult = [
            'id' => 1,
            'quantity' => 5,
            'is_marketplace' => true
        ];
        $this->item->expects($this->any())->method('getAdditionalData')->willReturn('{"id":1, "quantity":5, "is_marketplace":true}');
        $this->item->expects($this->any())->method('getQty')->willReturn(5);
        $result = $this->data->getProductAssociation($this->item, 1, 12, 14);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test case for getProductAssociation
     */
    public function testGetProductAssociationMirkl()
    {
        $expectedResult = [
            'id' => 1,
            'quantity' => 5,
            'is_marketplace' => true
        ];
        $this->item->expects($this->any())->method('getMiraklOfferId')->willReturn(1);
        $this->item->expects($this->any())->method('getAdditionalData')->willReturn('{"id":1, "quantity":5, "is_marketplace":true}');
        $this->item->expects($this->any())->method('getItemId')->willReturn(1);
        $this->item->expects($this->any())->method('getQty')->willReturn(5);
        $result = $this->data->getProductAssociation($this->item, 1, 12, 12);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test case for getProductAssociationWithElse
     */
    public function testGetProductAssociationWithElse()
    {
        $this->item->expects($this->any())->method('getItemId')->willReturn(1);
        $this->item->expects($this->any())->method('getQty')->willReturn(5);
        $this->assertNotNull($this->data->getProductAssociation($this->item, 1, 21, 12));
    }

    public function testGetProductAssociationWithToggleEnableItemExists()
    {
        $this->item->expects(static::exactly(2))->method('getItemId')->willReturn(1);
        $this->item->expects(static::any())->method('getQty')->willReturn(5);
        $this->assertNotNull($this->data->getProductAssociation($this->item, 1, 12, 12));
    }

    public function testGetProductAssociationWithToggleEnableItemNotExists()
    {
        $this->item->expects(static::atMost(2))->method('getItemId')->willReturn(null);
        $this->item->expects(static::any())->method('getQty')->willReturn(5);
        $this->assertNotNull($this->data->getProductAssociation($this->item, 1, 12, 12));
    }

    /**
     * @test applyFedxExAccountInCheckout
     */
    public function testApplyFedxExAccountInCheckout()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->checkoutSession->expects($this->any())
            ->method('getAppliedFedexAccNumber')
            ->willReturn(false);
        $this->checkoutSession->expects($this->any())
            ->method('getRemoveFedexAccountNumber')
            ->willReturn(false);

        $this->assertEquals(true, $this->data->applyFedxExAccountInCheckout($this->quoteMock));
    }

    /**
     * @test isRemoveBase64ImageToggleEnabled
     */
    public function testIsRemoveBase64ImageToggleEnabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->isRemoveBase64ImageToggleEnabled());
    }

    /**
     * @test isRemoveBase64ImageToggleEnabled
     */
    public function testIsRemoveBase64ImageToggleDisabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->assertEquals(false, $this->data->isRemoveBase64ImageToggleEnabled());
    }

    /**
     * @test isMixedCartPromoErrorToggleEnabled
     */
    public function testIsMixedCartPromoErrorToggleEnabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->isMixedCartPromoErrorToggleEnabled());
    }

    /**
     *test to getDltThresholdHours
     */
    public function testGetDltThresholdHours()
    {
        $dltThresholds = json_encode([
            'dlt_threshold_field' => [
                [
                    'dlt_start' => 1,
                    'dlt_end' => 100,
                    'dlt_hours' => 2,
                ],
            ],
        ]);
        $qty =3;
        $this->product->expects($this->any())->method('getData')
            ->with('dlt_thresholds')
            ->willReturn($dltThresholds);
        $this->product->expects($this->any())->method('getId')->willReturn(12021);
        $this->item->expects($this->any())->method('getQty')->willReturn(50);
        $this->data->getDltThresholdHours(
            $this->product,
            $qty,
            2
        );
    }

    /**
     * test to setDltThresholdHours
     */
    public function testSetDltThresholdHoursProductDetails()
    {
        $decodedData = [
            'external_prod' => [
                [
                    'externalProductDetails' => [
                        'productionTime' => [
                            'value' => 1,
                            'units' => 'HOUR',
                        ],
                    ],
                ],
            ],
        ];
        $dltHours = 2;

        $this->data->setDltThresholdHours($decodedData, $dltHours);
    }

    /**
     * test to setDltThresholdHours
     */
    public function testSetDltThresholdHoursProductDetails2()
    {
        $decodedData = [
            'external_prod' => [
                [
                    'externalProductionDetails' => [
                        'productionTime' => [
                            'value' => 1,
                            'units' => 'HOUR',
                        ],
                    ],
                ],
            ],
        ];
        $dltHours = 2;

        $this->data->setDltThresholdHours($decodedData, $dltHours);
    }

    /**
     * test to setDltThresholdHours No externalProductDetails key here
     */
    public function testSetDltThresholdHoursWithoutProductDetails()
    {
        $decodedData = [
            'external_prod' => [
                [],
            ],
        ];
        $dltHours = 2;
        $this->data->setDltThresholdHours($decodedData, $dltHours);
    }

    /**
     * test to checkQuotePriceableDisable
     */
    public function testCheckQuotePriceableDisable()
    {
        $this->enhancedProfile->expects($this->any())
            ->method('isQuotePriceableDisable')
            ->willReturn(true);
        $this->data->checkQuotePriceableDisable($this->quoteMock);
        $this->assertEquals(true, $this->data->checkQuotePriceableDisable($this->quoteMock));
    }
    
    /**
     * test to setAddressClassification
     */
    public function testSetAddressClassification()
    {
        $this->checkoutSession->expects($this->any())
            ->method('setAddressClassification')
            ->willReturnSelf();
        $result = $this->data->setAddressClassification("Test Classification");
        $this->assertEquals(null, $result);
    }

    /**
     * test to getAddressClassification
     */
    public function testGetAddressClassification()
    {
        $this->checkoutSession->expects($this->any())
            ->method('getAddressClassification')
            ->willReturn("Test Classification");
        $result = $this->data->getAddressClassification("Test Classification");
        $this->assertEquals("Test Classification", $result);
    }

    /**
     * test to isAddressClassificationFixToggleEnabled
     */
    public function testIsAddressClassificationFixToggleEnabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $result = $this->data->isAddressClassificationFixToggleEnabled();
        $this->assertEquals(true, $result);
    }

    /**
     * test to testIsAddressClassificationFixToggleDisabled
     */
    public function testIsAddressClassificationFixToggleDisabled()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $result = $this->data->isAddressClassificationFixToggleEnabled();
        $this->assertEquals(false, $result);
    }

    /**
     * test to isCommercialCustomer
     */
    public function testIsCommercialCustomer()
    {
        $this->deliveryHelper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $result = $this->data->isCommercialCustomer();
        $this->assertEquals(true, $result);
    }

}
