<?php

namespace Fedex\Delivery\Test\Unit\Helper;

use Fedex\Delivery\Helper\QuoteDataHelper;
use Fedex\Email\Helper\Data as EmailDataHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Test\Unit\Helper\AdminConfigHelperTest;

class QuoteDataHelperTest extends TestCase
{
    protected $abstractHelper;
    protected $addressInterfaceFactory;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterface;
    /**
     * @var (\Fedex\Email\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $emailDataHelper;
    protected $cartFactory;
    protected $cart;
    protected $quote;
    protected $quoteInterface;
    protected $quoteRepository;
    protected $address;
    protected $dataAddressInterfaceMock;
    protected $region;
    protected $quoteDataHelper;

    public const EMAIL = 'ayush.sood@igglobal.com';
    public const BILLING_EMAIL = 'neeraj2.gupta@igglobal.com';

    public const SHIPPING_ADDRESS = ['customAttributes' =>
        ['0' =>
            ['attribute_code' => 'email_id', 'value' => 'neeraj2.gupta@igglobl.com'],
        ],
        'altFirstName' => 'altFirstName',
        'altLastName' => 'altLastName',
        'altEmail' => 'altEmail',
        'altPhoneNumber' => 'altPhoneNumber',
        'is_alternate' => 'is_alternate',
        'altPhoneNumberext' => 'altPhoneNumberext',
        'fedexShipAccountNumber' => 'fedexShipAccountNumber',
        'fedexShipReferenceId' => 'fedexShipReferenceId',
        'region' => 'region',
        'regionId' => 'regionId',
        'regionCode' => 'regionCode',
        'countryId' => 'countryId',
        'street' => [0 => 'street', 1 => 'street1'],
        'postcode' => null,
        'city' => 'city',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'telephone' => 'telephone',
        'company' => 'company',
    ];

    public const REQUEST_DATA = [
        'addressInformation' => [
            'shipping_address' => [
                'saveInAddressBook' => '1',
                'customAttributes' => [
                    '0' => [
                        'attribute_code' => 'ext',
                        'value' => self::EMAIL,
                    ],
                ],
                'altFirstName' => 'altFirstName',
                'altLastName' => 'altLastName',
                'altEmail' => 'altEmail',
                'altPhoneNumber' => 'altPhoneNumber',
                'is_alternate' => 'is_alternate',
                'altPhoneNumberext' => 'altPhoneNumberext',
                'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                'fedexShipReferenceId' => 'fedexShipReferenceId',
                'region' => 'region',
                'regionId' => 'regionId',
                'regionCode' => 'regionCode',
                'countryId' => 'countryId',
                'street' => [
                    0 => 'street',
                    1 => 'street1',
                ],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
                'region_id' => 'regionId',
                'region_code' => 'regionCode',
                'country_id' => 'countryId',
                'email' => self::EMAIL,
            ],
            'billing_address' => [
                'customAttributes' => [
                    '0' => [
                        'attribute_code' => 'email_id',
                        'value' => self::BILLING_EMAIL,
                    ],
                ],
                'altFirstName' => 'altFirstName',
                'altLastName' => 'altLastName',
                'altEmail' => 'altEmail',
                'altPhoneNumber' => 'altPhoneNumber',
                'is_alternate' => 'is_alternate',
                'altPhoneNumberext' => 'altPhoneNumberext',
                'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                'fedexShipReferenceId' => 'fedexShipReferenceId',
                'region' => 'region',
                'regionId' => 'regionId',
                'regionCode' => 'regionCode',
                'countryId' => 'countryId',
                'street' => [0 => 'street'],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
                'region_id' => 'regionId',
                'region_code' => 'regionCode',
                'country_id' => 'countryId',
                'email' => self::EMAIL,
            ],
            'shipping_detail' => [
                'carrier_code' => 'carrier_code',
                'method_code' => 'method_code',
                'carrier_title' => 'carrier_title',
                'method_title' => 'method_title',
                'amount' => 'amount',
                'price_excl_tax' => 'price_excl_tax',
                'price_incl_tax' => 'price_incl_tax',
            ],
            'shipping_carrier_code' => 'carrier_code',
            'shipping_method_code' => 'method_code',
            'carrier_title' => 'carrier_title',
            'method_title' => 'EXPRESS_SAVER',
            'amount' => 'amount',
            'price_excl_tax' => 'price_excl_tax',
            'price_incl_tax' => 'price_incl_tax',
        ],
        'quoteCreation' => [
            'quoteName' => 'quoteName',
            'comment' => 'comment',
        ],
    ];
    public const REQUEST_DATA_WITHOUT_BILLING_ADDRESS = [
        'addressInformation' => [
            'shipping_address' => [
                'region' => 'region',
                'street' => [
                    0 => 'street',
                ],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
                'region_id' => 'regionId',
                'region_code' => 'regionCode',
                'country_id' => 'countryId',
                'email' => self::EMAIL,
            ],
            'billing_address' => [
                'region' => 'region',
                'street' => [0 => 'street'],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
                'region_id' => 'regionId',
                'region_code' => 'regionCode',
                'country_id' => 'countryId',
                'email' => self::EMAIL,
            ],
            'shipping_carrier_code' => 'carrier_code',
            'shipping_method_code' => 'method_code',
            'carrier_title' => 'carrier_title',
            'method_title' => 'EXPRESS_SAVER',
            'amount' => 'amount',
            'price_excl_tax' => 'price_excl_tax',
            'price_incl_tax' => 'price_incl_tax',
        ]];

    public const REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE = [
        'addressInformation' => [
            'shipping_address' => [
                'saveInAddressBook' => '1',
                'customAttributes' => [
                    '0' => [
                        'attribute_code' => 'email_id',
                        'value' => self::EMAIL,
                    ],
                ],
                'altFirstName' => 'altFirstName',
                'altLastName' => 'altLastName',
                'altEmail' => 'altEmail',
                'altPhoneNumber' => 'altPhoneNumber',
                'is_alternate' => 'is_alternate',
                'altPhoneNumberext' => 'altPhoneNumberext',
                'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                'fedexShipReferenceId' => 'fedexShipReferenceId',
                'region' => 'region',
                'regionId' => 'regionId',
                'regionCode' => 'regionCode',
                'countryId' => 'countryId',
                'street' => [
                    0 => 'street',
                    1 => 'street1',
                ],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
                'company' => 'company',
            ],
            'billing_address' => [
                'customAttributes' => [
                    '0' => [
                        'attribute_code' => 'email_id',
                        'value' => self::BILLING_EMAIL,
                    ],
                ],
                'altFirstName' => 'altFirstName',
                'altLastName' => 'altLastName',
                'altEmail' => 'altEmail',
                'altPhoneNumber' => 'altPhoneNumber',
                'is_alternate' => 'is_alternate',
                'altPhoneNumberext' => 'altPhoneNumberext',
                'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                'fedexShipReferenceId' => 'fedexShipReferenceId',
                'region' => 'region',
                'regionId' => 'regionId',
                'regionCode' => 'regionCode',
                'countryId' => 'countryId',
                'street' => [0 => 'street'],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
            ],
            'shipping_detail' => [
                'carrier_code' => 'carrier_code',
                'method_code' => 'method_code',
                'carrier_title' => 'carrier_title',
                'method_title' => 'method_title',
                'amount' => 'amount',
                'price_excl_tax' => 'price_excl_tax',
                'price_incl_tax' => 'price_incl_tax',
            ],
            'shipping_carrier_code' => 'carrier_code',
            'shipping_method_code' => 'method_code',
        ]];
    public const REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE_WITH_SHIPPING_INFO = [
        'shippingInformation' => [
            'addressInformation' => [
                'shipping_address' => [
                    'saveInAddressBook' => '1',
                    'customAttributes' => [
                        '0' => [
                            'attribute_code' => 'email_id',
                            'value' => self::EMAIL,
                        ],
                    ],
                    'altFirstName' => 'altFirstName',
                    'altLastName' => 'altLastName',
                    'altEmail' => 'altEmail',
                    'altPhoneNumber' => 'altPhoneNumber',
                    'is_alternate' => 'is_alternate',
                    'altPhoneNumberext' => 'altPhoneNumberext',
                    'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                    'fedexShipReferenceId' => 'fedexShipReferenceId',
                    'region' => 'region',
                    'regionId' => 'regionId',
                    'regionCode' => 'regionCode',
                    'countryId' => 'countryId',
                    'street' => [
                        0 => 'street',
                        1 => 'street1',
                    ],
                    'postcode' => 'postcode',
                    'city' => 'city',
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'telephone' => 'telephone',
                    'company' => 'company',
                    'country_id' => 'country_id',
                    'region_id' => 'region_id',
                ],
                'billing_address' => [
                    'customAttributes' => [
                        '0' => [
                            'attribute_code' => 'email_id',
                            'value' => 'neeraj2.gupta@igglobal.com',
                        ],
                    ],
                    'altFirstName' => 'altFirstName',
                    'altLastName' => 'altLastName',
                    'altEmail' => 'altEmail',
                    'altPhoneNumber' => 'altPhoneNumber',
                    'is_alternate' => 'is_alternate',
                    'altPhoneNumberext' => 'altPhoneNumberext',
                    'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                    'fedexShipReferenceId' => 'fedexShipReferenceId',
                    'region' => 'region',
                    'regionId' => 'regionId',
                    'regionCode' => 'regionCode',
                    'countryId' => 'countryId',
                    'street' => [0 => 'street'],
                    'postcode' => 'postcode',
                    'city' => 'city',
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'telephone' => 'telephone',
                    'country_id' => 'country_id',
                    'region_id' => 'region_id',
                ],
                'shipping_detail' => [
                    'carrier_code' => 'carrier_code',
                    'method_code' => 'method_code',
                    'carrier_title' => 'carrier_title',
                    'method_title' => 'method_title',
                    'amount' => 'amount',
                    'price_excl_tax' => 'price_excl_tax',
                    'price_incl_tax' => 'price_incl_tax',
                ],
            ]]];
    public const REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE_WITH_SHIPPING_INFO_WITHOUT_BILLING = [
        'shippingInformation' => [
            'addressInformation' => [
                'shipping_address' => [
                    'saveInAddressBook' => '1',
                    'customAttributes' => [
                        '0' => [
                            'attribute_code' => 'email_id',
                            'value' => self::EMAIL,
                        ],
                    ],
                    'altFirstName' => 'altFirstName',
                    'altLastName' => 'altLastName',
                    'altEmail' => 'altEmail',
                    'altPhoneNumber' => 'altPhoneNumber',
                    'is_alternate' => 'is_alternate',
                    'altPhoneNumberext' => 'altPhoneNumberext',
                    'fedexShipAccountNumber' => 'fedexShipAccountNumber',
                    'fedexShipReferenceId' => 'fedexShipReferenceId',
                    'region' => 'region',
                    'regionId' => 'regionId',
                    'regionCode' => 'regionCode',
                    'countryId' => 'countryId',
                    'street' => [
                        0 => 'street',
                        1 => 'street1',
                    ],
                    'postcode' => 'postcode',
                    'city' => 'city',
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'telephone' => 'telephone',
                    'company' => 'company',
                    'country_id' => 'country_id',
                    'region_id' => 'region_id',
                ],
                'shipping_detail' => [
                    'carrier_code' => 'carrier_code',
                    'method_code' => 'method_code',
                    'carrier_title' => 'carrier_title',
                    'method_title' => 'method_title',
                    'amount' => 'amount',
                    'price_excl_tax' => 'price_excl_tax',
                    'price_incl_tax' => 'price_incl_tax',
                ],
            ]]];

    public const SHIPPING_REQUEST_DATA = [
        'orderNumber' => '122334',
        'addressInformation' => [
            'shipping_method_code' => 'shipping_method_code',
            'shipping_carrier_code' => 'shipping_carrier_code',
            'shipping_detail' => [
                'carrier_code' => 'fedexshipping',
                'method_code' => 'PICKUP',
                'carrier_title' => 'Ground US',
                'method_title' => '1 Business Day(s)',
                'amount' => 1,
                'price_excl_tax' => 0,
                'price_incl_tax' => 0,
            ],
            'shipping_address' => [
                'firstname' => 'Ayush',
                'lastname' => 'Sood',
            ],
        ],
        'contactInformation' => [
            'isAlternatePerson' => true,
            'contact_fname' => 'Test',
            'contact_lname' => 'Test',
            'contact_email' => 'Test',
            'contact_number' => 'Test',
            'alternate_fname' => 'Test',
            'alternate_lname' => 'Test',
            'alternate_email' => 'Test',
            'alternate_number' => 'Test',
        ],
    ];
    /**
     * @var RegionFactory $regionFactory
     */
    protected $regionFactory;

