<?php

namespace Fedex\Delivery\Test\Unit\Controller\Quote;

use Exception;
use Fedex\Delivery\Controller\Quote\Create;
use Fedex\Delivery\Helper\Data as DeliveryData;
use Fedex\Email\Helper\Data as EmailData;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Purchaseorder\Model\QuoteCreation;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection as AddressCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Magento\Framework\Exception\LocalizedException;
use Fedex\InBranch\Model\InBranchValidation;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Magento\Quote\Api\CartItemRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;


class CreateTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Model\AbstractModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractHelper;
    protected $selfRegMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    /**
     * @var (\Fedex\InBranch\Model\InBranchValidation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $inBranchValidationMock;
    protected $dataAddressInterfaceMock;
    protected $jsonFactory;
    protected $jsonMock;
    protected $company;
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
    protected $item;
    protected $quoteDataHelper;
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepository;

    /**
     * @var Address|MockObject
     */
    protected $address;

    /**
     * @var AddressCollection|MockObject
     */
    protected $addressCollection;

    /**
     * @var CartFactory|MockObject
     */
    protected $cartFactory;

    /**
     * @var Cart|MockObject
     */
    protected $cart;

    /**
     * @var CheckoutSession|MockObject
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSession;

    /**
     * @var Customer|MockObject
     */
    protected $customer;

    /**
     * @var DeliveryData|MockObject
     */
    protected $helper;

    /**
     * @var PunchoutHelper|MockObject
     */
    protected $punchoutHelper;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var EmailData|MockObject
     */
    protected $emailhelper;

    /**
     * @var NegotiableQuoteItemManagementInterface|MockObject
     */
    protected $negotiableQuoteItemManagementInterface;

    /**
     * @var CommentManagementInterface|MockObject
     */
    protected $commentManagementInterface;

    /**
     * @var History|MockObject
     */
    protected $history;

    /**
     * @var QuoteCreation|MockObject
     */
    protected $quoteCreation;

    /**
     * @var RegionFactory|MockObject
     */
    protected $regionFactory;

    /**
     * @var Region|MockObject
     */
    protected $region;

    /**
     * @var AddressInterfaceFactory|MockObject
     */
    protected $addressInterfaceFactory;

    /**
     * @var AddressRepositoryInterface|MockObject
     */
    protected $addressRepositoryInterface;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    protected $companyRepositoryInterface;

    /**
     * @var Quote|MockObject
     */
    protected $quote;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Create|MockObject
     */
    protected $create;

    private MarketplaceCheckoutHelper|MockObject $marketplaceCheckoutHelper;
    private CartItemRepositoryInterface|MockObject $cartItemRepository;

    public const CONTACT_INFORMATION = [
        'firstName' => 'firstname',
        'lastName' => 'lastname',
        'telephone' => 'telephone',
        'email' =>'email',
        'company' => 'company',
        'ext_no' => 'ext_no',
        'number' => 'number',
        'region' => 'region',
        'region_id' => 'regionId',
        'regionCode' => 'regionCode',
        'country_id' => 'countryId',
        'street' => [0 => 'street', 1 => 'street1'],
        'postcode' => 'postcode',
        'city' => 'city'
    ];

    public const REQUEST_DATA = [
        'orderNumber' => '122334',
        'addressInformation' => [
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
            'shipping_carrier_code' => 'carrier_code',
            'shipping_method_code' => 'method_code',
        ]
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
        ]
    ];

    public const REQUEST_POST_DATA = '{"addressInformation":{"shipping_address":{"countryId":"US","regionId":"169","regionCode":"TX","region":"Texas","street":["Legacy dr"],"company":"","telephone":"7087753785","postcode":"75024","city":"Plano","firstname":"Ayush","lastname":"Sood","customAttributes":[{"attribute_code":"email_id","value":"ayush.sood@infogain.com"},{"attribute_code":"ext","value":""}],"altFirstName":"","altLastName":"","altPhoneNumber":"","altEmail":"","altPhoneNumberext":"","is_alternate":false},"billing_address":{"countryId":"US","regionId":"169","regionCode":"TX","region":"Texas","street":["Legacy dr"],"company":"","telephone":"7087753785","postcode":"75024","city":"Plano","firstname":"Ayush","lastname":"Sood","customAttributes":[{"attribute_code":"email_id","value":"ayush.sood@infogain.com"},{"attribute_code":"ext","value":""}],"altFirstName":"","altLastName":"","altPhoneNumber":"","altEmail":"","altPhoneNumberext":"","is_alternate":false,"saveInAddressBook":null},"shipping_method_code":"LOCAL_DELIVERY_PM","shipping_carrier_code":"fedexshipping","shipping_detail":{"carrier_code":"fedexshipping","method_code":"LOCAL_DELIVERY_PM","carrier_title":"FedEx Local Delivery","method_title":"Thursday, December 22 5:00 pm","amount":19.99,"base_amount":19.99,"available":true,"error_message":"","price_excl_tax":19.99,"price_incl_tax":19.99,"fedexShipAccountNumber":"","fedexShipReferenceId":"","productionLocation":""}},"rateapi_response":"{\"currency\":\"USD\",\"rateDetails\":[{\"productLines\":[{\"instanceId\":\"38676\",\"productId\":\"1463680545590\",\"name\":\"Flyer\",\"userProductName\":\"nature5\",\"retailPrice\":\"$0.00\",\"discountAmount\":\"$0.00\",\"unitQuantity\":50,\"linePrice\":\"$0.00\",\"unitOfMeasurement\":\"EACH\",\"priceable\":true,\"productLineDetails\":[{\"detailCode\":\"40005\",\"description\":\"Full Pg Clr Flyr 50\",\"detailCategory\":\"PRINTING\",\"unitQuantity\":1,\"detailPrice\":\"$34.99\",\"detailDiscountPrice\":\"$0.00\",\"detailUnitPrice\":\"$34.9900\",\"detailDiscountedUnitPrice\":\"$0.00\"}],\"productRetailPrice\":\"$34.99\",\"productDiscountAmount\":\"$0.00\",\"productLinePrice\":\"$34.99\",\"editable\":false}],\"deliveryLines\":[{\"recipientReference\":\"1\",\"linePrice\":\"$0.00\",\"estimatedDeliveryLocalTime\":\"2022-12-21T17:00:00\",\"estimatedShipDate\":\"2022-12-21\",\"deliveryLinePrice\":\"$19.99\",\"deliveryLineType\":\"SHIPPING\",\"priceable\":true,\"shipmentDetails\":{\"address\":{\"streetLines\":[\"Legacy dr\"],\"city\":\"Plano\",\"stateOrProvinceCode\":\"TX\",\"postalCode\":\"75024\",\"countryCode\":\"US\",\"addressClassification\":\"HOME\"},\"serviceType\":\"LOCAL_DELIVERY_PM\"},\"deliveryRetailPrice\":\"$19.99\",\"deliveryDiscountAmount\":\"$0.00\"},{\"recipientReference\":\"1\",\"linePrice\":\"$0.00\",\"deliveryLinePrice\":\"$0.00\",\"deliveryLineType\":\"PACKING_AND_HANDLING\",\"priceable\":true,\"deliveryRetailPrice\":\"$0.00\",\"deliveryDiscountAmount\":\"$0.00\"}],\"grossAmount\":\"$54.98\",\"totalDiscountAmount\":\"$0.00\",\"netAmount\":\"$54.98\",\"taxableAmount\":\"$54.98\",\"taxAmount\":\"$4.54\",\"totalAmount\":\"$59.52\",\"estimatedVsActual\":\"ACTUAL\"}]}"}';

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getActive', 'save'])
            ->getMockForAbstractClass();

        $this->address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCollection',
                'save',
                'getAddressType',
                'getAddressId',
                'setCompany',
                'getData',
                'setIsResidenceShipping'
            ])->getMock();

        $this->addressCollection = $this->getMockBuilder(AddressCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getIterator'])
            ->getMock();

        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->abstractHelper = $this->getMockBuilder(\Magento\Framework\Model\AbstractModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getId'])
            ->getMock();

        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->selfRegMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->inBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getProductionLocationId',
                    'unsProductionLocationId',
                    'unsCustomShippingMethodCode',
                    'unsCustomShippingCarrierCode',
                    'unsCustomShippingTitle',
                    'unsCustomShippingPrice',
                    'clearQuote',
                    'setCustomShippingMethodCode',
                    'setCustomShippingCarrierCode',
                    'setCustomShippingTitle',
                    'setCustomShippingPrice',
                    'setProductionLocationId',
                    'getAlternateContact',
                    'unsAlternateContact',
                    'getAlternatePickup',
                    'unsAlternatePickup',
                    'setAlternateContact',
                    'getLocationIds',
                    'getAddressClassification'
                ]
            )
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'logout', 'getCustomerCompany','setLastCustomerId'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getEmail', 'getFirstName', 'getLastName','setLastCustomerId'])
            ->getMock();

        $this->helper = $this->getMockBuilder(DeliveryData::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCompanySite',
                    'getApiToken',
                    'sendNotification',
                    'getRedirectUrl',
                    'isEproCustomer',
                    'getCustomer',
                    'isCommercialCustomer',
                    'isSdeCustomer',
                    'updateCartItemPrice',
                    'updateQuotePrice',
                    'isAutoCartTransmissiontoERPToggleEnabled'
                ]
            )
            ->getMock();

        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->emailhelper = $this->getMockBuilder(EmailData::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendEmailNotification'])
            ->getMock();

        $this->negotiableQuoteItemManagementInterface =
            $this->getMockBuilder(NegotiableQuoteItemManagementInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

        $this->commentManagementInterface = $this->getMockBuilder(CommentManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->setMethods(['createLog'])
            ->getMock();

        $this->quoteCreation = $this->getMockBuilder(QuoteCreation::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveShippingAddress'])
            ->getMock();

        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();

        $this->addressInterfaceFactory = $this->getMockBuilder(AddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->dataAddressInterfaceMock = $this->getMockBuilder(AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->addressRepositoryInterface = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMockForAbstractClass();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->companyRepositoryInterface = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllowProductionLocation', 'getProductionLocationOption'])
            ->getMockForAbstractClass();

        $this->quoteInterface = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getId', 'getAppliedRuleIds', 'setData', 'setCompany', 'setIsResidenceShipping'])
            ->getMockForAbstractClass();

        $this->quoteExtension = $this->getMockBuilder(CartExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setQuoteId',
                    'setIsRegularQuote',
                    'setAppliedRuleIds',
                    'setStatus',
                    'setQuoteName',
                    'getData'
                ]
            )
            ->getMockForAbstractClass();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getAllItems',
                    'getCustomerId',
                    'getId',
                    'save',
                    'setData',
                    'setShippingCost',
                    'setProductionLocationId',
                    'getBillingAddress',
                    'getShippingAddress',
                    'getCustomerEmail',
                    'getExtensionAttributes',
                    'getCoupon',
                    'getAllVisibleItems',
                    'setIsActive'
                ]
            )
            ->getMock();

        $this->item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionByCode', 'getAddressType'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();
        $this->quoteDataHelper = $this->getMockBuilder(QuoteDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getNewAddressData',
                    'isValidateShippingDetailQuoteRequest',
                    'getContactDetails',
                    'getStateCode',
                    'setQuoteData',
                    'setAlternateAddress',
                    'unsetAddressInformation',
                    'createNegotiableQuote',
                    'checkNegotiableQuoteExistingForQuote'
                ]
            )
            ->getMock();
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);
        $this->cartItemRepository = $this->createMock(CartItemRepositoryInterface::class);
        $objectManagerHelper = new ObjectManager($this);
        $this->create = $objectManagerHelper->getObject(
            Create::class,
            [
                'context' => $this->context,
                'quoteRepository' => $this->quoteRepository,
                'address' => $this->address,
                'cartFactory' => $this->cartFactory,
                'checkoutSession' => $this->checkoutSession,
                'customerSession' => $this->customerSession,
                'helper' => $this->helper,
                'punchoutHelper' => $this->punchoutHelper,
                'logger' => $this->logger,
                'emailhelper' => $this->emailhelper,
                'negotiableQuoteItemManagementInterface' => $this->negotiableQuoteItemManagementInterface,
                'commentManagementInterface' => $this->commentManagementInterface,
                'history' => $this->history,
                'quoteCreation' => $this->quoteCreation,
                'regionFactory' => $this->regionFactory,
                'dataAddressFactory' => $this->addressInterfaceFactory,
                'addressRepository' => $this->addressRepositoryInterface,
                'resultJsonFactory' => $this->jsonFactory,
                'toggleConfig' => $this->toggleConfigMock,
                'companyRepository' => $this->companyRepositoryInterface,
                'request' => $this->requestMock,
                'selfregHelper' => $this->selfRegMock,
                'quoteDataHelper' => $this->quoteDataHelper,
                'customer' => $this->customer,
                'inBranchValidation'=>$this->inBranchValidationMock,
                'marketplaceCheckoutHelper' => $this->marketplaceCheckoutHelper
            ]
        );
    }

    /**
     * Test case for saveAddress
     */
    public function testsaveAddress()
    {
        $this->quoteDataHelper->expects($this->any())->method('getNewAddressData')
            ->willReturn($this->dataAddressInterfaceMock);
        $this->addressRepositoryInterface->expects($this->any())->method('save')
            ->willReturnSelf();
        $this->assertNull($this->create->saveAddress(json_encode(self::REQUEST_DATA), '1'));
    }

    /**
     * Test case for saveAddress with Exception
     */
    public function testsaveAddressWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->quoteDataHelper->expects($this->any())->method('getNewAddressData')
            ->willReturn($this->dataAddressInterfaceMock);
        $this->addressRepositoryInterface->expects($this->any())->method('save')
            ->willThrowException($exception);
        $this->assertNull($this->create->saveAddress(json_encode(self::REQUEST_DATA), '1'));
    }

    /**
     * Test case for setShippingMethoodDetailsInCheckoutSession
     */
    public function testsetShippingMethoodDetailsInCheckoutSession()
    {
        $this->assertNull($this->create->setShippingMethoodDetailsInCheckoutSession(self::REQUEST_DATA));
    }

    /**
     * Test case for checkoutSaveAddressAndClearSession
     */
    public function testcheckoutSaveAddressAndClearSession()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCustomerId')->willReturn(2);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('getData')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingMethodCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingCarrierCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingTitle')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingPrice')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsProductionLocationId')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('clearQuote')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('logout')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getId')->willReturn(2);
        $this->customerSession->expects($this->any())->method('setLastCustomerId')->willReturnSelf();
        $this->requestMock->expects($this->any())
            ->method('getPost')->willReturn(json_encode(self::REQUEST_DATA));
        $this->testsaveAddress();
        $this->assertTrue($this->create
            ->checkoutSaveAddressAndClearSession($this->quote, json_encode(self::REQUEST_DATA)));
    }

    /**
     * Test case for setPreferredLocation
     */
    public function testSetPreferredLocation()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn(1);
        $this->companyRepositoryInterface->expects($this->any())->method('get')->willReturn($this->company);
        $this->company->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->company->expects($this->any())->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');
        $this->checkoutSession->expects($this->any())
            ->method('setProductionLocationId')->willReturnSelf();
        $this->assertNull($this->create->setPreferredLocation($this->quote, json_encode(self::REQUEST_DATA)));
    }

    /**
     * Test case for updateQuotePrices
     */
    public function testupdateQuotePrices()
    {
        $requestPostData = [
            'rateapi_response' => '{
                "currency":"USD",
                "rateDetails":[
                   {
                      "productLines":[
                         {
                            "instanceId":"38660",
                            "productId":"1463680545590",
                            "name":"Flyer",
                            "userProductName":"nature5",
                            "retailPrice":"$0.00",
                            "discountAmount":"$0.00",
                            "unitQuantity":50,
                            "linePrice":"$0.00",
                            "unitOfMeasurement":"EACH",
                            "priceable":true,
                            "productLineDetails":[
                               {
                                  "detailCode":"40005",
                                  "description":"Full Pg Clr Flyr 50",
                                  "detailCategory":"PRINTING",
                                  "unitQuantity":1,
                                  "detailPrice":"$34.99",
                                  "detailDiscountPrice":"$0.00",
                                  "detailUnitPrice":"$34.9900",
                                  "detailDiscountedUnitPrice":"$0.00"
                               }
                            ],
                            "productRetailPrice":"$34.99",
                            "productDiscountAmount":"$0.00",
                            "productLinePrice":"$34.99",
                            "editable":false
                         }
                      ],
                      "deliveryLines":[
                         {
                            "recipientReference":"1",
                            "linePrice":"$0.00",
                            "estimatedDeliveryLocalTime":"2022-12-02T08:00:00",
                            "estimatedShipDate":"2022-12-01",
                            "deliveryLinePrice":"$77.84",
                            "deliveryLineType":"SHIPPING",
                            "priceable":true,
                            "shipmentDetails":{
                               "address":{
                                  "streetLines":[
                                     "234"
                                  ],
                                  "city":"Plantation",
                                  "stateOrProvinceCode":"FL",
                                  "postalCode":"33324",
                                  "countryCode":"US",
                                  "addressClassification":"BUSINESS"
                               },
                               "serviceType":"FIRST_OVERNIGHT"
                            },
                            "deliveryRetailPrice":"$77.84",
                            "deliveryDiscountAmount":"$0.00"
                         },
                         {
                            "recipientReference":"1",
                            "linePrice":"$0.00",
                            "deliveryLinePrice":"$0.00",
                            "deliveryLineType":"PACKING_AND_HANDLING",
                            "priceable":true,
                            "deliveryRetailPrice":"$0.00",
                            "deliveryDiscountAmount":"$0.00"
                         }
                      ],
                      "grossAmount":"$112.83",
                      "totalDiscountAmount":"$0.00",
                      "netAmount":"$112.83",
                      "taxableAmount":"$112.83",
                      "taxAmount":"$2.45",
                      "totalAmount":"$115.28",
                      "estimatedVsActual":"ACTUAL"
                   }
                ]
             }'
        ];
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->quote->expects($this->any())->method('getCoupon')->willReturn('MGT001');
        $this->helper->expects($this->any())->method('updateCartItemPrice')->willReturnSelf();
        $this->helper->expects($this->any())->method('updateQuotePrice')->willReturnSelf();
        $this->assertNull($this->create
            ->updateQuotePrices($this->quote, json_decode(json_encode($requestPostData)), false));
    }

    /**
     * Test case for updateQuotePrices with no data
     */
    public function testupdateQuotePricesWithNoData()
    {
        $requestPostData = [
            'rateapi_response' => ''
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->quote->expects($this->any())->method('getCoupon')->willReturn('MGT001');
        $this->assertNull($this->create
            ->updateQuotePrices($this->quote, json_decode(json_encode($requestPostData)), false));
    }

    /**
     * Test case for saveQuoteAddress
     */
    public function testsaveQuoteAddress()
    {
        $id = 2;
        $this->address->expects($this->any())->method('getAddressType')->willReturn('billing');
        $this->address->expects($this->any())->method('getCollection')->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $iterator = new \ArrayIterator([$id => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->checkoutSession->expects($this->any())->method('getAlternateContact')->willReturn(true);
        $this->assertNull($this->create
            ->saveQuoteAddress('2', self::CONTACT_INFORMATION, 'Infogain', json_decode(self::REQUEST_POST_DATA)));
    }

    /**
     * Test case for saveQuoteAddress without AddressType
     */
    public function testsaveQuoteAddressWithoutAddressType()
    {
        $id = 2;
        $this->address->expects($this->any())->method('getCollection')->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $iterator = new \ArrayIterator([$id => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->checkoutSession->expects($this->any())->method('getAlternateContact')->willReturn(true);
        $this->assertNull($this->create
            ->saveQuoteAddress('2', self::CONTACT_INFORMATION, 'Infogain', json_decode(self::REQUEST_POST_DATA)));
    }

    /**
     * Test case for saveQuoteAddress without AddressType
     */
    public function testSetQuoteItemsData()
    {
        $this->item->expects($this->any())->method('getAddressType')->willReturn('shipping');

        $this->assertNull($this->create->setQuoteItemsData($this->item, self::CONTACT_INFORMATION, json_decode(self::REQUEST_POST_DATA)));
    }

    /**
     * Test case for saveQuoteAddress without AddressType
     */
    public function testSetQuoteItemsDataWithAlternate()
    {
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->helper->expects($this->any())->method('isSdeCustomer')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->item->expects($this->any())->method('getAddressType')->willReturn('shipping');

        $this->assertNull($this->create->setQuoteItemsData($this->item, self::CONTACT_INFORMATION, json_decode(self::REQUEST_POST_DATA)));
    }


    /**
     * Test case for validateRequestData
     */
    public function testvalidateRequestData()
    {
        $returnData = [
            'error' => '1',
            'url' => '',
            'message' => 'Shipping information is missing from request data. Please try again.'
        ];
        $this->quoteDataHelper->expects($this->any())->method('isValidateShippingDetailQuoteRequest')
            ->willReturn(false);
        $this->assertEquals(
            $returnData,
            $this->create->validateRequestData($this->quote, true, self::REQUEST_DATA, false)
        );
    }

    /**
     * Test case for validateRequestData with no customer
     */
    public function testvalidateRequestDataWithNoEPRO()
    {
        $this->quoteDataHelper->expects($this->any())->method('isValidateShippingDetailQuoteRequest')
            ->willReturn(false);
        $this->assertFalse($this->create->validateRequestData($this->quote, false, self::REQUEST_DATA, false));
    }

    /**
     * Test case for validateRequestData With No Data
     */
    public function testvalidateRequestDataWithNoData()
    {
        $returnData = [
            'error' => '1',
            'url' => '',
            'message' => 'There was a problem in creating quote. Please try again.'
        ];
        $this->quoteDataHelper->expects($this->any())->method('isValidateShippingDetailQuoteRequest')
            ->willReturn(false);
        $this->assertEquals(
            $returnData,
            $this->create->validateRequestData($this->quote, true, null, false)
        );
    }

    /**
     * Test case for Execute
     */
    public function testExecute()
    {
        $id = 2;
        $contactInformation = [
            'email' => 'test@test.com',
            'firstName' => 'firstname',
            'lastName' => 'lastname',
            'telephone' => 'telephone',
            'company' => 'company',
            'ext_no' => 'ext_no',
            'number' => 'number',
            'region' => 'region',
            'region_id' => 'regionId',
            'regionCode' => 'regionCode',
            'country_id' => 'countryId',
            'street' => [0 => 'street', 1 => 'street1'],
            'postcode' => 'postcode',
            'city' => 'city'
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())->method('getPost')->willReturn(json_encode(self::REQUEST_DATA));
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->helper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->selfRegMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->quoteDataHelper->expects($this->any())->method('getStateCode')->willReturn('TX');
        $this->quoteDataHelper->expects($this->any())->method('getContactDetails')->willReturn($contactInformation);
        $this->quoteDataHelper->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->quoteDataHelper->expects($this->any())->method('setAlternateAddress')->willReturnSelf();
        $this->quoteRepository->expects($this->any())->method('getActive')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();
        $this->quote->expects($this->any())->method('getBillingAddress')->willReturn($this->address);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setIsResidenceShipping')->willReturn(true);
        $this->address->expects($this->any())->method('setCompany')->willReturn('Infogain');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->address->expects($this->any())->method('getCollection')->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $iterator = new \ArrayIterator([$id => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
        ->willReturn(false);
        $this->testcheckoutSaveAddressAndClearSession();
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn(1);
        $this->companyRepositoryInterface->expects($this->any())->method('get')->willReturn($this->company);
        $this->company->expects($this->any())->method('getAllowProductionLocation')->willReturn(1);
        $this->company->expects($this->any())->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');
        $this->checkoutSession->expects($this->any())
            ->method('setProductionLocationId')->willReturnSelf();
        $this->assertNull($this->create->setPreferredLocation($this->quote, json_encode(self::REQUEST_DATA)));
        $this->assertNull($this->create->execute());
    }

    /**
     * Test case for Execute
     */
    public function testExecuteWithMock()
    {
        $id = 2;
        $contactInformation = [
            'email' => 'test@test.com',
            'firstName' => 'firstname',
            'lastName' => 'lastname',
            'telephone' => 'telephone',
            'company' => 'company',
            'ext_no' => 'ext_no',
            'number' => 'number',
            'region' => 'region',
            'region_id' => 'regionId',
            'regionCode' => 'regionCode',
            'country_id' => 'countryId',
            'street' => [0 => 'street', 1 => 'street1'],
            'postcode' => 'postcode',
            'city' => 'city'
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())->method('getPost')->willReturn(json_encode(self::REQUEST_DATA));
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->helper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->selfRegMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->quoteDataHelper->expects($this->any())->method('getStateCode')->willReturn('TX');
        $this->quoteDataHelper->expects($this->any())->method('getContactDetails')->willReturn($contactInformation);
        $this->quoteDataHelper->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->quoteDataHelper->expects($this->any())->method('setAlternateAddress')->willReturnSelf();
        $this->quoteRepository->expects($this->any())->method('getActive')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCompany')->willReturn('Infogain');
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();
        $item->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"is_marketplace_mocked": true}');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$item]);
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->address->expects($this->any())->method('getCollection')->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $iterator = new \ArrayIterator([$id => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->testcheckoutSaveAddressAndClearSession();
        $this->assertNull($this->create->execute());
    }

    /**
     * Test case for Execute With Invalid Request
     */
    public function testExecuteWithInvalidRequest()
    {
        $id = 2;
        $contactInformation = [
            'email' => 'test@test.com',
            'firstName' => 'firstname',
            'lastName' => 'lastname',
            'telephone' => 'telephone',
            'company' => 'company',
            'ext_no' => 'ext_no',
            'number' => 'number',
            'region' => 'region',
            'region_id' => 'regionId',
            'regionCode' => 'regionCode',
            'country_id' => 'countryId',
            'street' => [0 => 'street', 1 => 'street1'],
            'postcode' => 'postcode',
            'city' => 'city'
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())->method('getPost')->willReturn(json_encode(self::REQUEST_DATA));
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->helper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->selfRegMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $this->quoteDataHelper->expects($this->any())->method('getStateCode')->willReturn('TX');
        $this->quoteDataHelper->expects($this->any())->method('getContactDetails')->willReturn($contactInformation);
        $this->quoteDataHelper->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->quoteDataHelper->expects($this->any())->method('setAlternateAddress')->willReturnSelf();
        $this->quoteRepository->expects($this->any())->method('getActive')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCompany')->willReturn('Infogain');
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->address->expects($this->any())->method('getCollection')->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $iterator = new \ArrayIterator([$id => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->testcheckoutSaveAddressAndClearSession();
        $this->assertNull($this->create->execute());
    }

    /**
     * Test case for Execute For EPRO
     */
    public function testExecuteForEPRO()
    {
        $id = 2;
        $contactInformation = [
            'email' => 'test@test.com',
            'firstName' => 'firstname',
            'lastName' => 'lastname',
            'telephone' => 'telephone',
            'company' => 'company',
            'ext_no' => 'ext_no',
            'number' => 'number',
            'region' => 'region',
            'region_id' => 'regionId',
            'regionCode' => 'regionCode',
            'country_id' => 'countryId',
            'street' => [0 => 'street', 1 => 'street1'],
            'postcode' => 'postcode',
            'city' => 'city'
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturn(1);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->requestMock);
        $this->requestMock->expects($this->any())->method('getPost')->willReturn(json_encode(self::REQUEST_DATA));
        $this->quoteDataHelper->expects($this->any())->method('isValidateShippingDetailQuoteRequest')
            ->willReturn(true);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->helper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->selfRegMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        $this->quoteDataHelper->expects($this->any())->method('getStateCode')->willReturn('TX');
        $this->quoteDataHelper->expects($this->any())->method('getContactDetails')->willReturn($contactInformation);
        $this->quoteDataHelper->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->quoteDataHelper->expects($this->any())->method('setAlternateAddress')->willReturnSelf();
        $this->quoteRepository->expects($this->any())->method('getActive')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('setData')->willReturnSelf();
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->address);
        $this->address->expects($this->any())->method('setCompany')->willReturn('Infogain');
        $this->quote->expects($this->any())->method('save')->willReturnSelf();
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->address->expects($this->any())->method('getCollection')->willReturn($this->addressCollection);
        $this->addressCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $iterator = new \ArrayIterator([$id => $this->address]);
        $this->addressCollection->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->testcheckoutSaveAddressAndClearSession();
        $this->testSetPreferredLocation();
        $this->quoteDataHelper->expects($this->any())->method('createNegotiableQuote')->willReturnSelf();
        $this->assertNull($this->create->execute());
    }

    /**
     * Test Case for setAlternateContactInSession
     */
    public function testSetAlternateContactInSession()
    {
        $this->checkoutSession->expects($this->any())->method('getAlternateContact')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getAlternatePickup')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAlternatePickup')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAlternateContact')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setAlternateContact')->willReturnSelf();
        $this->assertNull($this->create->setAlternateContactInSession(true));
    }
    /**
     * Test Case for setAlternateContactInSession
     */
    public function testSetAlternateContactInSessionWithFalse()
    {
        $this->checkoutSession->expects($this->any())->method('getAlternateContact')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('getAlternatePickup')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAlternatePickup')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsAlternateContact')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('setAlternateContact')->willReturnSelf();
        $this->assertNull($this->create->setAlternateContactInSession(false));
    }
    /**
     * Test Case for saveShippingAccountNumber
     */
    public function testSaveShippingAccountNumber()
    {
        $shippingMethodCode =
            [
                'addressInformation'=>
                    [
                        'shipping_method_code' => 'LOCAL_DELIVERY_AM'
                    ]
            ];
        $fedExShippingAccountNumber = '123123';

        $this->assertNull(
            $this->create->saveShippingAccountNumber(
                $shippingMethodCode,
                $this->quote,
                $fedExShippingAccountNumber
            )
        );
    }

    /**
     * Test: Items with MiraklOfferId, valid additional data, Essendant enabled
     */
    public function testSaveFedexShippingAccountEssendantEnabledSuccess(): void
    {
        $fedexShipAccountNumber = '12345';
        $fedexShipReferenceId = 'ref-abc';

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->getMock();
        $item->method('getMiraklOfferId')->willReturn('offer1');
        $item->method('getAdditionalData')->willReturn(json_encode([
            'mirakl_shipping_data' => ['foo' => 'bar']
        ]));
        $item->expects($this->once())
            ->method('setAdditionalData')
            ->with($this->callback(function ($json) use ($fedexShipAccountNumber, $fedexShipReferenceId) {
                $data = json_decode($json, true);
                return $data['mirakl_shipping_data']['fedexShipAccountNumber'] === $fedexShipAccountNumber
                    && $data['mirakl_shipping_data']['fedexShipReferenceId'] === $fedexShipReferenceId;
            }));
        $quote = $this->createMock(Quote::class);
        $quote->method('getItems')->willReturn([$item]);
        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);
        $this->create->saveFedexShippingAccount($quote, $fedexShipAccountNumber, $fedexShipReferenceId);
    }

    /**
     * Test: Items with MiraklOfferId, valid additional data, Essendant disabled
     */
    public function testSaveFedexShippingAccountEssendantDisabledSuccess(): void
    {
        $fedexShipAccountNumber = '12345';
        $fedexShipReferenceId = 'ref-abc';

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->getMock();
        $item->method('getMiraklOfferId')->willReturn(123);
        $item->method('getAdditionalData')->willReturn(json_encode([
            'mirakl_shipping_data' => ['foo' => 'bar']
        ]));
        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(false);
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllItems')->willReturn([$item]);
        $this->create->saveFedexShippingAccount($quote, $fedexShipAccountNumber, $fedexShipReferenceId);
    }

    /**
     * Test: Item without MiraklOfferId is skipped
     */
    public function testSaveFedexShippingAccountItemWithoutMiraklOfferId(): void
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->getMock();
        $item->method('getMiraklOfferId')->willReturn(null);
        $item->expects($this->never())->method('setAdditionalData');

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->create->saveFedexShippingAccount($quote, 'acc', 'ref');
    }

    /**
     * Test: Item with empty additional data is skipped
     */
    public function testSaveFedexShippingAccountItemWithEmptyAdditionalData(): void
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->onlyMethods(['save'])
            ->getMock();
        $item->method('getMiraklOfferId')->willReturn('offer1');
        $item->method('getAdditionalData')->willReturn('');
        $item->expects($this->never())->method('setAdditionalData');

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->create->saveFedexShippingAccount($quote, 'acc', 'ref');
    }

    /**
     * Test: Item with additional data missing mirakl_shipping_data is skipped
     */
    public function testSaveFedexShippingAccountItemWithNoMiraklShippingData(): void
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
            ->onlyMethods(['save'])
            ->getMock();
        $item->method('getMiraklOfferId')->willReturn('offer1');
        $item->method('getAdditionalData')->willReturn(json_encode(['foo' => 'bar']));
        $item->expects($this->never())->method('setAdditionalData');

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->create->saveFedexShippingAccount($quote, 'acc', 'ref');
    }

    /**
     * Test: Exception is caught and logged
     */
    public function testSaveFedexShippingAccountExceptionIsLogged(): void
    {
        $fedexShipAccountNumber = '12345';
        $fedexShipReferenceId = 'ref-abc';
        $item = $this->getMockBuilder(Item::class)
                    ->disableOriginalConstructor()
                    ->addMethods(['getMiraklOfferId', 'getAdditionalData', 'setAdditionalData'])
                    ->getMock();
        $item->method('getMiraklOfferId')->willReturn('offer1');
        $item->method('getAdditionalData')->willReturn(json_encode([
            'mirakl_shipping_data' => ['foo' => 'bar']
        ]));
        $item->expects($this->once())->method('setAdditionalData');

        $this->quoteRepository->method('save')->willThrowException(new Exception('Save error'));

        $quote = $this->createMock(Quote::class);
        $quote->method('getItems')->willReturn([$item]);

        $this->marketplaceCheckoutHelper->method('isEssendantToggleEnabled')->willReturn(true);

        $this->logger->expects($this->any())
            ->method('error')
            ->with($this->stringContains('Error saving quote data: Save error'));

        $this->create->saveFedexShippingAccount($quote, $fedexShipAccountNumber, $fedexShipReferenceId);
    }
}
