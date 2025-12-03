<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare (strict_types = 1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model;

use Exception;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\CartGraphQl\Model\Note\Command\SaveInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Model\IntegrationNoteBuilder;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\MarketplaceRates\Helper\Data as MarketplaceRatesHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SubmitOrderSidebar\Helper\Data;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Fedex\SubmitOrderSidebar\Model\SubmitOrder as SubmitOrderModel;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderSidebarConfigProvider;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Region;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\Sales\Model\Order as OrderModel;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;

class SubmitOrderBuilderTest extends TestCase
{
    /**
     * @var (\Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder & MockObject)
     */
    protected $submitMock;
    /**
     * @var (MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $submitOrderModelMock;
    protected $submitOrderModelAPIMock;
    protected $orderMock;
    protected $submitOrderDataArrayMock;
    protected $toggleConfigMock;
    protected $dataObjectFactoryMock;
    protected $selfregHelperMock;
    protected $quoteMock;
    protected $addressMock;
    protected $itemMock;
    protected $companyHelperMock;
    protected $regionMock;
    protected $companyMock;
    /**
     * @var (\Fedex\SubmitOrderSidebar\Test\Unit\Model\Option & MockObject)
     */
    protected $itemOptionMock;
    protected $requestQueryValidatorMock;
    protected $cartIntegrationRepositoryMock;
    /**
     * @var (\Fedex\SubmitOrderSidebar\Helper\Data & MockObject)
     */
    protected $submitOrderHelperMock;
    protected $submitOrderHelper;
    protected $integrationNoteBuilderMock;
    protected $fxoRateQuoteMock;
    protected $commandOrderNoteSaveMock;
    protected $instoreConfigMock;
    protected $quoteHelper;
    protected $orderApprovalViewModel;
    /**
     * @var (\Magento\Sales\Model\Order & MockObject)
     */
    protected $orderModel;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (CustomerSession&MockObject)|MockObject
     */
    protected $customerSession;
    /**
     * @var MarketplaceRatesHelper|(MarketplaceRatesHelper&object&MockObject)|(MarketplaceRatesHelper&MockObject)|(object&MockObject)|MockObject
     */
    private $marketplaceRatesHelperMock;

    /**
     * @var MarketPlaceHelper|(MarketPlaceHelper&object&MockObject)|(MarketPlaceHelper&MockObject)|(object&MockObject)|MockObject
     */
    private $marketPlaceHelperMock;

    /**
     * @var CheckoutHelper|(CheckoutHelper&object&MockObject)|(CheckoutHelper&MockObject)|(object&MockObject)|MockObject
     */
    private $checkoutHelperMock;

    /**
     * @var CartRepositoryInterface|(CartRepositoryInterface&object&MockObject)|(CartRepositoryInterface&MockObject)|(object&MockObject)|MockObject
     */
    private CartRepositoryInterface|MockObject $quoteRepositoryMock;

    /**
     * @var SerializerInterface|(SerializerInterface&object&MockObject)|(SerializerInterface&MockObject)|(object&MockObject)|MockObject
     */
    private $serializerMock;

    /**
     * @var DeliveryDataHelper|(DeliveryDataHelper&object&MockObject)|(DeliveryDataHelper&MockObject)|(object&MockObject)|MockObject
     */
    private $deliveryHelperMock;
    /**
     * @var SubmitOrderSidebarConfigProvider|(SubmitOrderSidebarConfigProvider&object&MockObject)|(SubmitOrderSidebarConfigProvider&MockObject)|(object&MockObject)|MockObject
     */
    private $configProviderMock;
    /**
     * @var FuseBidViewModel|(FuseBidViewModel&object&MockObject)|(FuseBidViewModel&MockObject)|(object&MockObject)|MockObject
     */
    private $fuseBidViewModelMock;

    protected $submitOrderBuilderMock;
    protected $requestData = '{
        "paymentData": "{\"paymentMethod\":\"cc\",\"year\":\"2025\",\"expire\":\"08\",\"nameOnCard\":\"Yogesh\",\"number\":\"*1111\",\"cvv\":\"111\",\"isBillingAddress\":false,\"isFedexAccountApplied\":false,\"fedexAccountNumber\":null,\"creditCardType\":\"https://staging3.office.fedex.com/pub/media/wysiwyg/Visa.png\",\"billingAddress\":{\"countryId\":\"US\",\"regionId\":\"169\",\"region\":\"\",\"street\":[\"234\"],\"company\":\"Infogain\",\"telephone\":\"9514265874\",\"postcode\":\"75024\",\"city\":\"Plano\",\"firstname\":\"Yogesh\",\"lastname\":\"Suryawanshi\",\"customAttributes\":[{\"attribute_code\":\"email_id\",\"value\":\"Yogesh.suryawanshi@infogain.com\"},{\"attribute_code\":\"ext\",\"value\":\"\"}],\"altFirstName\":\"\",\"altLastName\":\"\",\"altPhoneNumber\":\"\",\"altEmail\":\"\",\"altPhoneNumberext\":\"\",\"is_alternate\":false}}",
        "orderNumber": "2010128931541244",
        "useSiteCreditCard": true,
        "isPickup": true,
        "encCCData": "q6y0PKLOS4wYR+UGkIJ50xfK9b9jUDH6TbKAp87KiopHmouOtz9esu7r3ZVQTsK9tJG2LXVGBHJxL7Q/7GyJwF9voVdcsiFNS1Mh2v9ZIWoOlWF5uATKlVheDsPg4cBDJbUFYIzXtilXlcqOnZq6a4ssqAfcaPE6zEQxFYlwRf0fJkZiAvPB/e0bj4orarO24qqjRuZNb0E9zxp/btCLy8dWY2lOfph0XgUFWyNbpgPu8hsK8vD1DL8Odwkmg0eYN5l4qq6EIBn2G/Ce9ZcrLi07qKLiQB/aHgsqG6GdfvRQzPTQCqMvP5fe0MA/0p7x+BQQceBIepCrM/lexAJAKA==",
        "pickupData": "{\"contactInformation\":{\"contact_fname\":\"Attri\",\"contact_lname\":\"Kumar\",\"contact_email\":\"attri.kumar@infogain.com\",\"contact_number\":\"1098647589\",\"alternate_fname\":\"\",\"alternate_lname\":\"\",\"alternate_email\":\"\",\"alternate_number\":\"\",\"isAlternatePerson\":false},\"addressInformation\":{\"pickup_location_name\":\"Dallas TX Valwood\",\"estimate_pickup_time\":\"00:00:00\",\"estimate_pickup_time_for_api\":\"12-12-2022\",\"pickup_location_street\":\"13940 N STEMMONS FWY\",\"pickup_location_city\":\"Dallas\",\"pickup_location_state\":\"TX\",\"pickup_location_zipcode\":\"75234\",\"pickup_location_country\":\"US\",\"pickup_location_date\":\"2021-12-16T16:00:00\",\"pickup\":true,\"shipping_address\":\"\",\"billing_address\":\"\",\"shipping_method_code\":\"PICKUP\",\"shipping_carrier_code\":\"fedexshipping\",\"shipping_detail\":{\"carrier_code\":\"fedexshipping\",\"method_code\":\"PICKUP\",\"carrier_title\":\"Fedex Store Pickup\",\"method_title\":\"0651\",\"amount\":0,\"base_amount\":0,\"available\":true,\"error_message\":\"\",\"price_excl_tax\":0,\"price_incl_tax\":0}},\"orderNumber\":null}",
        "notes": "[\"text\" => \"Test Note\", \"audit\" => [\"user\" => \"Test User\", \"creationTime\" => \"2023-12-01T00:00:00Z\", \"userReference\" => [\"reference\" => \"Testing\", \"source\" => \"MAGENTO\"], \"altContactInfo\" => [\"alternate_email\" => \"pallavi.kade@infogain.com\"]]]"
    }';

    protected $response = '{
        "transactionId":"d00b7d66-e169-4ad2-9d01-12c17ab604a2",
        "errors":[],
        "output":{
            "alerts":[],
            "rateQuote":{
                "currency":"USD",
                "rateQuoteDetails":[{
                    "grossAmount":45.45,
                    "totalDiscountAmount":12.25,
                    "netAmount":33.20,
                    "taxableAmount":33.20,
                    "taxAmount":1.88,
                    "totalAmount":35.08,
                    "estimatedVsActual":"ACTUAL",
                    "productLines":[{
                        "instanceId":"0",
                        "productId":"1463680545590",
                        "unitQuantity":50,
                        "priceable":true,
                        "unitOfMeasurement":"EACH",
                        "productRetailPrice":34.99,
                        "productDiscountAmount":12.25,
                        "productLinePrice":22.74,
                        "productLineDiscounts":[{
                            "type":"AR_CUSTOMERS",
                            "amount":12.25
                        }],
                        "productLineDetails":[{
                            "detailCode":"40005",
                            "priceRequired":false,
                            "priceOverridable":false,
                            "description":"Full Pg Clr Flyr 50",
                            "unitQuantity":1,
                            "quantity":1,
                            "detailPrice":22.74,
                            "detailDiscountPrice":12.25,
                            "detailUnitPrice":34.990000,
                            "detailDiscountedUnitPrice":12.25,
                            "detailDiscounts":[{
                                "type":"AR_CUSTOMERS",
                                "amount":12.2465
                            }],
                            "detailCategory":"PRINTING"
                        }],
                        "name":"Fast Order Flyer",
                        "userProductName":"Fast Order Flyer",
                        "type":"PRINT_ORDER"
                    }],
                    "deliveryLines":[
                        {
                            "recipientReference":"1",
                            "priceable":true,
                            "deliveryLinePrice":0,
                            "deliveryRetailPrice":0,
                            "deliveryLineType":"PACKING_AND_HANDLING",
                            "deliveryDiscountAmount":0
                        },
                        {
                            "recipientReference":"1",
                            "estimatedDeliveryLocalTime":"2021-09-17",
                            "estimatedShipDate":"2022-07-11",
                            "priceable":false,
                            "deliveryLinePrice":10.46,
                            "deliveryRetailPrice":10.46,
                            "deliveryLineType":"SHIPPING",
                            "deliveryDiscountAmount":0.0,
                            "recipientContact":{
                                "personName":{
                                    "firstName":"Nandhu",
                                    "lastName":"V Nair"
                                },
                                "company":{
                                    "name":"FXO"
                                },
                                "emailDetail":{
                                    "emailAddress":"nandhu.nair@igglobal.com"
                                },
                                "phoneNumberDetails":[{
                                    "phoneNumber":{
                                        "number":"8986776897"
                                    },
                                    "usage":"PRIMARY"
                                }]
                            },
                            "shipmentDetails":{
                                "address":{
                                    "streetLines":[
                                        "7900 Legacy Dr",
                                        null
                                    ],
                                    "city":"Plano",
                                    "stateOrProvinceCode":"75024",
                                    "postalCode":"75024",
                                    "countryCode":"US"
                                }
                            }
                        }
                    ],
                    "discounts":[{
                        "type":"AR_CUSTOMERS",
                        "amount":12.25
                    }],
                    "rateQuoteId":"eyJxdW90ZUl"
                }]
            }
        }
    }';

    public const ESTIMATE_PICKUP_TIME = "12-12-2022";
    public const EMAIL = "ayush.sood@infogain.com";
    public const ADDRESS = "Legacy Dr";
    public const PINCODE = "75024";
    public const PHONE_NUMBER = "9876543212";

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->submitMock = $this->getMockBuilder(SubmitOrderBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionExist'])
            ->getMock();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->submitOrderModelMock = $this->createMock(SubmitOrderModel::class);
        $this->submitOrderModelAPIMock = $this->getMockBuilder(SubmitOrderModelAPI::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'callRateQuoteApi',
                'isAlternateContact',
                'isAlternatePickupPerson',
                'deleteOrderWithPendingStatus',
                'createOrderBeforePayment',
                'updateOrderAfterPayment',
                'callFujitsuRateQuoteApi',
                'getRateQuoteId',
                'setTimeoutFlag',
                'getTransactionAPIResponse',
                'callRateQuoteApiWithSave',
                'manageAlternateFlags',
                'unsetOrderInProgress',
                'updateQuoteStatusAndTimeoutFlag',
                'getProductionLocationId'
            ])
            ->getMockForAbstractClass();
        $this->orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->submitOrderDataArrayMock = $this->createMock(SubmitOrderDataArray::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->dataObjectFactoryMock = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setQuoteId',
                    'create',
                    'setPickStore',
                    'setFedExAccountNumber',
                    'setLteIdentifier',
                    'setOrderNumber',
                    'setCompanySite',
                    'setUserReferences',
                    'setFname',
                    'setLname',
                    'setEmail',
                    'setTelephone',
                    'setExtension',
                    'setRecipientFname',
                    'setRecipientLname',
                    'setRecipientEmail',
                    'setRecipientTelephone',
                    'setRecipientExtension',
                    'setWebhookUrl',
                    'setProductData',
                    'setShipmentId',
                    'setProductAssociations',
                    'setPromoCodeArray',
                    'setPoReferenceId',
                    'setStreetAddress',
                    'setCity',
                    'setShipperRegion',
                    'setZipCode',
                    'setAddressClassification',
                    'setShipMethod',
                    'setFedexShipAccountNumber',
                    'setLocationId',
                    'setRequestedPickupLocalTime',
                    'setQuoteData',
                    'setPaymentData',
                    'setEncCCData',
                    'setIsPickup',
                    'setEstimatePickupTime',
                    'setUseSiteCreditCard',
                    'setOrderData',
                    'setRateQuoteId',
                    'setCompany',
                    'setRateQuoteResponse',
                    'setOrderClient',
                    'setSourceRetailLocationId',
                    'setNotes',
                    'getQuoteData',
                    'getOrderNumber',
                    'getIsPickup',
                    'getCompanyLevelConfig',
                    'setContactId',
                    'setSiteName',
                    'setIsB2bApproval'
                ]
            )
            ->getMockForAbstractClass();
        $this->selfregHelperMock = $this->createMock(SelfReg::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getShippingAddress',
                    'setData',
                    'getData',
                    'save',
                    'getId',
                    'getIsBid',
                    'setBillingAddress',
                    'getAllItems',
                    'getPayment',
                    'getBillingAddress',
                    'getGtn',
                    'getIsAlternate',
                    'getIsAlternatePickup',
                    'getExtensionAttributes',
                    'setBillingFields',
                    'setShippingAddress'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData'])
            ->onlyMethods(['setEmail','getEmail'])
            ->getMockForAbstractClass();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getOptionByCode', 'getQty', 'getName', 'getProductId', 'getId', 'getPrice', 'getDiscount'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyHelperMock = $this->createMock(CompanyHelper::class);
        $this->regionMock = $this->createMock(Region::class);
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getCompanyName', 'getSiteName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemOptionMock = $this->getMockBuilder(Option::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestQueryValidatorMock = $this->getMockBuilder(RequestQueryValidator::class)
            ->setMethods(['isGraphQl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartIntegrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->onlyMethods(['getByQuoteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->submitOrderHelperMock = $this->createMock(Data::class);
        $this->submitOrderHelper = $this->getMockBuilder(submitOrderHelper::class)
            ->setMethods(['clearQuoteCheckoutSessionAndStorage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->integrationNoteBuilderMock = $this->createMock(IntegrationNoteBuilder::class);
        $this->fxoRateQuoteMock = $this->createMock(FXORateQuote::class);
        $this->commandOrderNoteSaveMock = $this->createMock(SaveInterface::class);
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->quoteHelper = $this->createMock(QuoteHelper::class);
        $this->orderApprovalViewModel = $this->getMockBuilder(OrderApprovalViewModel::class)
            ->setMethods(['isOrderApprovalB2bEnabled', 'getOrderPendingApproval','b2bOrderSendEmail','saveEstimatedPickupTime'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderModel = $this->getMockBuilder(OrderModel::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getQuoteId',
            ])->getMock();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->addMethods([
                'setLastQuoteId',
                'setLastSuccessQuoteId',
                'setLastOrderId',
                'setLastRealOrderId',
                'setLastOrderStatus',
            ])->getMock();
        $this->marketPlaceHelperMock = $this->createMock(MarketPlaceHelper::class);
        $this->checkoutHelperMock = $this->createMock(CheckoutHelper::class);
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->configProviderMock = $this->createMock(SubmitOrderSidebarConfigProvider::class);
        $this->deliveryHelperMock = $this->createMock(DeliveryDataHelper::class);
        $this->fuseBidViewModelMock = $this->createMock(FuseBidViewModel::class);
        $this->marketplaceRatesHelperMock = $this->createMock(MarketplaceRatesHelper::class);

        $this->objectManager = new ObjectManager($this);
        $this->submitOrderBuilderMock = $this->objectManager->getObject(
            SubmitOrderBuilder::class,
            [
                'logger' => $this->loggerMock,
                'submitOrderModel' => $this->submitOrderModelMock,
                'submitOrderModelAPI' => $this->submitOrderModelAPIMock,
                'submitOrderDataArray' => $this->submitOrderDataArrayMock,
                'toggleConfig' => $this->toggleConfigMock,
                'dataObjectFactory' => $this->dataObjectFactoryMock,
                'selfregHelper' => $this->selfregHelperMock,
                'companyHelper' => $this->companyHelperMock,
                'requestQueryValidator' => $this->requestQueryValidatorMock,
                'cartIntegrationRepository' => $this->cartIntegrationRepositoryMock,
                'integrationNoteBuilder' => $this->integrationNoteBuilderMock,
                'submitOrderHelper' => $this->submitOrderHelperMock,
                'marketPlaceHelper' => $this->marketPlaceHelperMock,
                'checkoutHelper' => $this->checkoutHelperMock,
                'fxoRateQuote' => $this->fxoRateQuoteMock,
                'commandOrderNoteSave' => $this->commandOrderNoteSaveMock,
                'instoreConfig' => $this->instoreConfigMock,
                'quoteHelper' => $this->quoteHelper,
                'quoteRepository' => $this->quoteRepositoryMock,
                'serializer' => $this->serializerMock,
                'configProvider' => $this->configProviderMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'fuseBidViewModel' => $this->fuseBidViewModelMock,
                'orderApprovalViewModel' => $this->orderApprovalViewModel,
                'marketplaceRatesHelper' => $this->marketplaceRatesHelperMock,
                'customerSession' => $this->customerSession,
            ]
        );
    }

    /**
     * Test case for getGTNNumber
     */
    public function testGetGTNNumber()
    {
        $this->quoteMock->expects($this->any())->method('getData')->willReturn('1234567');
        $this->assertNotNull($this->submitOrderBuilderMock->getGTNNumber($this->quoteMock));
    }

    /**
     * Test case for getCustomerPickupInfo
     */
    public function testGetCustomerPickupInfo()
    {
        $pickupData = [
            'addressInformation' => [
                'estimate_pickup_time' => self::ESTIMATE_PICKUP_TIME,
            ],
            'contactInformation' => [
                'contact_email' => self::EMAIL
            ],
        ];
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getData')
            ->withConsecutive(
                [],
                ['customer_firstname'],
                ['customer_lastname'],
                ['customer_telephone']

            )->willReturnOnConsecutiveCalls(
                [],
                'Ayush',
                'Sood',
                self::PHONE_NUMBER
            );
        $this->submitOrderModelMock->expects($this->any())->method('isFclCustomer')->willReturn(true);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->getCustomerPickupInfo(
                $this->quoteMock,
                json_decode(json_encode($pickupData))
            )
        );
    }

    /**
     * Test case for getCustomerPickupInfo Without FCL
     */
    public function testGetCustomerPickupInfoWithoutFCL()
    {
        $pickupData = [
            'addressInformation' => [
                'estimate_pickup_time' => self::ESTIMATE_PICKUP_TIME,
            ],
            'contactInformation' => [
                'contact_number' => self::PHONE_NUMBER
            ],
        ];
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getData')
            ->withConsecutive(
                [],
                ['customer_firstname'],
                ['customer_lastname']
            )->willReturnOnConsecutiveCalls(
                [],
                'Ayush',
                'Sood'
            );
        $this->submitOrderModelMock->expects($this->any())->method('isFclCustomer')->willReturn(false);
        $this->assertNotNull($this->submitOrderBuilderMock
            ->getCustomerPickupInfo($this->quoteMock, json_decode(json_encode($pickupData))));
    }

    /**
     * Test case for getCustomerPickupAndShippingAddress
     */
    public function testGetCustomerPickupAndShippingAddress()
    {
        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method']
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );
        $this->assertNotNull($this->submitOrderBuilderMock->getCustomerPickupAndShippingAddress(
            true,
            false,
            json_decode($this->requestData),
            $this->quoteMock,
            $this->addressMock,
            json_decode(json_encode($paymentData))
        ));
    }

    /**
     * Test case for getCustomerPickupAndShippingAddress
     */
    public function testGetCustomerPickupAndShippingAddressWithArrayData()
    {
        $this->submitOrderModelMock->expects($this->any())->method('getRegionByRegionCode')
            ->willReturn($this->regionMock);

        $expectedOutput = [
            'isPickup' => false,
            'addressClassification' => 'BUSINESS',
            'streetAddress' => ['0' => 'Legacy Dr'],
            'city' => 'Plano',
            'regionCode' => 'PX',
            'shipperRegion' => $this->regionMock,
            'zipcode' => self::PINCODE,
            'shipMethod' => 'FirstGround',
            'poReferenceId' => '123',
            'fedExAccountNumber' => '21345456',
            'fedexShipAccountNumber' => null,
            'estimatePickupTime' => '',
            'locationId' => '',
            'requestedPickupLocalTime' => '',
            'fName' => null,
            'lName' => null,
            'email' => null,
            'telephone' => null,
            'company' => null
        ];

        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method']
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );

        $this->assertEquals(
            $expectedOutput,
            $this->submitOrderBuilderMock->getCustomerPickupAndShippingAddress(
                true,
                false,
                json_decode($this->requestData),
                $this->quoteMock,
                $this->addressMock,
                json_decode(json_encode($paymentData))
            )
        );
    }

    /**
     * Test case for getCustomerShippingInfo
     */
    public function testGetCustomerShippingInfo()
    {
        $this->quoteMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['customer_firstname'],
                ['customer_lastname'],
                ['customer_email'],
                ['customer_telephone']
            )->willReturnOnConsecutiveCalls(
                'Ayush',
                'Sood',
                self::EMAIL,
                self::PHONE_NUMBER
            );

        $this->assertNotNull($this->submitOrderBuilderMock->getCustomerShippingInfo(
            true,
            $this->quoteMock,
            $this->addressMock
        ));
    }

    /**
     * Test case for getCustomerShippingInfo Without Alternate
     */
    public function testGetCustomerShippingInfoWithoutAlternate()
    {
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['firstname'],
                ['lastname'],
                ['email'],
                ['telephone']
            )->willReturnOnConsecutiveCalls(
                'Ayush',
                'Sood',
                self::EMAIL,
                self::PHONE_NUMBER
            );

        $this->assertNotNull($this->submitOrderBuilderMock->getCustomerShippingInfo(
            false,
            $this->quoteMock,
            $this->addressMock
        ));
    }

    /**
     * Test case for getRecipientInformation
     */
    public function testGetRecipientInformation()
    {
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['firstname'],
                ['lastname'],
                ['email'],
                ['telephone'],
                ['ext_no']
            )->willReturnOnConsecutiveCalls(
                'Ayush',
                'Sood',
                self::EMAIL,
                self::PHONE_NUMBER,
                '+91'
            );
        $this->assertNotNull($this->submitOrderBuilderMock->getRecipientInformation(
            true,
            false,
            $this->addressMock
        ));
    }

    /**
     * Test case for prepareDataObject
     */
    public function testPrepareDataObject()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
            'company' => 'FXO'
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(
                ['coupon_code'],
                ['ext_no']
            )
            ->willReturnOnConsecutiveCalls(
                'UAT001',
                '+91'
            );
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompany')->willReturnSelf();
        $this->companyHelperMock->expects($this->any())->method('getCompanyLevelConfig')
            ->willReturn(['order_notes' => 'Sample order note']);
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->assertNotNull($this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            true,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        ));
    }

    /**
     * Test case for prepareDataObject
     */
    public function testPrepareDataObjectWithNonPickup()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(
                ['coupon_code'],
                ['ext_no']
            )
            ->willReturnOnConsecutiveCalls(
                'UAT001',
                '+91'
            );
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->companyHelperMock->expects($this->any())->method('getCompanyLevelConfig')
            ->willReturn(['order_notes' => 'Sample order note']);
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->assertNotNull($this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            false,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        ));
    }

    /**
     * Test case for prepareDataObject
     */
    public function testPrepareDataObjectWithGraphQl()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->requestQueryValidatorMock->expects($this->once())->method('isGraphQl')->willReturn(true);
        $this->integrationNoteBuilderMock->expects($this->once())->method('build')->willReturn(['note 1']);
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(
                ['coupon_code'],
                ['ext_no']
            )
            ->willReturnOnConsecutiveCalls(
                'UAT001',
                '+91'
            );
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(false);
        $cartIntegrationInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->onlyMethods(['getPickupStoreId', 'getPickupLocationId', 'getRetailCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->cartIntegrationRepositoryMock->expects($this->any())->method('getByQuoteId')
            ->willReturn($cartIntegrationInterface);

        $this->assertNotNull($this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            true,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        ));
    }

    /**
     * Test case for prepareDataObject
     */
    public function testPrepareDataObjectWithGraphQlAndIntegrationException()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->requestQueryValidatorMock->expects($this->once())->method('isGraphQl')->willReturn(true);
        $this->integrationNoteBuilderMock->expects($this->never())->method('build');
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(
                ['coupon_code'],
                ['ext_no']
            )
            ->willReturnOnConsecutiveCalls(
                'UAT001',
                '+91'
            );
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(false);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);

        $exception = new NoSuchEntityException(
            __('No such entity found with quote_id = %1', 123)
        );
        $this->cartIntegrationRepositoryMock->expects($this->any())->method('getByQuoteId')
            ->willThrowException($exception);

        $this->assertNotNull($this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            true,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        ));
    }

    public function testPrepareDataObjectThrowsNoSuchEntityException()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->requestQueryValidatorMock->expects($this->once())->method('isGraphQl')->willReturn(false);
        $this->integrationNoteBuilderMock->expects($this->never())->method('build');
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(
                ['coupon_code'],
                ['ext_no']
            )
            ->willReturnOnConsecutiveCalls(
                'UAT001',
                '+91'
            );
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();

        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getIsBid')->willReturn(true);

        $this->fuseBidViewModelMock->expects($this->any())
            ->method('isSendRetailLocationIdEnabled')
            ->willReturn(true);

        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->cartIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')
            ->willThrowException($exception);

        $this->quoteHelper->expects($this->once())
            ->method('isFullMiraklQuote')
            ->willReturn(false);

        $result = $this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            true,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        );

        $this->assertNotNull($result);
    }

    /**
     * Test case for prepareDataObject
     */
    public function testPrepareDataObjectWithException()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->requestQueryValidatorMock->expects($this->once())->method('isGraphQl')->willReturn(true);
        $this->quoteMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(
                ['coupon_code'],
                ['ext_no']
            )
            ->willReturnOnConsecutiveCalls(
                'UAT001',
                '+91'
            );
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(false);

        $exception = new \Magento\Framework\Exception\NoSuchEntityException();
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->cartIntegrationRepositoryMock->expects($this->any())->method('getByQuoteId')
            ->willThrowException($exception);

        $this->assertNotNull($this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            true,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        ));
    }

    /**
     * Test case for prepareDataObject
     */
    public function testPrepareDataObjectWithFalseToggle()
    {
        $customerOrderInfo = [
            'fedExAccountNumber' => '6787654343',
            'fName' => 'Ayush',
            'lName' => 'Sood',
            'email' => self::EMAIL,
            'telephone' => self::PHONE_NUMBER,
            'poReferenceId' => '123',
            'streetAddress' => 'XYZ',
            'city' => 'Plano',
            'shipperRegion' => 'TX',
            'zipcode' => self::PINCODE,
            'addressClassification' => "YES",
            'shipMethod' => 'Test',
            'fedexShipAccountNumber' => '678765432',
            'locationId' => self::PINCODE,
            'requestedPickupLocalTime' => '12-2-12',
        ];
        $recipientInfo = [
            'recipientFname' => 'Ayush',
            'recipientLname' => 'Sood',
            'recipientEmail' => self::EMAIL,
            'recipientTelephone' => '70877753785',
            'recipientExt' => '+91',
        ];
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(['coupon_code'], ['ext_no'])
            ->willReturnOnConsecutiveCalls('UAT001', '+91');
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->assertNotNull($this->submitOrderBuilderMock->prepareDataObject(
            $this->quoteMock,
            false,
            '12',
            123,
            $customerOrderInfo,
            $recipientInfo
        ));
    }

    /**
     * Test case for build
     */
    public function testBuild()
    {
        $responseData = [
            "error" => 0,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                    "alerts" => [
                        [
                            "code" => "QCXS.SERVICE.ORDERNUMBER",
                            "message" => "Order is already exists with orderNumber",
                            "alertType" => "WARNING",
                        ],
                    ],
                    "trasactionDetails" => [
                        "alerts" => [],
                        "orderReferenceSearch" => [
                            "orderReferences" => [
                                [
                                    "txnDetails" => [
                                        "retailTransactionId" => "ADSKDF9C6435480505X",
                                        "apiClientId" => "l7e4acbdd6b7d341b0b59234bbdbd4e82e",
                                    ],
                                    "orderDetails" => [
                                        [
                                            "userReference" => [],
                                            "reference" => [
                                                "name" => "MAGENTO",
                                                "value" => "2010512174808764",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->commandOrderNoteSaveMock->expects($this->any())
            ->method('execute');

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')
            ->willReturn(['response' => ['output' => ['alerts' => null]]]);
        $this->submitOrderModelAPIMock->expects($this->any())->method('createOrderBeforePayment')
            ->willReturn($this->orderMock);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['shipping_description'],
            )
            ->willReturnOnConsecutiveCalls(
                self::PINCODE,
            );

        $this->testPrepareDataObject();
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())
            ->method('callRateQuoteApiWithSave')
            ->willReturn($responseData);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->build(
                json_decode((string) $this->requestData),
                true
            )
        );
    }

    /**
     * Test case for instore build retry transaction
     */
    public function testInstoreBuildRetryTransaction()
    {
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $requestData = (object) ['paymentData' => '{"some-data"}'];
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $result = $this->submitOrderBuilderMock->instoreBuildRetryTransaction(
            $this->orderMock,
            $this->quoteMock,
            $requestData
        );

        $this->assertNotNull($result);
    }


    /**
     * Test case for instore build retry transaction
     */
    public function testInstoreBuildRetryTransactionException()
    {
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $requestData = (object) ['paymentData' => '{"some-data"}'];
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->once())->method('updateOrderAfterPayment')
            ->willThrowException(new GraphQlFujitsuResponseException(__('exception message')));
        $this->expectExceptionMessage('exception message');

        $result = $this->submitOrderBuilderMock->instoreBuildRetryTransaction(
            $this->orderMock,
            $this->quoteMock,
            $requestData
        );

        $this->assertNotNull($result);
    }

    /**
     * Test case for build
     */
    public function testBuildAlternateContact()
    {
        $responseData = [
            "error" => 0,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                    "alerts" => [
                        [
                            "code" => "QCXS.SERVICE.ORDERNUMBER",
                            "message" => "Order is already exists with orderNumber",
                            "alertType" => "WARNING",
                        ],
                    ],
                    "trasactionDetails" => [
                        "alerts" => [],
                        "orderReferenceSearch" => [
                            "orderReferences" => [
                                [
                                    "txnDetails" => [
                                        "retailTransactionId" => "ADSKDF9C6435480505X",
                                        "apiClientId" => "l7e4acbdd6b7d341b0b59234bbdbd4e82e",
                                    ],
                                    "orderDetails" => [
                                        [
                                            "userReference" => [],
                                            "reference" => [
                                                "name" => "MAGENTO",
                                                "value" => "2010512174808764",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getIsAlternate')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getIsAlternatePickup')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->commandOrderNoteSaveMock->expects($this->any())
            ->method('execute');

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')
            ->willReturn(['response' => ['output' => ['alerts' => null]]]);
        $this->submitOrderModelAPIMock->expects($this->any())->method('createOrderBeforePayment')
            ->willReturn($this->orderMock);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['firstname'],
                ['lastname'],
                ['email'],
                ['company'],
                ['telephone'],
                ['ext_no'],
                ['ext_no'],
                ['shipping_description'],
            )
            ->willReturnOnConsecutiveCalls(
                'firstname',
                'lastname',
                'email@email.com',
                'company',
                'telephone',
                'ext_no',
                'ext_no',
                self::PINCODE,
            );

        $this->testPrepareDataObject();
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())
            ->method('callRateQuoteApiWithSave')
            ->willReturn($responseData);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->build(
                json_decode((string) $this->requestData),
                true
            )
        );
    }

    /**
     * Test case for build
     */
    public function testBuildFunction()
    {
        $responseData = [
            "error" => 0,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                    "alerts" => [
                        [
                            "code" => "QCXS.SERVICE.ORDERNUMBER",
                            "message" => "Order is already exists with orderNumber",
                            "alertType" => "WARNING",
                        ],
                    ],
                    "trasactionDetails" => [
                        "alerts" => [],
                        "orderReferenceSearch" => [
                            "orderReferences" => [
                                [
                                    "txnDetails" => [
                                        "retailTransactionId" => "ADSKDF9C6435480505X",
                                        "apiClientId" => "l7e4acbdd6b7d341b0b59234bbdbd4e82e",
                                    ],
                                    "orderDetails" => [
                                        [
                                            "userReference" => [],
                                            "reference" => [
                                                "name" => "MAGENTO",
                                                "value" => "2010512174808764",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->expects($this->any())->method('setShippingAddress')->with($this->addressMock);

        $this->quoteMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->commandOrderNoteSaveMock->expects($this->any())
            ->method('execute');

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')
            ->willReturn(['response' => ['output' => ['alerts' => null]]]);
        $this->submitOrderModelAPIMock->expects($this->any())->method('createOrderBeforePayment')
            ->willReturn($this->orderMock);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method'],
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method']
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround',
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );

        $this->testPrepareDataObjectWithNonPickup();
        $this->testGetCustomerPickupAndShippingAddress();
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())
            ->method('callRateQuoteApiWithSave')
            ->willReturn($responseData);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->build(
                json_decode((string) $this->requestData),
                false
            )
        );
    }

    /**
     * Test case For build with RateQuote
     */
    public function testBuildWithSaveRateQuote()
    {
        $data = [
            'rateQuoteRequest' => [
                'retailPrintOrder' => [
                    'recipients' => [
                        0 => [
                            'shipmentDelivery' => [
                                'specialServices' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $responseData = [
            "error" => 0,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                    "alerts" => [
                        [
                            "code" => "QCXS.SERVICE.ORDERNUMBER",
                            "message" => "Order is already exists with orderNumber",
                            "alertType" => "WARNING",
                        ],
                    ],
                    "trasactionDetails" => [
                        "alerts" => [],
                        "orderReferenceSearch" => [
                            "orderReferences" => [
                                [
                                    "txnDetails" => [
                                        "retailTransactionId" => "ADSKDF9C6435480505X",
                                        "apiClientId" => "l7e4acbdd6b7d341b0b59234bbdbd4e82e",
                                    ],
                                    "orderDetails" => [
                                        [
                                            "userReference" => [],
                                            "reference" => [
                                                "name" => "MAGENTO",
                                                "value" => "2010512174808764",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')
            ->willReturn(['response' => ['output' => ['alerts' => null]]]);
        $this->submitOrderModelAPIMock->expects($this->any())->method('createOrderBeforePayment')
            ->willReturn($this->orderMock);
        $this->submitOrderDataArrayMock->expects($this->any())->method('getOrderDetails')->willReturn($data);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn(['test' => 'test']);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['shipping_description'],
            )->willReturnOnConsecutiveCalls(
                self::PINCODE,
                'fedex_FirstGround'
            );
        $this->testPrepareDataObject();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->submitOrderBuilderMock->build(
            json_decode((string) $this->requestData),
            true
        );
    }

    /**
     * Test case for build With PickupFalse
     */
    public function testBuildWithFalsePickup()
    {
        $data = [
            'rateQuoteRequest' => [
                'retailPrintOrder' => [
                    'recipients' => [
                        0 => [
                            'shipmentDelivery' => [
                                'specialServices' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $responseData = [
            "error" => 0,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                    "alerts" => [
                        [
                            "code" => "QCXS.SERVICE.ORDERNUMBER",
                            "message" => "Order is already exists with orderNumber",
                            "alertType" => "WARNING",
                        ],
                    ],
                    "trasactionDetails" => [
                        "alerts" => [],
                        "orderReferenceSearch" => [
                            "orderReferences" => [
                                [
                                    "txnDetails" => [
                                        "retailTransactionId" => "ADSKDF9C6435480505X",
                                        "apiClientId" => "l7e4acbdd6b7d341b0b59234bbdbd4e82e",
                                    ],
                                    "orderDetails" => [
                                        [
                                            "userReference" => [],
                                            "reference" => [
                                                "name" => "MAGENTO",
                                                "value" => "2010512174808764",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')
            ->willReturn(['response' => ['output' => ['alerts' => null]]]);
        $this->submitOrderModelAPIMock->expects($this->any())->method('createOrderBeforePayment')
            ->willReturn($this->orderMock);
        $this->submitOrderDataArrayMock->expects($this->any())->method('getOrderDetails')->willReturn($data);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn(['test' => 'test']);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method'],
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );
        $this->testPrepareDataObject();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->submitOrderBuilderMock->build(
            json_decode((string) $this->requestData),
            false
        );
    }
    /**
     * Test case for build With False and Toggle false
     */
    public function testBuildWithFalseToggle()
    {
        $data = [
            'rateQuoteRequest' => [
                'retailPrintOrder' => [
                    'recipients' => [
                        0 => [
                            'shipmentDelivery' => [
                                'specialServices' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderDataArrayMock->expects($this->any())->method('getOrderDetails')->willReturn($data);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn(['test' => 'test']);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method'],
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );

        $this->testPrepareDataObjectWithFalseToggle();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->submitOrderBuilderMock->build(
            json_decode((string) $this->requestData),
            false
        );
    }

    /**
     * Test case for build With PickupFalse and Toggle false
     */
    public function testBuildWithFalsePickupFalseToggle()
    {
        $data = [
            'rateQuoteRequest' => [
                'retailPrintOrder' => [
                    'recipients' => [
                        0 => [
                            'shipmentDelivery' => [
                                'specialServices' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->testGetGTNNumber();
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternateContact')->willReturn(false);
        $this->submitOrderModelAPIMock->expects($this->any())->method('isAlternatePickupPerson')->willReturn(true);
        $this->submitOrderDataArrayMock->expects($this->any())->method('getOrderDetails')->willReturn($data);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn(['test' => 'test']);
        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method'],
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );
        $this->testPrepareDataObjectWithFalseToggle();
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->submitOrderBuilderMock->build(
            json_decode((string) $this->requestData),
            false
        );
    }

    /**
     * Test case for build With PickupFalse and Toggle false With Exception
     */
    public function testBuildWithFalsePickupFalseToggleException()
    {
        $exception = new Exception();
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(12);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method'],
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('12345678');
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())
            ->method('getData')->withConsecutive(['gtn'])
            ->willReturnOnConsecutiveCalls('12345678');
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPickStore')->willReturn(0);
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedExAccountNumber')->willReturn("");
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderNumber')->willReturn('24823482348');
        $this->dataObjectFactoryMock->expects($this->any())->method('setCompanySite')->willReturn(false);
        $this->dataObjectFactoryMock->expects($this->any())->method('setUserReferences')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setFname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLname')->willReturn('test');
        $this->dataObjectFactoryMock->expects($this->any())->method('setEmail')->willReturn('test.email.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setTelephone')->willReturn("956965656565");
        $this->dataObjectFactoryMock->expects($this->any())->method('setExtension')->willReturn("111");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientFname')->willReturn('Attri');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientLname')->willReturn('Kumar');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientEmail')
            ->willReturn('attri@yopmail.com');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientTelephone')
            ->willReturn("956965656562");
        $this->dataObjectFactoryMock->expects($this->any())->method('setRecipientExtension')->willReturn("222");
        $this->dataObjectFactoryMock->expects($this->any())->method('setWebhookUrl')->willReturn("https://fedex.com");
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductData')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipmentId')->willReturn(987654);
        $this->dataObjectFactoryMock->expects($this->any())->method('setProductAssociations')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPromoCodeArray')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setStreetAddress')->willReturn([]);
        $this->dataObjectFactoryMock->expects($this->any())->method('setCity')->willReturn("Plano");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipperRegion')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setZipCode')->willReturn(self::PINCODE);
        $this->dataObjectFactoryMock->expects($this->any())->method('setAddressClassification')->willReturn("Home");
        $this->dataObjectFactoryMock->expects($this->any())->method('setShipMethod')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setFedexShipAccountNumber')->willReturn('12345');
        $this->dataObjectFactoryMock->expects($this->any())->method('setLocationId')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setRequestedPickupLocalTime')->willReturn('');
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setPaymentData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())
            ->method('setEncCCData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setIsPickup')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setEstimatePickupTime')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setUseSiteCreditCard')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setOrderData')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setQuoteId')->willReturnSelf();
        $this->dataObjectFactoryMock->expects($this->any())->method('setRateQuoteId')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('callFujitsuRateQuoteApi')
            ->willThrowException($exception);
        $this->quoteHelper->expects($this->once())->method('isFullMiraklQuote')->willReturn(false);
        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));
        $this->assertNotNull(
            $this->submitOrderBuilderMock->build(
                json_decode((string) $this->requestData),
                false
            )
        );
    }

    public function testBuildWithGraphQlFujitsuResponseException(): void
    {
        $exception = new GraphQlFujitsuResponseException(__("Some Message"));
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(12);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->addressMock->expects($this->any())->method('getData')
            ->withConsecutive(
                ['company'],
                ['street'],
                ['city'],
                ['region_id'],
                ['postcode'],
                ['shipping_method'],
            )->willReturnOnConsecutiveCalls(
                'Infogain',
                [0 => self::ADDRESS],
                'Plano',
                'PX',
                self::PINCODE,
                'fedex_FirstGround'
            );
        $this->submitOrderModelMock->expects($this->any())->method('getGTNNumber')->willReturn('12345678');
        $this->selfregHelperMock->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->companyHelperMock->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getSiteName')->willReturn('l6site51');
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->submitOrderModelMock->expects($this->any())->method('getWebHookUrl')->willReturn('123456');
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([$this->itemMock]);
        $this->quoteMock->expects($this->any())->method('getData')->withConsecutive(['gtn'])
            ->willReturnOnConsecutiveCalls('12345678');
        $this->quoteMock->expects($this->any())->method('getExtensionAttributes')->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getProductAndProductAssociations')
            ->with([$this->itemMock])
            ->willReturn([]);
        $this->submitOrderModelMock->expects($this->any())
            ->method('getUuid')
            ->willReturn('test');

        $this->instoreConfigMock->expects(static::any())
            ->method("isEnabledThrowExceptionOnGraphqlRequests")
            ->willReturn(true);

        $this->fxoRateQuoteMock->expects($this->any())
            ->method('getFXORateQuote')
            ->willReturn(json_decode($this->response, true));

        $this->commandOrderNoteSaveMock->expects($this->once())
            ->method('execute')->willThrowException($exception);

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->submitOrderBuilderMock->build(json_decode((string) $this->requestData), false);
    }

    /**
     * Test case for build With Empty Order Number
     */
    public function testBuildWithEmptyOrderNumber()
    {
        $this->submitOrderModelMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getShippingAddress')->willReturn($this->addressMock);

        $this->assertEquals(
            ['error' => 1],
            $this->submitOrderBuilderMock->build(json_decode((string) $this->requestData), false)
        );
    }

    /**
     * Test case for get requested pickup local time
     */
    public function testGetRequestedPickupLocalTime()
    {
        $requestedPickupLocalTime = self::ESTIMATE_PICKUP_TIME;
        $pickupData = [
            'addressInformation' => [
                'estimate_pickup_time_for_api' => self::ESTIMATE_PICKUP_TIME,
            ],
        ];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(
            $requestedPickupLocalTime,
            $this->submitOrderBuilderMock->getRequestedPickupLocalTime(
                $requestedPickupLocalTime,
                json_decode(json_encode($pickupData))
            )
        );
    }

    /**
     * Test case for callRateQuoteApiWithCommitAction
     */
    public function testcallRateQuoteApiWithCommitActionWithError()
    {
        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $logHeader = 'File: ';
        $errorResponse = [
            'error' => 'RAQ',
            'response' => [
                'errors' => [
                    0 => [
                        'code' => 'RAQ.SERVICE.119',
                    ],
                ],
            ],
        ];
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($errorResponse);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($errorResponse);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->callRateQuoteApiWithCommitAction(
                $logHeader,
                $this->dataObjectFactoryMock,
                $paymentData
            )
        );
    }

    /**
     * Test case for callRateQuoteApiWithCommitAction
     */
    public function testcallRateQuoteApi()
    {
        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $logHeader = 'File: ';

        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())
            ->method('callRateQuoteApi')
            ->willReturn(json_decode($this->response, true));
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn(json_decode($this->response, true));
        $this->assertNotNull(
            $this->submitOrderBuilderMock->callRateQuoteApiWithCommitAction(
                $logHeader,
                $this->dataObjectFactoryMock,
                $paymentData
            )
        );
    }

    /**
     * Test case for callRateQuoteApiWithCommitAction
     */
    public function testcallRateQuoteApiWithCommitActionWithWarning()
    {
        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $logHeader = 'File: ';

        $responseData = [
            "error" => 0,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                    "alerts" => [
                        [
                            "code" => "QCXS.SERVICE.ORDERNUMBER",
                            "message" => "Order is already exists with orderNumber",
                            "alertType" => "WARNING",
                        ],
                    ],
                    "trasactionDetails" => [
                        "alerts" => [],
                        "orderReferenceSearch" => [
                            "orderReferences" => [
                                [
                                    "txnDetails" => [
                                        "retailTransactionId" => "ADSKDF9C6435480505X",
                                        "apiClientId" => "l7e4acbdd6b7d341b0b59234bbdbd4e82e",
                                    ],
                                    "orderDetails" => [
                                        [
                                            "userReference" => [],
                                            "reference" => [
                                                "name" => "MAGENTO",
                                                "value" => "2010512174808764",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->callRateQuoteApiWithCommitAction(
                $logHeader,
                $this->dataObjectFactoryMock,
                $paymentData
            )
        );
    }

    /**
     * Test case for callRateQuoteApiWithCommitAction
     */
    public function testcallRateQuoteApiWithCommitActionWithElse()
    {
        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $logHeader = 'File: ';

        $responseData = [
            "error" => 1,
            "response" => [
                "transactionId" => "f7672bc3-6d26-4672-8e41-a0e984fb147b",
                "errors" => [],
                "output" => [
                ],
            ],
        ];
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->callRateQuoteApiWithCommitAction(
                $logHeader,
                $this->dataObjectFactoryMock,
                $paymentData
            )
        );
    }

    /**
     * Test case for callRateQuoteApiWithCommitAction
     * @param object DataObjectForFujistu
     */
    public function testcallRateQuoteApiWithCommitActionWithSuccessResponse()
    {
        $paymentData = [
            'poReferenceId' => '123',
            'fedexAccountNumber' => '21345456',
        ];
        $logHeader = 'File: ';

        $responseData = '{"response":{
            "transactionId": "32332f91-bad6-4660-850f-fe1b2962beca",
            "errors": [],
            "output": {
              "alerts": [],
              "rateQuote": {
                "currency": "USD",
                "rateQuoteDetails": [
                  {
                    "grossAmount": 6118.77,
                    "totalDiscountAmount": 3509.3,
                    "netAmount": 2609.47,
                    "taxableAmount": 2609.47,
                    "taxAmount": 215.28,
                    "totalAmount": 2824.75,
                    "productsTotalAmount": 6098.78,
                    "deliveriesTotalAmount": 19.99,
                    "estimatedVsActual": "ACTUAL",
                    "productLines": [
                      {
                        "instanceId": "147188",
                        "productId": "1456773326927",
                        "unitQuantity": 9998,
                        "priceable": true,
                        "unitOfMeasurement": "EACH",
                        "productRetailPrice": 6098.78,
                        "productTaxAmount": 213.63,
                        "productDiscountAmount": 3509.3,
                        "productLinePrice": 2589.48,
                        "productLineDiscounts": [
                          {
                            "type": "QUANTITY",
                            "amount": 3499.3
                          },
                          {
                            "type": "COUPON",
                            "amount": 10
                          }
                        ],
                        "productLineDetails": [
                          {
                            "detailCode": "0173",
                            "priceRequired": false,
                            "priceOverridable": false,
                            "description": "CLR 1S Copy/Print",
                            "unitQuantity": 9998,
                            "quantity": 9998,
                            "detailPrice": 2589.48,
                            "detailDiscountPrice": 3509.3,
                            "detailUnitPrice": 0.61,
                            "detailDiscountedUnitPrice": 0.351,
                            "detailDiscounts": [
                              {
                                "type": "QUANTITY",
                                "amount": 3499.3
                              },
                              {
                                "type": "COUPON",
                                "amount": 10
                              }
                            ],
                            "detailCategory": "PRINTING"
                          }
                        ],
                        "name": "Multi Sheet",
                        "userProductName": "plus-icon",
                        "type": "PRINT_ORDER"
                      }
                    ],
                    "deliveryLines": [
                      {
                        "recipientReference": "4587",
                        "priceable": true,
                        "deliveryLinePrice": 0,
                        "deliveryRetailPrice": 0,
                        "deliveryLineType": "PACKING_AND_HANDLING",
                        "deliveryDiscountAmount": 0
                      },
                      {
                        "recipientReference": "4587",
                        "estimatedDeliveryLocalTime": "2024-04-19T17:00:00",
                        "estimatedShipDate": "2024-04-19",
                        "priceable": false,
                        "deliveryLinePrice": 19.99,
                        "deliveryRetailPrice": 19.99,
                        "deliveryTaxAmount": 1.65,
                        "deliveryLineType": "SHIPPING",
                        "deliveryDiscountAmount": 0,
                        "deliveryLineDiscounts": [],
                        "recipientContact": {
                          "personName": {
                            "firstName": "Mohan",
                            "lastName": "Kumar"
                          },
                          "company": {
                            "name": "FXO"
                          },
                          "emailDetail": {
                            "emailAddress": "avneesh.maurya.osv@fedex.com",
                            "primary": false
                          },
                          "phoneNumberDetails": [
                            {
                              "phoneNumber": {
                                "number": "9899980009"
                              },
                              "usage": "PRIMARY"
                            }
                          ]
                        },
                        "shipmentDetails": {
                          "address": {
                            "streetLines": [
                              "7900 Legacy Drive",
                              null
                            ],
                            "city": "Plano",
                            "stateOrProvinceCode": "TX",
                            "postalCode": "75024",
                            "countryCode": "US",
                            "addressClassification": "HOME"
                          },
                          "serviceType": "LOCAL_DELIVERY_PM",
                          "specialServices": []
                        }
                      }
                    ],
                    "discounts": [
                      {
                        "type": "COUPON",
                        "amount": 10
                      },
                      {
                        "type": "QUANTITY",
                        "amount": 3499.3
                      }
                    ],
                    "responsibleLocationId": "DNEK",
                    "supportContact": {
                      "address": {
                        "streetLines": [
                          "8290 State Highway 121",
                          null
                        ],
                        "city": "Frisco",
                        "stateOrProvinceCode": "TX",
                        "postalCode": "75034",
                        "countryCode": "US"
                      },
                      "email": "usa0798@fedex.com",
                      "phoneNumberDetails": {
                        "phoneNumber": {
                          "number": "972.731.0997"
                        },
                        "usage": "PRIMARY"
                      }
                    },
                    "rateQuoteId": "eyJxdW90ZUlkIjoiODE2MzIyYmQtYjExYS00ZjI4LWI2MWQtMzVhY2QyYzBjMjczIiwiY2FydElkIjoiYzMzNjI2MDAtNGJkOS00YmEwLTk2YzItMzZmZjBlNWNjMTk0In0="
                  }
                ]
              }
            }
          }}';
        $responseData = json_decode($responseData, true);
        $this->dataObjectFactoryMock->expects($this->any())->method('getQuoteData')->willReturn($this->quoteMock);
        $this->dataObjectFactoryMock->expects($this->any())->method('getOrderNumber')->willReturn('1234');
        $this->dataObjectFactoryMock->expects($this->any())->method('getIsPickup')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getGtn')->willReturn('123456');
        $this->quoteMock->expects($this->any())->method('getId')->willReturn('123');
        $this->submitOrderModelAPIMock->expects($this->any())->method('callRateQuoteApi')->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('setTimeoutFlag')->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('getTransactionAPIResponse')
            ->willReturn($responseData);
        $this->submitOrderModelAPIMock->expects($this->any())->method('updateQuoteStatusAndTimeoutFlag')
            ->willReturnSelf();
        $this->orderApprovalViewModel->expects($this->any())->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);
        $this->orderApprovalViewModel->expects($this->any())->method('getOrderPendingApproval')
            ->willReturn($responseData);
        $orderData = [
            'status' => 'confirmed',
            'order_id' => 123,
        ];
        $this->orderApprovalViewModel->expects($this->any())->method('b2bOrderSendEmail')
            ->willReturn($orderData);
        $this->submitOrderHelper->expects($this->any())->method('clearQuoteCheckoutSessionAndStorage')
            ->willReturnSelf();
        $this->submitOrderModelAPIMock->expects($this->any())->method('createOrderBeforePayment')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->any())->method('save')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('getQuoteId')->willReturn(110);
        $this->orderMock->expects($this->any())->method('getId')->willReturn(12345);
        $this->orderMock->expects($this->any())->method('getIncrementId')->willReturn(1682656386712873);
        $this->orderMock->expects($this->any())->method('getStatus')->willReturn('pending');
        $this->marketPlaceHelperMock->expects($this->once())
            ->method('isEssendantToggleEnabled')
            ->willReturn(true);
        $this->assertNotNull(
            $this->submitOrderBuilderMock->callRateQuoteApiWithCommitAction(
                $logHeader,
                $this->dataObjectFactoryMock,
                $paymentData
            )
        );
    }

    public function testFixQuoteShippingAddressEmailWithAlternateContactInfo()
    {
        $contactInformation = (object)[
            'isAlternatePerson' => 1,
            'alternate_email' => 'alternate@example.com',
        ];
        $pickupData = (object)[
            'contactInformation' => $contactInformation
        ];
        $requestData = (object)[
            'pickupData' => $pickupData
        ];

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_d209119')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getIsAlternate')
            ->willReturn(true);
        $this->quoteMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->addressMock);

        $this->addressMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('contact@example.com');
        $this->addressMock->expects($this->once())
            ->method('setEmail')
            ->with('alternate@example.com');

        $this->submitOrderBuilderMock->fixQuoteShippingAddressEmailWithAlternateContactInfo($this->quoteMock, $requestData);

        $this->assertTrue(true);
    }
}