    /**
     * @var AddressInterfaceFactory $dataAddressFactory
     */
    protected $dataAddressFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var EmailDataHelper $emailHelper
     */
    protected $emailHelper;

    /**
     * @var NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagementInterface
     */
    protected $negotiableQuoteItemManagementInterface;

    /**
     * @var CommentManagementInterface $commentManagementInterface
     */
    protected $commentManagementInterface;

    /**
     * @var History $history
     */
    protected $history;

    /**
     * @var SdeHelper $sdeHelper
     */
    //protected $sdeHelper;
    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var AdminConfigHelper $adminConfigHelper
     */
    protected $adminConfigHelper;
    
    /**
     * @var TimezoneInterface $timezoneInterface
     */
    protected $timezoneInterface;

    protected $adminConfigHelperTest;

    protected function setup(): void
    {
        $this->abstractHelper = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getId'])
            ->getMock();
        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adminConfigHelperTest = $this->getMockBuilder(AdminConfigHelperTest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressInterfaceFactory = $this->getMockBuilder(AddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->emailDataHelper = $this->getMockBuilder(EmailDataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->negotiableQuoteItemManagementInterface =
            $this->getMockBuilder(NegotiableQuoteItemManagementInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['setQuoteId', 'setIsRegularQuote', 'setAppliedRuleIds',
                    'setStatus', 'setQuoteName', 'setUploadToQuoteFlow', 'setQuoteMgntLocationCode',
                     'getData','setExpirationPeriod','getExpirationPeriod','setCreatorId'])
                ->getMockForAbstractClass();
        $this->commentManagementInterface = $this->getMockBuilder(CommentManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        // $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
        //     ->setMethods(['getIsSdeStore'])
        //     ->disableOriginalConstructor()
        //     ->getMock();
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['setData', 'setCouponCode', 'getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getExtensionAttributes', 'getNegotiableQuote',
                'getAppliedRuleIds', 'getQuoteMgntLocationCode', 'getShippingAddress', 
                'getBillingAddress', 'setConvertedAt', 'save', 
                'setData'])
            ->getMockForAbstractClass();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->addressInterfaceFactory = $this->getMockBuilder(AddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateEproNegotiableQuote','updateFinalQuoteStatus', 'isNegotiableQuoteExistingForQuote'])
            ->getMock();
            
        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCollection',
                    'save',
                    'getAddressType',
                    'getAddressId',
                    'setCompany',
                    'getData',
                    'setStreet',
                    'setCity',
                    'setCountryId',
                    'setPostcode',
                    'setRegion',
                    'setRegionId',
                    'setCollectShippingRates',
                    'setSameAsBilling',
                ]
            )
            ->getMock();
        $this->dataAddressInterfaceMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->timezoneInterface = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'format'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteDataHelper = $objectManagerHelper->getObject(
            QuoteDataHelper::class,
            [
                'regionFactory' => $this->regionFactory,
                'dataAddressFactory' => $this->addressInterfaceFactory,
                'loggerInterface' => $this->loggerInterface,
                'emailHelper' => $this->emailDataHelper,
                'negotiableQuoteItemManagementInterface' => $this->negotiableQuoteItemManagementInterface,
                'commentManagementInterface' => $this->commentManagementInterface,
                'history' => $this->history,
                'adminConfigHelper' => $this->adminConfigHelper,
                'toggleConfig' => $this->toggleConfig,
                'timezoneInterface' => $this->timezoneInterface
                //'sdeHelper' => $this->sdeHelper,
            ]
        );
    }

    /**
     * Test Case for unsetAddressInformation
     */
    public function testunsetAddressInformation()
    {
        $shipAddressInfo = [
            'carrier_title' => 'Express_Saver',
            'method_title' => 'Fedex_expressSaver',
            'amount' => '$23.00',
            'price_excl_tax' => "$30.00",
            'price_incl_tax' => "$35.00",
        ];

        $this->assertNull($this->quoteDataHelper->unsetAddressInformation($shipAddressInfo));
    }

    /**
     * Test case for setQuoteData
     */
    public function testsetQuoteData()
    {
        $contactInformation = [
            'firstName' => 'Ayush',
            'lastName' => 'Sood',
            'email' => 'ayush.sood@infogain.com',
            'number' => '7087753785',
            'ext_no' => '+91',
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNull($this->quoteDataHelper->setQuoteData($this->quote, $contactInformation));
    }

    /**
     * Test Case for getNewAddressData
     */
    public function testgetNewAddressData()
    {
        $this->addressInterfaceFactory->expects($this->any())
            ->method('create')->willReturn($this->dataAddressInterfaceMock);
        $this->dataAddressInterfaceMock->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCity')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultShipping')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setIsDefaultBilling')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomerId')->willReturnSelf();
        $this->dataAddressInterfaceMock->expects($this->any())->method('setCustomAttribute')->willReturnSelf();
        $this->assertEquals(
            $this->dataAddressInterfaceMock,
            $this->quoteDataHelper->getNewAddressData(self::SHIPPING_ADDRESS, 12)
        );
    }

    /**
     * Test Case for Reset Promo Code
     */
    public function testResetPromoCodeWithInvalid()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.INVALID',
                ],
            ],
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);

        $this->assertEquals(
            'Promo code invalid. Please try again.',
            $this->quoteDataHelper->resetPromoCode($rateResponse, $this->quote)
        );
    }

    public function testResetPromoCodeWithMinimumPurchaseRequired()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED',
                ],
            ],
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);

        $this->assertEquals(
            'Minimum purchase amount not met.',
            $this->quoteDataHelper->resetPromoCode($rateResponse, $this->quote)
        );
    }

    public function testResetPromoCodeWithInvalidProductCode()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'INVALID.PRODUCT.CODE',
                    'message' => 'promo code is invali',
                ],
            ],
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);

        $this->assertEquals(
            'promo code is invali',
            $this->quoteDataHelper->resetPromoCode($rateResponse, $this->quote)
        );
    }

    public function testResetPromoCodeWithCodeCouponExpired()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.EXPIRED',
                ],
            ],
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);

        $this->assertEquals(
            'Promo code has expired. Please try again.',
            $this->quoteDataHelper->resetPromoCode($rateResponse, $this->quote)
        );
    }

    public function testResetPromoCodeWithCodeCouponReedemed()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.REDEEMED',
                ],
            ],
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);

        $this->assertEquals(
            'Promo code has already been redeemed.',
            $this->quoteDataHelper->resetPromoCode($rateResponse, $this->quote)
        );
    }

    public function testisValidContactInformation()
    {
        $requestData = [
            'orderNumber' => '12234',
            'addressInformation' => [
                'pickup_location_state' => 'pickup_location_state',
                'pickup_location_country' => 'pickup_location_country',
                'pickup_location_street' => 'pickup_location_street',
                'pickup_location_zipcode' => 'pickup_location_zipcode',
                'pickup_location_city' => 'pickup_location_city',
                'shipping_detail' => [
                    'carrier_code' => 'fedexshipping',
                    'method_code' => 'PICKUP',
                    'carrier_title' => 'Ground US',
                    'method_title' => '1 Business Day(s)',
                    'amount' => 1,
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                ],
            ],
            'contactInformation' => [
                'isAlternatePerson' => true,
                'contact_fname' => 'Test',
                'contact_lname' => 'Test',
                'contact_email' => 'Test',
                'contact_number' => 'Test',
                'alternate_fname' => 'Test',
                'alternate_lname' => 'Test',
                'alternate_email' => 'Test',
                'alternate_number' => 'Test',
            ],
        ];
        $this->assertTrue($this->quoteDataHelper->isValidContactInformation(json_encode($requestData)));
    }

    public function testIsValidateShippingDetailQuoteRequest()
    {
        $requestData = [
            'orderNumber' => '122334',
            'addressInformation' => [
                'pickup_location_state' => 'pickup_location_state',
                'pickup_location_country' => 'pickup_location_country',
                'pickup_location_street' => 'pickup_location_street',
                'pickup_location_zipcode' => 'pickup_location_zipcode',
                'pickup_location_city' => 'pickup_location_city',
                'shipping_detail' => [
                    'carrier_code' => 'fedexshipping',
                    'method_code' => 'PICKUP',
                    'carrier_title' => 'Ground US',
                    'method_title' => '1 Business Day(s)',
                    'amount' => 1,
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                ],
            ],
            'contactInformation' => [
                'isAlternatePerson' => true,
                'contact_fname' => 'Test',
                'contact_lname' => 'Test',
                'contact_email' => 'Test',
                'contact_number' => 'Test',
                'alternate_fname' => 'Test',
                'alternate_lname' => 'Test',
                'alternate_email' => 'Test',
                'alternate_number' => 'Test',
            ],
        ];
        $this->assertFalse($this->quoteDataHelper->isValidateShippingDetailQuoteRequest(json_encode($requestData)));
    }

    public function testIsValidateShippingDetailQuoteRequestWithShippingInfo()
    {
        $this->assertTrue($this->quoteDataHelper
            ->isValidateShippingDetailQuoteRequest(json_encode(self::SHIPPING_REQUEST_DATA)));
    }

    /**
     * Test case for hasIncludeDirectSignatureOptions
     */
    public function testIsValidateShippingData()
    {
        $this->assertFalse($this->quoteDataHelper->isValidateShippingData(self::SHIPPING_REQUEST_DATA));
    }


    /**
     * Test case for hasIncludeDirectSignatureOptions
     */
    // public function testHasIncludeDirectSignatureOptions()
    // {
    //     $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
    //     $this->assertTrue($this->quoteDataHelper->hasIncludeDirectSignatureOptions());
    // }

    /**
     * Test case for hasIncludeDirectSignatureOptions With SDE Store
     */
    // public function testHasIncludeDirectSignatureOptionsWithNoSDEStore()
    // {
    //     $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
    //     $this->assertFalse($this->quoteDataHelper->hasIncludeDirectSignatureOptions());
    // }

    /**
     * Test Case for getDirectSignatureOptionsParams
     */
    // public function testgetDirectSignatureOptionsParams()
    // {
    //     $signatureOptions = [
    //         'specialServiceType' => 'SIGNATURE_OPTION',
    //         'specialServiceSubType' => 'DIRECT',
    //         'displayText' => 'Direct Signature Required',
    //         'description' => 'Direct Signature Required',
    //     ];
    //     $this->testHasIncludeDirectSignatureOptions();
    //     $this->assertEquals($signatureOptions, $this->quoteDataHelper->getDirectSignatureOptionsParams());
    // }

    /**
     * Test Case for  getContactDetails
     */
    public function testgetContactDetails()
    {
        $contactInformation = [
            'firstName' => 'firstname',
            'lastName' => 'lastname',
            'email' => '',
            'number' => 'telephone',
            'street' => [
                0 => 'street',
                1 => 'street1',
            ],
            'country_id' => 'countryId',
            'region_id' => 'regionId',
            'region' => 'region',
            'postcode' => 'postcode',
            'city' => 'city',
            'ext_no' => self::EMAIL,
        ];
        $requestDataObject = json_decode(json_encode(self::REQUEST_DATA), false);
        $this->assertequals(
            $contactInformation,
            $this->quoteDataHelper->getContactDetails($requestDataObject, false, 0, 0, 1)
        );
    }
    /**
     * Test Case for  getContactDetails With email ID as a custom Attribute
     */
    public function testgetContactDetailswithEmailID()
    {
        $contactInformation = [
            'firstName' => 'altFirstName',
            'lastName' => 'altLastName',
            'email' => 'altEmail',
            'number' => 'altPhoneNumber',
            'street' => [
                0 => 'street',
                1 => 'street1',
            ],
            'country_id' => 'countryId',
            'region_id' => 'regionId',
            'region' => 'region',
            'postcode' => 'postcode',
            'city' => 'city',
            'ext_no' => 'altPhoneNumberext',
        ];
        $requestDataObject = json_decode(json_encode(self::REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE), false);
        $this->assertequals(
            $contactInformation,
            $this->quoteDataHelper->getContactDetails($requestDataObject, true, 0, 0, 1)
        );
    }

    /**
     * Test Case for getStateCode
     */
    public function testgetStateCode()
    {
        $requestDataObject = json_decode(json_encode(self::REQUEST_DATA), false);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('load')->willReturn($this->abstractHelper);
        $this->abstractHelper->expects($this->any())->method('getCode')->willReturn('TX');
        $this->assertEquals('TX', $this->quoteDataHelper->getStateCode($requestDataObject));
    }

    /**
     * Test case for setAlternateAddress
     */
    public function testSetAlternateAddress()
    {
        $requestDataObject = json_decode(json_encode(self::REQUEST_DATA), false);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setSameAsBilling')->willReturn(false);
        $this->assertNull($this->quoteDataHelper->setAlternateAddress(
            $this->quote,
            $requestDataObject,
            1,
            0,
            0,
            1
        ));
    }

    /**
     * Test case for getShippingData
     */
    public function testgetShippingData()
    {
        $requestDataObject = json_decode(json_encode(self::REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE), false);
        $this->assertEquals(
            self::REQUEST_DATA_WITHOUT_BILLING_ADDRESS,
            $this->quoteDataHelper->getShippingData($requestDataObject, 'EXPRESS_SAVER')
        );
    }

    /**
     * Test case for getNegotiableQuoteCreateData
     */
    public function testgetNegotiableQuoteCreateData()
    {
        $data = [
            'quoteCreation' => [
                'quoteId' => '12345',
                'quoteName' => 'Punchout Quote Creation',
                'comment' => 'Review my quote',
            ],
            'shippingInformation' => 'ABC',
        ];
        $this->assertEquals($data, $this->quoteDataHelper->getNegotiableQuoteCreateData('12345', 'ABC'));
    }

    /**
     * Test case for createNegotiableQuote
     */
    public function testcreateNegotiableQuote()
    {
        $this->quoteInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->quoteInterface->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getAppliedRuleIds')->willReturnSelf();
        $this->quoteInterface->expects($this->any())
            ->method('getNegotiableQuote')->willReturn($this->negotiableQuoteItemManagementInterface);
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setIsRegularQuote')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setAppliedRuleIds')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteName')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('getData')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('getData')->willReturnSelf();
        $this->assertNull($this->quoteDataHelper->createNegotiableQuote(
            $this->quoteInterface,
            $this->quoteRepository,
            self::REQUEST_DATA
        ));
    }

    /**
     * Test case for createNegotiableQuote with Upload To Quote
     */
    public function testcreateNegotiableQuoteWithUploadToQuote()
    {
        $this->quoteInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->quoteInterface->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getAppliedRuleIds')->willReturnSelf();
        $this->quoteInterface->expects($this->any())
            ->method('getNegotiableQuote')->willReturn($this->negotiableQuoteItemManagementInterface);
        $this->quoteInterface->expects($this->any())->method('getQuoteMgntLocationCode')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setIsRegularQuote')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setAppliedRuleIds')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteName')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setUploadToQuoteFlow')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteMgntLocationCode')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('getData')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('getData')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('getExpirationPeriod')->willReturnSelf(1);
        
        $uploadToQuote = [
            "isUploadToQuote" => true,
            "uploadToQuoteFlow" => "Flow 1"
        ];
        $this->assertNull($this->quoteDataHelper->createNegotiableQuote(
            $this->quoteInterface,
            $this->quoteRepository,
            self::REQUEST_DATA,
            $uploadToQuote
        ));
    }

    /**
     * Test case for createNegotiableQuote with Upload To Quote to Fuse
     */
    public function testcreateNegotiableQuoteWithUploadToQuoteFuse()
    {
        $this->quoteInterface->expects($this->any())->method('getId')->willReturn(12);
        $this->quoteInterface->expects($this->any())->method('getExtensionAttributes')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getAppliedRuleIds')->willReturnSelf();
        $this->quoteInterface->expects($this->any())
            ->method('getNegotiableQuote')->willReturn($this->negotiableQuoteItemManagementInterface);
        $this->quoteInterface->expects($this->any())->method('getQuoteMgntLocationCode')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setIsRegularQuote')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setAppliedRuleIds')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setStatus')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteName')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setQuoteMgntLocationCode')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getQuoteMgntLocationCode')->willReturnSelf();
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(1);
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('setCreatorId')->willReturnSelf();
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')->willReturn(1);
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2025-04-20');
        $this->quoteInterface->expects($this->any())->method('setConvertedAt')->with('2025-04-20')->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('save')->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())->method('getExpirationPeriod')->willReturnSelf(1);
        
        $uploadToQuote = [];
        $this->assertNull($this->quoteDataHelper->createNegotiableQuote(
            $this->quoteInterface,
            $this->quoteRepository,
            self::REQUEST_DATA,
            $uploadToQuote,
            false,
            true
        ));
    }

    /**
     * Test case for setShippingInformation
     */
    public function testsetShippingInformation()
    {
        $this->quoteInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->quoteInterface->expects($this->any())->method('getBillingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->assertNull($this->quoteDataHelper->setShippingInformation(
            $this->quoteInterface,
            self::REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE_WITH_SHIPPING_INFO
        ));
    }

    /**
     * Test case for setShippingInformation without Billing Information
     */
    public function testsetShippingInformationWithoutBilling()
    {
        $this->quoteInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->quoteInterface->expects($this->any())->method('getBillingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->address->expects($this->any())->method('setStreet')->willReturnSelf();
        $this->address->expects($this->any())->method('setCity')->willReturnSelf();
        $this->address->expects($this->any())->method('setCountryId')->willReturnSelf();
        $this->address->expects($this->any())->method('setPostcode')->willReturnSelf();
        $this->address->expects($this->any())->method('setRegionId')->willReturnSelf();
        $this->address->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->address->expects($this->any())->method('setCollectShippingRates')->willReturnSelf();
        $this->assertNull($this->quoteDataHelper->setShippingInformation(
            $this->quoteInterface,
            self::REQUEST_DATA_WITH_CUSTOM_ATTRIBUTE_WITH_SHIPPING_INFO_WITHOUT_BILLING
        ));
    }

    /**
     * Test case for setShippingInfo
     */
    public function testsetShippingInfo()
    {
        $requestData = [
            'orderNumber' => '122334',
            'addressInformation' => [
                'pickup_location_state' => 'pickup_location_state',
                'pickup_location_country' => 'pickup_location_country',
                'pickup_location_street' => 'pickup_location_street',
                'pickup_location_zipcode' => 'pickup_location_zipcode',
                'pickup_location_city' => 'pickup_location_city',
                'shipping_detail' => [
                    'carrier_code' => 'fedexshipping',
                    'method_code' => 'PICKUP',
                    'carrier_title' => 'Ground US',
                    'method_title' => '1 Business Day(s)',
                    'amount' => 1,
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                ],
            ],
            'contactInformation' => [
                'isAlternatePerson' => true,
                'contact_fname' => 'Test',
                'contact_lname' => 'Test',
                'contact_email' => 'Test',
                'contact_number' => 'Test',
                'alternate_fname' => 'Test',
                'alternate_lname' => 'Test',
                'alternate_email' => 'Test',
                'alternate_number' => 'Test',
            ],
        ];
        $this->quoteInterface->expects($this->any())->method('setData')->willReturnSelf();
        $this->assertNull($this->quoteDataHelper->setShippingInfo($this->quoteInterface, $requestData ));
    }

    /**
     * Test case for checkNegotiableQuoteExistingForQuote
     */
    public function testcheckNegotiableQuoteExistingForQuote()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('tiger_d206707')->willReturn(true);
        $this->adminConfigHelper->expects($this->any())->method('isNegotiableQuoteExistingForQuote')->with('146621')->willReturn(true);
        $this->assertEquals($this->quoteDataHelper->checkNegotiableQuoteExistingForQuote('146621'),true);
    }

    /**
     * Test case for updateEproQuoteStatusByKey
     */
    public function testupdateEproQuoteStatusByKey()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('tiger_d206707')->willReturn(true);
        $this->adminConfigHelper->expects($this->any())->method('updateFinalQuoteStatus')->with(1, 'New')->willReturn(true);
        
        $this->assertEquals($this->quoteDataHelper->updateEproQuoteStatusByKey(1, 'New'),1);
    }

    /**
     * Test case for updateEproNegotiableQuote
     */
    public function testupdateEproNegotiableQuote()
    {
        $this->adminConfigHelper->expects($this->any())->method('updateEproNegotiableQuote')->with($this->quoteInterface)->willReturn(true);
        
        $this->assertEquals($this->quoteDataHelper->updateEproNegotiableQuote($this->quoteInterface),1);
    }

     /**
     * Test case for checkIfuploadToQuote
     */
    public function testcheckIfuploadToQuote()
    {
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setQuoteName')
            ->with("Upload To Quote Creation")
            ->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setQuoteMgntLocationCode')
            ->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getQuoteMgntLocationCode')->willReturnSelf();
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('mazegeeks_d_210584_fix')->willReturn(true);
        $this->testsetConvertedAtDateForQuote();
        $this->assertEquals($this->quoteDataHelper->checkIfuploadToQuote($this->quoteInterface, 1, $this->negotiableQuoteItemManagementInterface),null);
    }

     /**
     * Test case for checkIffuseBidding
     */
    public function testcheckIffuseBidding()
    {
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setQuoteName')
            ->with("Fuse bidding Quote Creation")
            ->willReturnSelf();
        $this->negotiableQuoteItemManagementInterface->expects($this->any())
            ->method('setQuoteMgntLocationCode')
            ->willReturnSelf();
        $this->quoteInterface->expects($this->any())->method('getQuoteMgntLocationCode')->willReturnSelf();
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->with('mazegeeks_d_210584_fix')->willReturn(true);
        $this->testsetConvertedAtDateForQuote();
        $this->assertEquals($this->quoteDataHelper->checkIffuseBidding($this->quoteInterface, 1, $this->negotiableQuoteItemManagementInterface),null);
    }

    
     /**
     * Test case for setConvertedAtDateForQuote
     */
    public function testsetConvertedAtDateForQuote()
    {
        $this->timezoneInterface->expects($this->any())->method('date')->willReturnSelf();
        $this->timezoneInterface->expects($this->any())->method('format')->willReturn('2025-04-20');
        $this->quoteInterface->expects($this->any())->method('setConvertedAt')->with('2025-04-20')->willReturnSelf();
        
        $this->assertEquals($this->quoteDataHelper->setConvertedAtDateForQuote($this->quoteInterface),null);
    }
}
