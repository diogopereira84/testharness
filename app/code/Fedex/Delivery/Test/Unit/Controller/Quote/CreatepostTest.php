<?php

namespace Fedex\Delivery\Test\Unit\Controller\Quote;

use Fedex\Company\Helper\Data as CompanyData;
use Fedex\Delivery\Controller\Quote\Createpost;
use Fedex\Delivery\Helper\Data as DeliveryData;
use Fedex\Email\Helper\Data as EmailData;
use Fedex\Purchaseorder\Model\QuoteCreation;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Customer;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection as RateCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection as AddressCollection;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\Mars\Helper\PublishToQueue;
use Fedex\Mars\Model\Config as MarsConfig;

class CreatepostTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Magento\Quote\Api\CartRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteRepository;
    /**
     * @var (\Magento\Quote\Api\Data\CartInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteInterface;
    /**
     * @var (\Magento\Quote\Api\Data\CartExtensionInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteExtension;
    /**
     * @var (\Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $negotiableQuoteInterface;
    protected $address;
    protected $uploadToQuoteViewModel;
    protected $addressCollection;
    /**
     * @var (\Magento\Quote\Model\Quote\Address\Rate & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rate;
    /**
     * @var (\Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rateCollection;
    /**
     * @var (\Magento\Checkout\Model\CartFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartFactory;
    /**
     * @var (\Magento\Checkout\Model\Cart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cart;
    protected $quote;
    protected $item;
    /**
     * @var (\Magento\Quote\Model\Quote\Item\Option & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $option;
    protected $checkoutSession;
    protected $customerSession;
    /**
     * @var (\Magento\Customer\Model\Customer & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customer;
    protected $helper;
    /**
     * @var (\Fedex\SelfReg\Helper\SelfReg & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $selfRegMock;
    /**
     * @var (\Fedex\Company\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyHelper;
    protected $logger;
    /**
     * @var (\Fedex\Email\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $emailhelper;
    /**
     * @var (\Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $negotiableQuoteItemManagementInterface;
    /**
     * @var (\Magento\NegotiableQuote\Model\CommentManagementInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $commentManagementInterface;
    /**
     * @var (\Magento\NegotiableQuote\Model\Quote\History & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $history;
    /**
     * @var (\Fedex\Purchaseorder\Model\QuoteCreation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteCreation;
    /**
     * @var (\Magento\Directory\Model\RegionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $regionFactory;
    protected $region;
    /**
     * @var (\Magento\Framework\HTTP\Client\Curl & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $curl;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterface;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestMock;
    /**
     * @var (\Magento\Framework\Controller\Result\JsonFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonFactoryMock;
    protected $jsonMock;
    protected $toggleConfig;
    protected $quoteDataHelper;
    protected $shipToHelperMock;
    protected $publishMock;
    protected $marsConfigMock;
    protected $createpost;
    public const REQUEST_DATA = [
        'orderNumber' => '122334',
        'addressInformation' => [
            'pickup_location_state' => 'TEXAS',
            'pickup_location_country' => 'US',
            'pickup_location_street' => 'Legacy Dr',
            'pickup_location_zipcode' => '75024',
            'pickup_location_city' => 'Plano',
            'pickup_location_date' => '12/04/2021',
            'upload_to_quote_flow' => 'Flow 1',
            'shipping_address' => [
                'saveInAddressBook' => '1',
                'customAttributes' => [
                    '0' => [
                        'attribute_code' => 'email_id',
                        'value' => 'neeaj2.gupta@igglobal.com'
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
                'street' => [0 => 'street', 1 => 'street1'],
                'postcode' => 'postcode',
                'city' => 'city',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'telephone' => 'telephone',
                'company' => 'company',
            ],
            'billing_address' => [
                'customAttributes' => [
                    '0' => ['attribute_code' => 'email_id', 'value' => 'eeraj2.gupta@igglobal.com'],
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
                'productionLocation' => '3'
            ],
        ],
        'contactInformation' => [
            'alternate_fname' => 'alternate_fname',
            'alternate_lname' => 'alternate_lname',
            'alternate_email' => 'alternate_email',
            'alternate_number' => 'alternate_number',
            'alternate_ext' => 'alternate_ext',
            'contact_fname' => 'contact_fname',
            'contact_lname' => 'contact_lname',
            'contact_email' => 'contact_email',
            'contact_number' => '7087753785',
            'contact_ext' => 'contact_ext',
            'isAlternatePerson' => 1
        ],
        'quoteCreation' => 'quoteCreation',
        'quoteId' => 'quoteId',

    ];
    public const SHIPPING_DATA = [
        'shippingInformation' => [
            'addressInformation' => [
                'shipping_address' => [
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
                    'email' => 'recipientEmail',
                ],
                'billing_address' => ['region' => 'region',
                    'regionId' => 'regionId',
                    'regionCode' => 'regionCode',
                    'countryId' => 'countryId',
                    'street' => [0 => 'street'],
                    'postcode' => 'postcode',
                    'city' => 'city',
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'telephone' => 'telephone',
                    'email' => 'recipientEmail',
                ],
                'shipping_carrier_code' => 'carrier_code',
                'shipping_method_code' => 'method_code',
                'carrier_title' => 'carrier_title',
                'method_title' => 'method_title',
                'amount' => 'amount',
                'price_excl_tax' => 'price_excl_tax',
                'price_incl_tax' => 'price_incl_tax',
            ],
        ],
        'quoteCreation' => [
            'quoteId' => 1
        ],
    ];

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getActive', 'save'])
            ->getMockForAbstractClass();

        $this->quoteInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes','getId','getAppliedRuleIds'])
            ->getMockForAbstractClass();

        $this->quoteExtension = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setQuoteId','setIsRegularQuote','setAppliedRuleIds','setStatus','setQuoteName'])
            ->getMockForAbstractClass();

        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCollection',
                'save',
                'getAddressType',
                'getAddressId',
                'setStreet',
                'getData',
                'setSameAsBilling'
            ])
            ->getMock();

        $this->uploadToQuoteViewModel = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUploadToQuoteSuccessUrl',
                'updateQuoteStatusByKey',
                'isQuoteNegotiated',
                'updateLogHistory'
            ])
            ->getMock();

        $this->addressCollection = $this->getMockBuilder(AddressCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter','getIterator'])
            ->getMock();

        $this->rate = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection'])
            ->getMock();

        $this->rateCollection = $this->getMockBuilder(RateCollection::class)
            ->disableOriginalConstructor()
            //->setMethods(['getCollection'])
            ->getMock();

        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAllItems',
                'getId',
                'setIsActive',
                'save',
                'getShippingAddress',
                'getBillingAddress',
                'getExtensionAttributes'
                ])
            ->getMock();

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionByCode', 'getAddressType'])
            ->getMock();

        $this->option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
        ->setMethods(
            [
                'getAlternateContact',
                'unsAlternateContact',
                'setAlternatePickupPerson',
                'getAlternatePickupPerson',
                'unsAlternatePickupPerson',
                'unsCustomShippingMethodCode',
                'unsCustomShippingCarrierCode',
                'unsCustomShippingTitle',
                'unsCustomShippingPrice',
                'setCustomShippingMethodCode',
                'setCustomShippingCarrierCode',
                'setCustomShippingTitle',
                'setCustomShippingPrice',
                'clearQuote'
            ]
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer','logout','getFirstname','getContactNumber','getEmail','getLastname', 'setUploadToQuoteId'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','getEmail','getFirstName','getLastName','getContactNumber'])
            ->getMock();

        $this->helper = $this->getMockBuilder(DeliveryData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCompanySite',
                'getApiToken',
                'sendNotification',
                'getRedirectUrl',
                'isCommercialCustomer',
                'isSdeCustomer',
                'isAutoCartTransmissiontoERPToggleEnabled',
                'isPromiseTimeWarningToggleEnabled'
            ])->getMock();

        $this->helper->method('isPromiseTimeWarningToggleEnabled')->willReturn(true);

        $this->selfRegMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer'])
            ->getMockForAbstractClass();

        $this->companyHelper = $this->getMockBuilder(CompanyData::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyPaymentMethod','getFedexAccountNumber'])
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->emailhelper = $this->getMockBuilder(EmailData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteItemManagementInterface = $this->getMockBuilder(
            NegotiableQuoteItemManagementInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->commentManagementInterface = $this->getMockBuilder(CommentManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteCreation = $this->getMockBuilder(QuoteCreation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadByCode','load','getId'])
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();

        $this->jsonFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->quoteDataHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\QuoteDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getNegotiableQuoteCreateData',
                'setShippingInfomration',
                'createNegotiableQuote',
                'getContactDetails',
                'isValidContactInformation',
                'isValidateShippingDetailQuoteRequest',
                'setAlternateAddress',
                'getNewAddressData',
                'setShippingInformation',
                'checkNegotiableQuoteExistingForQuote'
                ])
            ->getMock();

        $this->shipToHelperMock = $this->getMockBuilder(\Fedex\Shipto\Helper\Data::class)
            ->setMethods(['getAddressByLocationId', 'formatAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->publishMock = $this->getMockBuilder(PublishToQueue::class)
            ->setMethods(['publish'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->marsConfigMock = $this->getMockBuilder(MarsConfig::class)
            ->setMethods(['isEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->createpost = $objectManagerHelper->getObject(
            Createpost::class,
            [
                'context' => $this->context,
                'quoteRepository' => $this->quoteRepository,
                'address' => $this->address,
                'rate' => $this->rate,
                'cartFactory' => $this->cartFactory,
                'checkoutSession' => $this->checkoutSession,
                'customerSession' => $this->customerSession,
                'helper' => $this->helper,
                'companyHelper' => $this->companyHelper,
                'logger' => $this->logger,
                'emailhelper' => $this->emailhelper,
                'negotiableQuoteItemManagementInterface' => $this->negotiableQuoteItemManagementInterface,
                'commentManagementInterface' => $this->commentManagementInterface,
                'history' => $this->history,
                'quoteCreation' => $this->quoteCreation,
                'regionFactory' => $this->regionFactory,
                'curl' => $this->curl,
                'configInterface' => $this->configInterface,
                '_request' => $this->requestMock,
                'toggleConfig'=> $this->toggleConfig,
                'region' => $this->region,
                'resultJsonFactory' => $this->jsonFactoryMock,
                'shipToHelper' => $this->shipToHelperMock,
                'selfregHelper' => $this->selfRegMock,
                'quoteDataHelper' => $this->quoteDataHelper,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModel,
                'publish' => $this->publishMock,
                'marsConfig' => $this->marsConfigMock
            ]
        );
    }

    /**
     * Test case for submitUploadToQuote
     */
    public function testSubmitUploadToQuote()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->testSetQuoteContactInformation();
        $this->testsaveQuoteAddress();
        $this->testGetShippingInformationData();
        $this->testClearShippingDataAndCheckoutSession();
        $this->quoteDataHelper->expects($this->once())->method('getNegotiableQuoteCreateData')
        ->willReturn(["quoteId" => 1]);
        $this->quoteDataHelper->expects($this->once())->method('setShippingInformation')->willReturnSelf();
        $this->quoteDataHelper->expects($this->once())->method('createNegotiableQuote')->willReturnSelf();
        $this->testClearShippingDataAndCheckoutSession();
        $this->uploadToQuoteViewModel->expects($this->once())->method('getUploadToQuoteSuccessUrl')->willReturnSelf();
        $this->checkoutSession->expects($this->once())->method('clearQuote')->willReturn(1);
        $this->customerSession->expects($this->once())->method('setUploadToQuoteId')->willReturnSelf();
        $this->uploadToQuoteViewModel->expects($this->once())->method('updateLogHistory')->willReturn(null);
        $this->uploadToQuoteViewModel->expects($this->once())->method('updateQuoteStatusByKey')->willReturn(null);
        $this->uploadToQuoteViewModel->expects($this->once())->method('isQuoteNegotiated')->willReturn(1);
        $this->marsConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->publishMock->expects($this->once())->method('publish')->willReturnSelf();

        $this->createpost->submitUploadToQuote($this->quote, $requestPostData, $this->jsonMock);
    }

    /**
     * Test case for submitUploadToQuote with exception
     */
    public function testSubmitUploadToQuoteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->quote->expects($this->any())->method('getId')->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical');

        $this->createpost->submitUploadToQuote($this->quote, $requestPostData, $this->jsonMock);
    }

    /**
     * Test case for setAlternatePickupInSession
     */
    public function testSetAlternatePickupInSession()
    {
        $this->checkoutSession->expects($this->any())->method('getAlternateContact')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('unsAlternateContact')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('setAlternatePickupPerson')->willReturn(1);
        $this->assertNull($this->createpost->setAlternatePickupInSession(1));
    }

    /**
     * Test case for setAlternatePickupInSession With False
     */
    public function testSetAlternatePickupInSessionWithNull()
    {
        $this->checkoutSession->expects($this->any())->method('getAlternatePickupPerson')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('unsAlternatePickupPerson')->willReturn(1);
        $this->assertNull($this->createpost->setAlternatePickupInSession(0));
    }

    /**
     * Test case for clearShippingDataAndCheckoutSession
     */
    public function testClearShippingDataAndCheckoutSession()
    {
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingMethodCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingCarrierCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingTitle')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingPrice')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->address->expects($this->any())->method('getData')->willReturnSelf();
        $this->assertNull($this->createpost->clearShippingDataAndCheckoutSession($this->quote));
    }

    /**
     * Test Case for getShippingInformationData
     */
    public function testGetShippingInformationData()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(1);
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getFirstname')->willReturn('Ayush');
        $this->customerSession->expects($this->any())->method('getLastname')->willReturn('Sood');
        $this->customerSession->expects($this->any())->method('getEmail')->willReturn('Ayush@gmail.com');
        $this->customerSession->expects($this->any())->method('getContactNumber')->willReturn('798765432');
        $this->assertNotNull($this->createpost->getShippingInformationData($requestPostData));
    }

    /**
     * Test Case for getAlternateContact
     */
    public function testgetAlternateContact()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->assertNotNull($this->createpost->getAlternateContact($requestPostData));
    }

    /**
     * Test Case for  getContactDetails
     */
    public function testGetContactDetails()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->assertNotNull($this->createpost->getContactDetails($requestPostData));
    }

    /**
     * Test case for getPickupAddress
     */
    public function testGetPickupAddress()
    {
        $tempAddress = ['success' => 1, 'address' => 'Random Address'];
        $this->shipToHelperMock->expects($this->any())->method('getAddressByLocationId')->willReturn($tempAddress);
        $this->assertNotNull($this->createpost->getPickupAddress('PICKUP', 1));
    }

    /**
     * Test Case for setQuoteContactInformation
     */
    public function testSetQuoteContactInformation()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setSameAsBilling')->willReturn(false);
        $this->assertNull($this->createpost->setQuoteContactInformation($this->quote, $requestPostData));
    }

    /**
     * Test Case for setQuoteContactInformationWithAlternate
     */
    public function testSetQuoteContactInformationWithAlternate()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setSameAsBilling')->willReturn(false);
        $this->assertNull($this->createpost->setQuoteContactInformation($this->quote, $requestPostData));
    }

    /**
     * Test Case for setShippingDataInCheckoutSession
     */
    public function testSetShippingDataInCheckoutSession()
    {
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setSameAsBilling')->willReturn(false);
        $this->checkoutSession->expects($this->any())->method('setCustomShippingMethodCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setCustomShippingCarrierCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setCustomShippingTitle')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setCustomShippingPrice')->willReturnSelf();
        $this->assertNull($this->createpost->setShippingDataInCheckoutSession(self::SHIPPING_DATA));
    }

    /**
     * Test case for setQuoteItemsData
     */
    public function testSetQuoteItemsData()
    {
        $alternateContactInformation = [
            'afirstName' => 'afirstName',
            'alastName' => 'alastName',
            'aEmail' => 'aEmail',
            'aNumber' => 'aNumber',
            'aExtNo' => 'aExtNo'
        ];
        $contactInformation = [
            'firstName' => 'afirstName',
            'lastName' => 'alastName',
            'email' => 'aEmail',
            'number' => 'aNumber',
            'ext_no' => 'aExtNo'
        ];
        $this->item->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $this->assertNull(
            $this->createpost
            ->setQuoteItemsData(1, $this->item, $alternateContactInformation, $contactInformation, 'test')
        );
    }

    /**
     * Test case for setQuoteItemsDataWithAlternate
     */
    public function testSetQuoteItemsDataWithAlternate()
    {
        $alternateContactInformation = [
            'afirstName' => 'afirstName',
            'alastName' => 'alastName',
            'aEmail' => 'aEmail',
            'aNumber' => 'aNumber',
            'aExtNo' => 'aExtNo'
        ];
        $contactInformation = [
            'firstName' => 'afirstName',
            'lastName' => 'alastName',
            'email' => 'aEmail',
            'number' => 'aNumber',
            'ext_no' => 'aExtNo'
        ];
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->item->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $this->assertNull(
            $this->createpost
            ->setQuoteItemsData(1, $this->item, $alternateContactInformation, $contactInformation, 'test')
        );
    }

    /**
     * Test case for saveQuoteAddress
     */
    public function testsaveQuoteAddress()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->address->expects($this->any())->method('getCollection')
        ->will($this->returnValue($this->addressCollection));
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->address->expects($this->any())->method('save')->willReturnSelf();
        $this->address->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $iterator = new \ArrayIterator([45 => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->assertNull($this->createpost->saveQuoteAddress(1, $requestPostData));
    }

    /**
     * Test case for savePickupTime
     */
    public function testsavePickupTime()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->assertNull($this->createpost->savePickupTime($this->quote, $requestPostData));
    }

    /**
     * Test Case for validateRequestData
     */
    public function testvalidateRequestData()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->assertNotNull($this->createpost->validateRequestData($this->quote, $requestPostData, false));
    }

    /**
     * Test Case for validateRequestData without commercial customer
     */
    public function testvalidateRequestDataWithoutCommercialCustomer()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertNotNull($this->createpost->validateRequestData($this->quote, $requestPostData, false));
    }

    /**
     * Test Case for validateRequestData with false
     */
    public function testvalidateRequestDataWithFalse()
    {
        $requestPostData = json_decode(json_encode(self::REQUEST_DATA));
        $this->assertNotNull($this->createpost->validateRequestData($this->quote, $requestPostData, false));
    }
}
