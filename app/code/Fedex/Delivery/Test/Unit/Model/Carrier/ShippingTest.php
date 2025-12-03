<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Model;

use Fedex\Delivery\Helper\Data as DeliveryData;
use Fedex\Delivery\Helper\Delivery;
use Fedex\Delivery\Model\Carrier\Shipping;
use Fedex\Punchout\Helper\Data as PunchoutData;
use Magento\Checkout\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;

/**
 * ShippingTest Model
 */
class ShippingTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfig;
    protected $selfRegMock;
    protected $companyRepository;
    protected $companyInterface;
    protected $toggleConfig;
    /**
     * @var (\Magento\Checkout\Model\Cart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cart;
    /**
     * @var (\Magento\Quote\Model\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quote;
    protected $ship;
    public const ZIP_CODE = '75024';
    public const CARRIER_SHIPPING_ACTIVE = 'carriers/fedexshipping/active';
    public const ESTIMATED_TIME = "2021-06-06 04:00:00";
    public const PUNCHOUT_API_URL = '/index.php/rest/V1/fedex/eprocurement';
    public const ORDER_STATUS_URL = '/rest/V1/fedexoffice/orders/2010250333434900/status';

    /**
     * @var ErrorFactory|MockObject
     */
    private $rateErrorFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ResultFactory|MockObject
     */
    private $rateResultFactory;

    /**
     * @var Delivery|MockObject
     */
    private $deliveryHelper;

    /**
     * @var PunchoutData|MockObject
     */
    private $gateTokenHelper;

    /**
     * @var MethodFactory|MockObject
     */
    private $rateMethodFactory;

    /**
     * @var Method|MockObject
     */
    private $rateMethod;

    /**
     * @var DeliveryData|MockObject
     */
    private $helper;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $configInterface;

    /**
     * @var State|MockObject
     */
    private $state;

    /**
     * @var Request|MockObject
     */
    private $requestU;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestObj;

    /**
     * @var AbstractCarrierInterface|MockObject
     */
    private $abstractCarrier;

    /**
     * @var RateRequest|MockObject
     */
    private $rateRequest;

    /**
     * @var Result|MockObject
     */
    private $rateResult;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSession;

    /**
     * @var SessionFactory|MockObject
     */
    private $checkoutSessionFactory;

    /**
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var QuoteHelper
     */
    private $quoteHelper;



    protected function setUp(): void
    {
        $this->abstractCarrier = $this->getMockBuilder(AbstractCarrierInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfigFlag'])
            ->getMockForAbstractClass();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'isSetFlag', 'getValue'])
            ->getMockForAbstractClass();
        $this->rateRequest = $this->getMockBuilder(RateRequest::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getAllItems',
                    'getDestStreet',
                    'getDestCountryId',
                    'getDestRegionId',
                    'getDestPostcode',
                    'getDestCity'
                ]
            )
            ->getMock();
        $this->rateErrorFactory = $this->getMockBuilder(ErrorFactory::class)->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->rateResultFactory = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->rateResult = $this->getMockBuilder(Result::class)->disableOriginalConstructor()
            ->setMethods(['append'])
            ->getMock();
        $this->selfRegMock = $this->getMockBuilder(SelfReg::class)->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer', 'getDeliveryOptionsData'])
            ->getMock();
        $this->deliveryHelper = $this->getMockBuilder(Delivery::class)->disableOriginalConstructor()
            ->setMethods([
                'getPickOption',
                'getRateOptions',
                'getDeliveryOptions',
                'isItPickup',
                'getExpectedDateFormat',
                'getExpectedDate',
                'isDeliveryApiMockEnabled',
                'getDeliveryMockApiUrl',
                'isCheckoutQuotePriceableDisable'
            ])->getMock();
        $this->gateTokenHelper = $this->getMockBuilder(PunchoutData::class)->disableOriginalConstructor()
            ->setMethods(['getTazToken', 'getAuthGatewayToken', 'getPHPSessionId'])
            ->getMock();
        $this->rateMethodFactory = $this->getMockBuilder(MethodFactory::class)->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->rateMethod = $this->getMockBuilder(Method::class)->disableOriginalConstructor()
            ->setMethods(
                [
                    'setCarrier',
                    'setCarrierTitle',
                    'setMethod',
                    'setMethodTitle',
                    'setPrice',
                    'setCost',
                    'setExpected',
                    'setData'
                ]
            )
            ->getMock();
        $this->helper = $this->getMockBuilder(DeliveryData::class)->disableOriginalConstructor()
            ->setMethods(
                [
                    'getIsDelivery',
                    'getGateToken',
                    'isCommercialCustomer',
                    'getCompanySite',
                    'getApiToken',
                    'isOurSourced',
                    'isEproCustomer',
                    'updateDateTimeFormat',
                    'isD175160ToggleEnabled',
                    'getMessageError',
                ]
            )
            ->getMock();
        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->state = $this->getMockBuilder(State::class)->disableOriginalConstructor()
            ->setMethods(['getAreaCode'])
            ->getMock();
        $this->requestU = $this->getMockBuilder(Request::class)->disableOriginalConstructor()
            ->setMethods(['getRequestUri'])
            ->getMock();
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setProductionLocationId',
                    'setDeliveryOptions',
                    'unsProductionLocationId',
                    'getCustomShippingMethodCode',
                    'getCustomShippingCarrierCode',
                    'getCustomShippingTitle',
                    'getCustomShippingPrice',
                    'getDeliveryOptions',
                    'getServiceType',
                    'getShippingCost',
                    'setLocationIds'
                    ]
            )
            ->getMock();
        $this->checkoutSessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->requestObj = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()
            ->setMethods(['getContent', 'getPost'])
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
        ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomerCompany',
                    'getCustomShippingMethodCode',
                    'getCustomShippingCarrierCode',
                    'getCustomShippingPrice',
                    'getCustomShippingTitle',
                    'getDeliveryOptionsResponse',
                    'setDeliveryOptionsResponse'
                ]
            )
            ->getMock();
        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)->disableOriginalConstructor()
            ->setMethods(['getRecipientAddressFromPo', 'getAllowProductionLocation', 'getProductionLocationOption'])
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
        ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->cart = $this->getMockBuilder(\Magento\Checkout\Model\Cart::class)->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteHelper = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFullMiraklQuote', 'isMiraklQuote'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->ship = $objectManagerHelper->getObject(
            Shipping::class,
            [
                'rateErrorFactory' => $this->rateErrorFactory,
                'logger' => $this->logger,
                'rateResultFactory' => $this->rateResultFactory,
                'deliveryHelper' => $this->deliveryHelper,
                'gateTokenHelper' => $this->gateTokenHelper,
                'rateMethodFactory' => $this->rateMethodFactory,
                'helper' => $this->helper,
                'configInterface' => $this->configInterface,
                'state' => $this->state,
                'requestU' => $this->requestU,
                'requestObj' => $this->requestObj,
                'checkoutSessionFactory' => $this->checkoutSessionFactory,
                'checkoutSession' => $this->checkoutSession,
                'toggleConfig' => $this->toggleConfig,
                'customerSession' => $this->customerSession,
                'companyRepository' => $this->companyRepository,
                'selfregHelper' => $this->selfRegMock,
                'scopeConfig' => $this->scopeConfig,
                'quoteHelper' => $this->quoteHelper,
            ]
        );
    }

    /**
     * Test getAllowedMethods.
     *
     * @return void
     */
    public function testGetAllowedMethods()
    {
        $response = ['fedexshipping' => false];
        $this->abstractCarrier->expects($this->any())->method('getConfigData')->with('name')->willReturn(false);

        $this->assertEquals($response, $this->ship->getAllowedMethods());
    }

    /**
     * getRequestDataFirstAssert
     *
     * @return array
     */
    public function getRequestDataFirstAssert()
    {
        $requestData['addressInformation']['shipping_address']['street'][0] = "Test";
        $requestData['addressInformation']['shipping_address']['country_id'] = "US";
        $requestData['addressInformation']['shipping_address']['region_id'] = "45";
        $requestData['addressInformation']['shipping_address']['postcode'] = "226012";
        $requestData['addressInformation']['shipping_address']['city'] = "Lucknow";
        $requestData['site_name'] = "Test";
        $requestData['product'] = "Hello";
        $requestData['association'] = "association";
        $requestData['access_token'] = "token";
        $requestData['token_type'] = "auth";
        $requestData['gatewayToken'] = "gatewayToken";
        $requestData['addressInformation']['shipping_method_code'] = "PICKUP";
        $requestData['addressInformation']['shipping_carrier_code'] = "fedex";
        $requestData['pickUpIdLocation'] = "15";
        $requestData['reloadOptions'] = 0;
        $requestData['productionLocation'] = 1234;
        return $requestData;
    }

    /**
     * Test collectRates.
     *
     * @return void
     */
    public function testCollectRatesWithDisable()
    {
        $this->configInterface->expects($this->any())
        ->method('isSetFlag')
        ->with(
            static::CARRIER_SHIPPING_ACTIVE,
            ScopeInterface::SCOPE_STORE,
            null
        )
        ->willReturn(false);

        $this->assertFalse($this->ship->collectRates($this->rateRequest));
    }

    /**
     * Test collectRates.
     *
     * @return void
     */
    public function testCollectRates()
    {
        $paymentData = '{
            "paymentData": "{\"paymentMethod\":\"cc\",\"year\":\"2025\",\"expire\":\"08\",\"nameOnCard\":\"Yogesh\",\"number\":\"*1111\",\"cvv\":\"111\",\"isBillingAddress\":false,\"isFedexAccountApplied\":false,\"fedexAccountNumber\":null,\"creditCardType\":\"https://staging3.office.fedex.com/pub/media/wysiwyg/Visa.png\",\"billingAddress\":{\"countryId\":\"US\",\"regionId\":\"169\",\"region\":\"\",\"street\":[\"234\"],\"company\":\"Infogain\",\"telephone\":\"9514265874\",\"postcode\":\"75024\",\"city\":\"Plano\",\"firstname\":\"Yogesh\",\"lastname\":\"Suryawanshi\",\"customAttributes\":[{\"attribute_code\":\"email_id\",\"value\":\"Yogesh.suryawanshi@infogain.com\"},{\"attribute_code\":\"ext\",\"value\":\"\"}],\"altFirstName\":\"\",\"altLastName\":\"\",\"altPhoneNumber\":\"\",\"altEmail\":\"\",\"altPhoneNumberext\":\"\",\"is_alternate\":false}}",
            "orderNumber": "2010128931541244",
            "encCCData": "q6y0PKLO",
            "pickupData": "{\"contactInformation\":{\"contact_fname\":\"Attri\",\"contact_lname\":\"Kumar\",\"contact_email\":\"attri.kumar@infogain.com\",\"contact_number\":\"1098647589\",\"alternate_fname\":\"\",\"alternate_lname\":\"\",\"alternate_email\":\"\",\"alternate_number\":\"\",\"isAlternatePerson\":false},\"addressInformation\":{\"pickup_location_name\":\"Dallas TX Valwood\",\"estimate_pickup_time\":\"00:00:00\",\"estimate_pickup_time_for_api\":\"00:00:00\",\"pickup_location_street\":\"13940 N STEMMONS FWY\",\"pickup_location_city\":\"Dallas\",\"pickup_location_state\":\"TX\",\"pickup_location_zipcode\":\"75234\",\"pickup_location_country\":\"US\",\"pickup_location_date\":\"2021-12-16T16:00:00\",\"pickup\":true,\"shipping_address\":\"\",\"billing_address\":\"\",\"shipping_method_code\":\"PICKUP\",\"shipping_carrier_code\":\"fedexshipping\",\"shipping_detail\":{\"carrier_code\":\"fedexshipping\",\"method_code\":\"PICKUP\",\"carrier_title\":\"Fedex Store Pickup\",\"method_title\":\"0651\",\"amount\":0,\"base_amount\":0,\"available\":true,\"error_message\":\"\",\"price_excl_tax\":0,\"price_incl_tax\":0}},\"orderNumber\":null}"
        }';
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['estimatedDeliveryDuration']['unit'] = "BUSINESSDAYS";
        $deliveryOption['estimatedDeliveryDuration']['value'] = "10";
        $deliveryOption['serviceDescription'] = "Fedex";
        $deliveryOption['estimatedShipmentRate'] = "10";
        $deliveryOption['productionLocationId'] = null;
        $deliveryOptions[] = $deliveryOption;

        $this->checkoutSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->checkoutSession);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->configInterface->expects($this->any())
            ->method('isSetFlag')
            ->with(
                static::CARRIER_SHIPPING_ACTIVE,
                ScopeInterface::SCOPE_STORE,
                null
            )->willReturn(true);

        $this->deliveryHelper->expects($this->any())->method('isItPickup')->willReturn(['pickupDataaaaa' => 'test']);
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->helper->expects($this->any())->method('isOurSourced')->willReturn(true);
        $this->requestObj->expects($this->any())
            ->method('getContent')
            ->willReturn($paymentData);
        $this->checkoutSession->expects($this->any())
            ->method('getServiceType')
            ->willReturn('GROUND_US');
        $this->checkoutSession->expects($this->any())
            ->method('getShippingCost')
            ->willReturn('12');
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);
        $this->companyInterface->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');
        $this->checkoutSession->expects($this->any())
            ->method('setProductionLocationId')
            ->willReturn(123);
        $this->helper->expects($this->any())
            ->method('getIsDelivery')
            ->willReturn(true);
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->gateTokenHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(json_encode(['access_token' => 'token', 'token_type' => 'type']));
        $this->helper->expects($this->any())
            ->method('getApiToken')
            ->willReturn(['token' => 'token', 'type' => 'type']);
        $this->rateRequest->expects($this->any())
            ->method('getDestStreet')
            ->willReturn('plano');
        $this->rateRequest->expects($this->any())
            ->method('getDestCountryId')
            ->willReturn('US');
        $this->rateRequest->expects($this->any())
            ->method('getDestRegionId')
            ->willReturn('45');
        $this->rateRequest->expects($this->any())
            ->method('getDestPostcode')
            ->willReturn(static::ZIP_CODE);
        $this->rateRequest->expects($this->any())
            ->method('getDestCity')
            ->willReturn('Texas');
        $this->requestObj->expects($this->any())
            ->method('getPost')
            ->willReturn(json_decode(json_encode($paymentData), true));
        $deliveryOption['serviceType'] = "LOCAL_DELIVERY";
        $this->deliveryHelper->expects($this->any())
            ->method('getDeliveryOptions')
            ->willReturn($deliveryOptions);

        $this->customerSession->expects($this->any())
            ->method('getDeliveryOptionsResponse')
            ->willReturn($deliveryOptions);
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getExtShippingInfo'])
            ->getMock();
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $item->expects($this->any())
            ->method('getQuote')
            ->willReturn($quote);

        $item->expects($this->any())
            ->method('getExtShippingInfo')
            ->willReturn(null);

        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->rateRequest->expects($this->any())->method('getAllItems')->willReturn([$item]);

        $this->checkoutSession->expects($this->any())
            ->method('setLocationIds')
            ->willReturn(['1234','4567']);

        $this->assertEquals($this->rateResult, $this->ship->collectRates($this->rateRequest));
    }

    public function testCollectRatesExceptionOnNoQuotesAvailable()
    {
        $deliveryOptions = [];
        $paymentData = '{
            "paymentData": "{\"paymentMethod\":\"cc\",\"year\":\"2025\",\"expire\":\"08\",\"nameOnCard\":\"Yogesh\",\"number\":\"*1111\",\"cvv\":\"111\",\"isBillingAddress\":false,\"isFedexAccountApplied\":false,\"fedexAccountNumber\":null,\"creditCardType\":\"https://staging3.office.fedex.com/pub/media/wysiwyg/Visa.png\",\"billingAddress\":{\"countryId\":\"US\",\"regionId\":\"169\",\"region\":\"\",\"street\":[\"234\"],\"company\":\"Infogain\",\"telephone\":\"9514265874\",\"postcode\":\"75024\",\"city\":\"Plano\",\"firstname\":\"Yogesh\",\"lastname\":\"Suryawanshi\",\"customAttributes\":[{\"attribute_code\":\"email_id\",\"value\":\"Yogesh.suryawanshi@infogain.com\"},{\"attribute_code\":\"ext\",\"value\":\"\"}],\"altFirstName\":\"\",\"altLastName\":\"\",\"altPhoneNumber\":\"\",\"altEmail\":\"\",\"altPhoneNumberext\":\"\",\"is_alternate\":false}}",
            "orderNumber": "2010128931541244",
            "encCCData": "q6y0PKLO",
            "pickupData": "{\"contactInformation\":{\"contact_fname\":\"Attri\",\"contact_lname\":\"Kumar\",\"contact_email\":\"attri.kumar@infogain.com\",\"contact_number\":\"1098647589\",\"alternate_fname\":\"\",\"alternate_lname\":\"\",\"alternate_email\":\"\",\"alternate_number\":\"\",\"isAlternatePerson\":false},\"addressInformation\":{\"pickup_location_name\":\"Dallas TX Valwood\",\"estimate_pickup_time\":\"00:00:00\",\"estimate_pickup_time_for_api\":\"00:00:00\",\"pickup_location_street\":\"13940 N STEMMONS FWY\",\"pickup_location_city\":\"Dallas\",\"pickup_location_state\":\"TX\",\"pickup_location_zipcode\":\"75234\",\"pickup_location_country\":\"US\",\"pickup_location_date\":\"2021-12-16T16:00:00\",\"pickup\":true,\"shipping_address\":\"\",\"billing_address\":\"\",\"shipping_method_code\":\"PICKUP\",\"shipping_carrier_code\":\"fedexshipping\",\"shipping_detail\":{\"carrier_code\":\"fedexshipping\",\"method_code\":\"PICKUP\",\"carrier_title\":\"Fedex Store Pickup\",\"method_title\":\"0651\",\"amount\":0,\"base_amount\":0,\"available\":true,\"error_message\":\"\",\"price_excl_tax\":0,\"price_incl_tax\":0}},\"orderNumber\":null}"
        }';
        $this->checkoutSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->checkoutSession);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->requestObj->expects($this->any())
            ->method('getContent')
            ->willReturn($paymentData);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);
        $this->deliveryHelper->expects($this->any())->method('isItPickup')
            ->willReturn(false);
        $this->companyInterface->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');
        $this->checkoutSession->expects($this->any())
            ->method('setProductionLocationId')
            ->willReturn(123);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();
        $item->expects($this->any())
            ->method('getQuote')
            ->willReturn($quote);
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')
            ->willReturn(false);
        $this->quoteHelper->expects($this->any())->method('isMiraklQuote')
            ->willReturn(true);
        $this->rateRequest->expects($this->any())->method('getAllItems')
            ->willReturn([$item]);
        $this->configInterface->expects($this->any())
            ->method('isSetFlag')
            ->with(
                static::CARRIER_SHIPPING_ACTIVE,
                ScopeInterface::SCOPE_STORE,
                null
            )->willReturn(true);
        $this->helper->expects($this->any())
            ->method('getIsDelivery')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getDeliveryOptions')
            ->willReturn($deliveryOptions);
        $this->selfRegMock->expects($this->any())
            ->method('getDeliveryOptionsData')
            ->willReturn([]);
        $cookieMocked = '0202840928409284';
        $this->gateTokenHelper->expects($this->any())
            ->method('getPHPSessionId')
            ->willReturn($cookieMocked);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);
        $this->helper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(false);
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('getDeliveryOptionsResponse')
            ->willReturn($deliveryOptions);
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->ship->collectRates($this->rateRequest);
    }

    /**
     * Test CollectRatesForOptions
     *
     * @return void
     */
    public function testCollectRatesForOptions()
    {
        $requestData = $this->getRequestDataFirstAssert();
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['estimatedDeliveryDuration']['unit'] = "BUSINESSDAYS";
        $deliveryOption['estimatedDeliveryDuration']['value'] = "10";
        $deliveryOption['serviceDescription'] = "Fedex";
        $deliveryOption['estimatedShipmentRate'] = "10";
        $deliveryOption['productionLocationId'] = null;
        $deliveryOptions[] = $deliveryOption;

        $this->checkoutSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->checkoutSession);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->configInterface->expects($this->any())
            ->method('isSetFlag')
            ->with(
                static::CARRIER_SHIPPING_ACTIVE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(true);
        $this->requestObj->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);
        $this->companyInterface->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');
        $this->checkoutSession->expects($this->any())
            ->method('setProductionLocationId')
            ->willReturn(123);
        $this->helper->expects($this->any())
            ->method('getIsDelivery')
            ->willReturn(true);
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->gateTokenHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(json_encode(['access_token' => 'token', 'token_type' => 'type']));
        $this->helper->expects($this->any())
            ->method('getApiToken')
            ->willReturn(['token' => 'token', 'type' => 'type']);
        $this->rateRequest->expects($this->any())
            ->method('getDestStreet')
            ->willReturn('plano');
        $this->rateRequest->expects($this->any())
            ->method('getDestCountryId')
            ->willReturn('US');
        $this->rateRequest->expects($this->any())
            ->method('getDestRegionId')
            ->willReturn('45');
        $this->rateRequest->expects($this->any())
            ->method('getDestPostcode')
            ->willReturn(static::ZIP_CODE);
        $this->rateRequest->expects($this->any())
            ->method('getDestCity')
            ->willReturn('Texas');
        $this->requestObj->expects($this->any())
            ->method('getPost')
            ->with('data')
            ->willReturn(json_encode($this->getRequestDataSecondAssert()));
        $deliveryOption['serviceType'] = "LOCAL_DELIVERY";
        $this->deliveryHelper->expects($this->any())
            ->method('getDeliveryOptions')
            ->willReturn($deliveryOptions);
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getQuote'])
            ->getMock();

        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->rateRequest->expects($this->any())->method('getAllItems')->willReturn([$item]);

        $this->assertEquals($this->rateResult, $this->ship->collectRates($this->rateRequest));
    }


    /**
     * Test collectRates.
     *
     * @return void
     */
    public function testCollectRatesWithFlagIsPickup()
    {
        $paymentData = '{
            "isPickup": "true"
        }';

        $this->configInterface->expects($this->any())
            ->method('isSetFlag')
            ->with(
                static::CARRIER_SHIPPING_ACTIVE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn(true);
        $this->requestObj->expects($this->any())
            ->method('getContent')
            ->willReturn($paymentData);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(1);

        $this->assertFalse($this->ship->collectRates($this->rateRequest));
    }

    /**
     * GetRequestDataSecondAssert
     *
     * @return array
     */
    public function getRequestDataSecondAssert()
    {
        $requestData['addressInformation']['shipping_address']['street'][0] = "Test";
        $requestData['addressInformation']['shipping_address']['country_id'] = "US";
        $requestData['addressInformation']['shipping_address']['region_id'] = "45";
        $requestData['addressInformation']['shipping_address']['postcode'] = "226012";
        $requestData['addressInformation']['shipping_address']['city'] = "Lucknow";
        $requestData['site_name'] = "Test";
        $requestData['product'] = "Hello";
        $requestData['association'] = "association";
        $requestData['access_token'] = "token";
        $requestData['token_type'] = "auth";
        $requestData['gatewayToken'] = "gatewayToken";
        $requestData['addressInformation']['shipping_method_code'] = "PICKUP";
        $requestData['addressInformation']['shipping_carrier_code'] = "fedex";
        $requestData['addressInformation']['shipping_detail']['method_title'] = "PICKUP";
        $requestData['pickUpIdLocation'] = "15";
        $requestData['reloadOptions'] = 1;
        $requestData['productionLocation'] = 1234;
        $requestData['pickupData'] = json_encode($requestData);

        return $requestData;
    }

    /**
     * Test collectRatesSetQuoteRateData
     *
     * @return void
     */
    public function testCollectRatesSetQuoteRateData()
    {
        $this->checkoutSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->checkoutSession);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(2);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->abstractCarrier->expects($this->any())
            ->method('getConfigFlag')
            ->with('active')
            ->willReturnSelf();
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingMethodCode')
            ->willReturn('PICKUP');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingTitle')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingCarrierCode')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingPrice')
            ->willReturn('fedexshipping');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturnSelf();
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->collectRates($this->rateRequest));
    }

    /**
     * Test collectRatesSetQuoteRateData
     *
     * @return void
     */
    public function testCollectRatesSetQuoteRateDataFalse()
    {
        $this->checkoutSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->checkoutSession);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(2);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->abstractCarrier->expects($this->any())
            ->method('getConfigFlag')
            ->with('active')
            ->willReturnSelf();
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingMethodCode')
            ->willReturn('PICKUP');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingTitle')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingCarrierCode')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingPrice')
            ->willReturn('fedexshipping');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->collectRates($this->rateRequest));
    }

    /**
     * Test collectRatesSetQuoteRateDataForRetail
     *
     * @return void
     */
    public function testCollectRatesSetQuoteRateDataForRetail()
    {
        $this->checkoutSessionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->checkoutSession);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(2);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(false);
        $this->abstractCarrier->expects($this->any())
            ->method('getConfigFlag')
            ->with('active')
            ->willReturnSelf();
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingMethodCode')
            ->willReturn('PICKUP');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingTitle')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingCarrierCode')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingPrice')
            ->willReturn('fedexshipping');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturnSelf();
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->collectRates($this->rateRequest));
    }

    /**
     * Test collectRatesSetQuoteRateDataForRetail
     *
     * @return void
     */
    public function testCollectRatesSetQuoteRateDataForRetailFalse()
    {
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(2);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(false);
        $this->abstractCarrier->expects($this->any())
            ->method('getConfigFlag')
            ->with('active')
            ->willReturnSelf();
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingMethodCode')
            ->willReturn('PICKUP');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingTitle')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingCarrierCode')
            ->willReturn('fedexshipping');
        $this->checkoutSession->expects($this->any())
            ->method('getCustomShippingPrice')
            ->willReturn('fedexshipping');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->collectRates($this->rateRequest));
    }

     /**
     * Test IsOrderCreatedProgramtically
     *
     * @return void
     */
    public function testIsOrderCreatedProgramtically()
    {
        $this->requestU->expects($this->any())
        ->method('getRequestUri')
        ->willReturn(static::ORDER_STATUS_URL);
        $this->state->expects($this->any())
        ->method('getAreaCode')
        ->willReturn('webapi_rest');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getQuote'])
            ->getMock();
        $quote = $this->createMock(Quote::class);
        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();
        $shippingAddress->method('validate')
            ->willReturn(!empty($shippingAddress['firstname']));
        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();

        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $item->method('getQuote')
            ->willReturn($quote);
        $this->rateRequest->method('getAllItems')
            ->willReturn([$item]);
        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturnSelf();
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->isOrderCreatedProgramtically($this->rateRequest));
    }

    /**
     * Test IsOrderCreatedProgramticallyNoItems
     *
     * @return void
     */
    public function testIsOrderCreatedProgramticallyNoItems()
    {
        $this->requestU->expects($this->any())->method('getRequestUri')->willReturn(static::ORDER_STATUS_URL);
        $this->state->expects($this->any())->method('getAreaCode')->willReturn('webapi_rest');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->rateRequest->method('getAllItems')->willReturn([]);
        $this->assertNotNull($this->ship->isOrderCreatedProgramtically($this->rateRequest));
    }

    /**
     * Test IsOrderCreatedProgramticallyPickup
     *
     * @return void
     */
    public function testIsOrderCreatedProgramticallyPickup()
    {
        $this->requestU->expects($this->any())->method('getRequestUri')->willReturn(static::ORDER_STATUS_URL);
        $this->state->expects($this->any())->method('getAreaCode')->willReturn('webapi_rest');
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getQuote'])
            ->getMock();
        $quote = $this->createMock(Quote::class);
        $shippingAddressData['email'] = 'john.doe@example.com';
        $billingAddressData['email'] = 'john.doe@example.com';
        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();
        $shippingAddress->addData($shippingAddressData);
        $shippingAddress->method('validate')
            ->willReturn(!empty($shippingAddress['firstname']));
        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingMethod'])
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();

        $billingAddress->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn("shipping method PICKUP");
        $billingAddress->addData($billingAddressData);
        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $item->method('getQuote')
        ->willReturn($quote);
        $this->rateRequest->method('getAllItems')
        ->willReturn([$item]);

        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturnSelf();
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->isOrderCreatedProgramtically($this->rateRequest));
    }

    /**
     * Test isCartEmptySetShippingMethodAndRate
     *
     * @return void
     */
    public function testIsCartEmptySetShippingMethodAndRate()
    {
        $this->requestU->expects($this->any())
        ->method('getRequestUri')
        ->willReturn(static::PUNCHOUT_API_URL);
        $this->state->expects($this->any())
        ->method('getAreaCode')
        ->willReturn('webapi_rest');
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getQuote'])
            ->getMock();
        $quote = $this->createMock(Quote::class);
        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();
        $shippingAddress->method('validate')
            ->willReturn(!empty($shippingAddress['firstname']));
        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();

        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $item->method('getQuote')
            ->willReturn($quote);
        $this->rateRequest->method('getAllItems')
            ->willReturn([$item]);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturnSelf();
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->isCartEmptySetShippingMethodAndRate($this->rateRequest));
    }

    /**
     * Test isCartEmptySetShippingMethodAndRate
     *
     * @return void
     */
    public function testIsCartEmptySetShippingMethodNoItems()
    {
        $this->requestU->expects($this->any())->method('getRequestUri')->willReturn(static::PUNCHOUT_API_URL);
        $this->state->expects($this->any())->method('getAreaCode')->willReturn('webapi_rest');
        $this->rateRequest->method('getAllItems')->willReturn([]);
        $this->assertNotNull($this->ship->isCartEmptySetShippingMethodAndRate($this->rateRequest));
    }

    /**
     * Test isCartEmptySetShippingMethodPickupAndRate
     *
     * @return void
     */
    public function testIsCartEmptySetShippingMethodPickupAndRate()
    {
        $this->requestU->expects($this->any())->method('getRequestUri')->willReturn(static::PUNCHOUT_API_URL);
        $this->state->expects($this->any())->method('getAreaCode')->willReturn('webapi_rest');
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'getQuote'])
            ->getMock();
        $quote = $this->createMock(Quote::class);
        $shippingAddressData['email'] = 'john.doe@example.com';
        $billingAddressData['email'] = 'john.doe@example.com';
        $shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();
        $shippingAddress->addData($shippingAddressData);
        $shippingAddress->method('validate')
            ->willReturn(!empty($shippingAddress['firstname']));
        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShippingMethod'])
            ->onlyMethods(['validate'])
            ->getMockForAbstractClass();

        $billingAddress->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn("shipping method PICKUP");
        $billingAddress->addData($billingAddressData);
        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $quote->method('getShippingAddress')
            ->willReturn($shippingAddress);
        $quote->method('getBillingAddress')
            ->willReturn($billingAddress);
        $item->method('getQuote')
        ->willReturn($quote);
        $this->rateRequest->method('getAllItems')
        ->willReturn([$item]);
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('updateDateTimeFormat')
            ->willReturnSelf();
        $this->rateResultFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateResult);
        $this->rateResult->expects($this->any())
            ->method('append')
            ->willReturnSelf();
        $this->rateMethodFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->rateMethod);
        $this->rateMethod->expects($this->any())
            ->method('setCarrier')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCarrierTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethod')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setMethodTitle')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setPrice')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setCost')
            ->willReturnSelf();
        $this->rateMethod->expects($this->any())
            ->method('setExpected')
            ->willReturnSelf();

        $this->assertEquals($this->rateResult, $this->ship->isCartEmptySetShippingMethodAndRate($this->rateRequest));
    }

    /**
     * Test saveProductionLocationIdInQuote
     *
     * @return void
     */
    public function testSaveProductionLocationIdInQuote()
    {
        $requestData = $this->getRequestDataFirstAssert();

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->helper->expects($this->any())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->abstractCarrier->expects($this->any())
            ->method('getConfigFlag')
            ->with('active')
            ->willReturnSelf();
        $this->requestObj->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($requestData));
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())
            ->method('getAllowProductionLocation')
            ->willReturn(2);
        $this->companyInterface->expects($this->any())
            ->method('getProductionLocationOption')
            ->willReturn('recommended_location_all_locations');
        $this->checkoutSession->expects($this->any())
            ->method('setProductionLocationId')
            ->willReturn(123);
        $this->helper->expects($this->any())
            ->method('getIsDelivery')
            ->willReturn(true);

        $this->assertNull($this->ship->saveProductionLocationIdInQuote(true, $requestData));
    }

    /**
     * Test case for setPickupData
     */
    public function testsetPickupData()
    {
        $pickupRequestData = [
            'pickupDataaaaa' => 'test'
        ];
        $this->assertNotNull($this->ship->setPickupData($pickupRequestData));
    }

    /**
     * Test case for setQuoteRateData
     */
    public function testsetQuoteRateData()
    {
        $this->checkoutSession->expects($this->any())->method('getShippingCost')->willReturn('12');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertNull($this->ship->setQuoteRateData(true, false));
    }

    /**
     * Test case for getDeliveryOptionsData
     */
    public function testgetDeliveryOptionsData()
    {
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['estimatedDeliveryDuration']['unit'] = "BUSINESSDAYS";
        $deliveryOption['estimatedDeliveryDuration']['value'] = "10";
        $deliveryOption['serviceDescription'] = "Fedex";
        $deliveryOption['estimatedShipmentRate'] = "10";
        $deliveryOptions[] = $deliveryOption;
        $this->deliveryHelper->expects($this->any())->method('getDeliveryOptions')->willReturn($deliveryOptions);
        $this->assertNotNull($this->ship->getDeliveryOptionsData());
    }

    /**
     * Test case for getDeliveryOptionsDataWithEpro
     */
    public function testgetDeliveryOptionsDataWithEpro()
    {
        $deliveryOption['estimatedDeliveryLocalTime'] = static::ESTIMATED_TIME;
        $deliveryOption['serviceType'] = "GROUND_US";
        $deliveryOption['estimatedDeliveryDuration']['unit'] = "BUSINESSDAYS";
        $deliveryOption['estimatedDeliveryDuration']['value'] = "10";
        $deliveryOption['serviceDescription'] = "Fedex";
        $deliveryOption['estimatedShipmentRate'] = "10";
        $deliveryOptions[] = $deliveryOption;
        $this->deliveryHelper->expects($this->any())->method('getDeliveryOptions')->willReturn($deliveryOptions);
        $this->assertNotNull($this->ship->getDeliveryOptionsData(true, false));
    }

    /**
     * Test case for getDeliveryApiUrl
     */
    public function testgetDeliveryApiUrl()
    {
        $this->configInterface->expects($this->any())->method('isSetFlag')->willReturn("https://localhost:2424/order/fedexoffice/v2/deliveryoptions");
        $this->deliveryHelper->expects($this->any())->method('isDeliveryApiMockEnabled')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('getDeliveryMockApiUrl')->willReturn("https://localhost:2424/order/fedexoffice/v2/deliveryoptions");
        $this->assertNull($this->ship->getDeliveryApiUrl());
    }
}
