<?php
declare(strict_types=1);
namespace Fedex\Shipto\Test\Unit\Helper;

use Fedex\Shipto\Helper\Data;
use PHPUnit\Framework\TestCase;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Fedex\Email\Helper\SendEmail;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Framework\Phrase;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Header\Helper\Data as HeaderData;
use ReflectionClass;

class DataTest extends TestCase
{
        protected $orderRepository;
        /**
         * @var (\Magento\Sales\Api\Data\InvoiceInterface & \PHPUnit\Framework\MockObject\MockObject)
         */
        protected $invoiceInterface;
        protected $invoiceMock;
        /**
         * @var (\Magento\Sales\Api\Data\OrderInterface & \PHPUnit\Framework\MockObject\MockObject)
         */
        protected $orderInterface;
        protected $orderMock;
        protected $invoiceService;
        protected $transaction;
        protected $_customerSessionMock;
        protected $quoteFactoryMock;
        protected $quoteMock;
        protected $quoteInterfaceMock;
        /**
         * @var (\Magento\Customer\Model\Customer & \PHPUnit\Framework\MockObject\MockObject)
         */
        protected $customer;
        protected $customerInterface;
        protected $customerExtensionInterface;
        protected $companyCustomerInterface;
        protected $scopeConfig;
        protected $curlMock;
        protected $companyInterface;
        protected $regionMock;
        protected $countryMock;
        protected $toggleConfig;
        /**
         * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
         */
        protected $objectManager;
        protected $resourceConnectionMock;
        protected $ConnectionMock;
        protected $contextMock;
        protected $_customerSessionnMock;
        protected $customerRepositoryMock;
        protected $cartFactoryMock;
        protected $mailMock;
        protected $punchoutHelperMock;
        protected $companyRepositoryMock;
        protected $quoteRepositoryMock;
        protected $dataMock;
        private const COUNTRYCODE = 'US';
        private const TYPE = 'nearestAddress';
        private const ADDRESSCLASSIFICATION = 'BUSINESS';
        private const UNIT = 'MILES';
        private const MATCH_TYPE = 'ALL';
        private const SORT_BY = 'DISTANCE';

    protected function setUp(): void
    {

        $this->orderRepository = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->invoiceInterface = $this
            ->getMockBuilder(InvoiceInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->invoiceMock = $this
            ->getMockBuilder(Invoice::class)
            ->setMethods(['register','save','getOrder'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderInterface = $this
            ->getMockBuilder(OrderInterface::class)
            ->setMethods(['canInvoice'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderMock = $this
            ->getMockBuilder(Order::class)
            ->setMethods(['canInvoice','addStatusHistoryComment','setIsCustomerNotified','
            save','getCustomTaxAmount','getBaseGrandTotal','getGrandTotal'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceService = $this
            ->getMockBuilder(InvoiceService::class)
            ->setMethods(['prepareInvoice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transaction = $this
            ->getMockBuilder(Transaction::class)
            ->setMethods(['addObject','save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_customerSessionMock = $this
        ->getMockBuilder(Session::class)
        ->setMethods(['setAllLocations','getCustomer',
            'getCustomerCompany','getApiAccessToken','getApiAccessType','getAuthGatewayToken','getAllLocations'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->customerRepositoryMock = $this
        ->getMockBuilder(CustomerRepositoryInterface::class)
        ->setMethods(['getById'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->quoteFactoryMock = $this
        ->getMockBuilder(QuoteFactory::class)
        ->setMethods(['create'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->quoteMock = $this
         ->getMockBuilder(Quote::class)
         ->setMethods(['load','getCustomerFirstname','getCustomerLastname','getCustomerEmail','getSubtotal','getFailureEmailStatus','setFailureEmailStatus','save'])
         ->disableOriginalConstructor()
         ->getMock();

        $this->mailMock = $this
        ->getMockBuilder(SendEmail::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->punchoutHelperMock = $this
        ->getMockBuilder(PunchoutHelper::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->companyRepositoryMock = $this
        ->getMockBuilder(CompanyRepositoryInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->quoteRepositoryMock = $this
        ->getMockBuilder(CartRepositoryInterface::class)
        ->setMethods(['get','getFailureEmailStatus'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->quoteInterfaceMock = $this
        ->getMockBuilder(CartInterface::class)
        ->setMethods(['get','getFailureEmailStatus','setFailureEmailStatus','getCustomerId',
            'getCustomerFirstname','getCustomerLastname','getCustomerEmail','getSubtotal','save','getDiscount'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->customer = $this
        ->getMockBuilder(Customer::class)
        ->setMethods(['getId'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->customerInterface = $this
        ->getMockBuilder(CustomerInterface::class)
        ->setMethods(['getExtensionAttributes'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->customerExtensionInterface = $this
        ->getMockBuilder(CustomerExtensionInterface::class)
        ->setMethods(['getCompanyAttributes'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->companyCustomerInterface = $this
        ->getMockBuilder(CompanyCustomerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getValue'])
            ->getMockForAbstractClass();

        $this->curlMock = $this
        ->getMockBuilder(Curl::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->companyInterface = $this
        ->getMockBuilder(CompanyInterface::class)
        ->setMethods(['getIsQuoteRequest','getIsOrderReject','getIsExpiredOrder','getIsExpiringOrder','getHcToggle'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

         $this->regionMock = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
                                        ->setMethods(['loadByCode', 'getName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->countryMock = $this->getMockBuilder(\Magento\Directory\Model\Country::class)
                                        ->setMethods(['loadByCode', 'getName'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
                                        ->setMethods(['getToggleConfigValue'])
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
        ->setMethods(['getConnection'])
        ->disableOriginalConstructor()
        ->getMock();

    $this->ConnectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)->setMethods(['getTable'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->dataMock = $this->objectManager->getObject(
            Data::class,
            [
                'customerSession' => $this->_customerSessionMock,
                'customerRepository' => $this->customerRepositoryMock,
                'quoteFactory' => $this->quoteFactoryMock,
                'mail' => $this->mailMock,
                'punchoutHelper' => $this->punchoutHelperMock,
                'companyRepository' => $this->companyRepositoryMock,
                'quoteRepository' => $this->quoteRepositoryMock,
                'orderRepository' => $this->orderRepository,
                'invoiceService' => $this->invoiceService,
                'transaction' => $this->transaction,
                'curl' =>  $this->curlMock,
                'scopeConfig' => $this->scopeConfig,
                'region' => $this->regionMock,
                'country' => $this->countryMock,
                'toggleConfig' => $this->toggleConfig,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    public function testGetAssignedCompany()
    {

        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->dataMock->getAssignedCompany($this->customerInterface);
    }

    public function testGetIsOrderRejectNotificationEnable()
    {

        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getIsOrderReject');
        $this->dataMock->getIsOrderRejectNotificationEnable($this->customerInterface);
    }

    public function testSendOrderFailureNotification()
    {
        $quoteId = 123;
        $rejectionReason = "Invalid PO number";
        $taz= '{"access_token":"123","token_type":"123"}';

        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getFailureEmailStatus')->willReturn(0);
        $this->quoteInterfaceMock->expects($this->any())->method('setFailureEmailStatus')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('save')->willReturnSelf();
        $this->quoteRepositoryMock->expects($this->any())->method('get')->willReturn($this->quoteInterfaceMock);
        $this->quoteInterfaceMock->expects($this->any())->method('getFailureEmailStatus')->willReturn(0);
         $this->quoteInterfaceMock->expects($this->any())->method('getCustomerId')->willReturn(23);

        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('1111');
        $tazToken= $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn($taz);

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getIsOrderReject')->willReturn(true);
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerFirstname')->willReturn('neeraj');
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerLastname')->willReturn('gupta');
        $this->quoteInterfaceMock->expects($this->any())->method('getDiscount')->willReturn(10);
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerEmail')->willReturn('neeraj2.gupta@infogain.com');
        $this->quoteInterfaceMock->expects($this->any())->method('getSubtotal')->willReturn(100);
        $this->quoteInterfaceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturn($this->ConnectionMock);
        $this->ConnectionMock->expects($this->any())->method('getTable')->willReturn('quote');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->ConnectionMock->expects($this->any())->method('update')->willReturnSelf();
        $this->dataMock->sendOrderFailureNotification($quoteId, $rejectionReason);
    }

    public function testSendOrderFailureNotificationForDirectQuery()
    {
        $quoteId = 123;
        $rejectionReason = "Invalid PO number";
        $taz= '{"access_token":"123","token_type":"123"}';

        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getFailureEmailStatus')->willReturn(0);
        $this->quoteInterfaceMock->expects($this->any())->method('setFailureEmailStatus')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('save')->willReturnSelf();
        $this->quoteRepositoryMock->expects($this->any())->method('get')->willReturn($this->quoteInterfaceMock);
        $this->quoteInterfaceMock->expects($this->any())->method('getFailureEmailStatus')->willReturn(0);
         $this->quoteInterfaceMock->expects($this->any())->method('getCustomerId')->willReturn(23);

        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('1111');
        $tazToken= $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn($taz);

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getIsOrderReject')->willReturn(true);
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerFirstname')->willReturn('neeraj');
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerLastname')->willReturn('gupta');
        $this->quoteInterfaceMock->expects($this->any())->method('getDiscount')->willReturn(10);
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerEmail')->willReturn('neeraj2.gupta@infogain.com');
        $this->quoteInterfaceMock->expects($this->any())->method('getSubtotal')->willReturn(100);
        $this->quoteInterfaceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturn($this->ConnectionMock);
        $this->ConnectionMock->expects($this->any())->method('getTable')->willReturn('quote');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->ConnectionMock->expects($this->any())->method('update')->willReturnSelf();
        $this->dataMock->sendOrderFailureNotification($quoteId, $rejectionReason);
    }

    public function testSendOrderFailureNotificationWithFailureSatus()
    {
        $quoteId = 123;
        $rejectionReason = "Invalid PO number";
        $taz= '{"access_token":"123","token_type":"123"}';

        $this->quoteFactoryMock->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('getFailureEmailStatus')->willReturn(1);
        $this->quoteMock->expects($this->any())->method('setFailureEmailStatus')->willReturnSelf();
        $this->quoteMock->expects($this->any())->method('save')->willReturnSelf();

        $this->quoteRepositoryMock->expects($this->any())->method('get')->willReturn($this->quoteInterfaceMock);
        $this->quoteInterfaceMock->expects($this->any())->method('getFailureEmailStatus')->willReturn(1);

        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerId')->willReturn(23);

        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('1111');
        $tazToken= $this->punchoutHelperMock->expects($this->any())->method('getTazToken')->willReturn($taz);

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')
            ->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getIsOrderReject')->willReturn(true);
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerFirstname')->willReturn('neeraj');
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerLastname')->willReturn('gupta');
        $this->quoteInterfaceMock->expects($this->any())->method('getCustomerEmail')->willReturn('neeraj2.gupta@infogain.com');
        $this->quoteInterfaceMock->expects($this->any())->method('getSubtotal')->willReturn(100);
        $this->dataMock->sendOrderFailureNotification($quoteId, $rejectionReason);
    }
    public function testSendOrderFailureNotificationWithoutQuoteId()
    {
        $quoteId = '';
        $rejectionReason = "";
        $this->dataMock->sendOrderFailureNotification($quoteId, $rejectionReason);
    }

    public function testCreateInvoice()
    {
        $exceptedResponse1 = ['error' => '0' , 'message' => 'Manual invoice created successfully'];
        $exceptedResponse2 = ['error' => '1' , 'message' => 'Invoice already created for this order'];
        $exceptedResponse3 = ['error' => '1' , 'message' => 'Invoice already created for this order'];
        $exceptedResponse4 = ['error' => '1' , 'message' => 'Invalid order number'];

        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->orderMock);

        $this->orderMock
            ->expects($this->exactly(3))
            ->method('canInvoice')
			->withConsecutive([],[],[])
            ->willReturnOnConsecutiveCalls(true, false, false);

        $this->orderMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(23.5);
        $this->orderMock->expects($this->any())->method('getGrandTotal')->willReturn(23.5);
        $this->orderMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(23.5);

        $this->invoiceService->expects($this->any())->method('prepareInvoice')->willReturn($this->invoiceMock);
        $this->invoiceMock->expects($this->any())->method('register')->willReturnSelf();
        $this->invoiceMock->expects($this->any())->method('save')->willReturnSelf();
        $this->invoiceMock->expects($this->any())->method('getOrder')->willReturn($this->orderMock);
        $this->transaction->expects($this->any())->method('addObject')->willReturnSelf();
        $this->transaction->expects($this->any())->method('save')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('addStatusHistoryComment')->willReturnSelf();
        $this->orderMock->expects($this->any())->method('setIsCustomerNotified')->willReturnSelf();
        //$this->orderMock->expects($this->any())->method('save');
        $this->assertEquals($exceptedResponse1, $this->dataMock->createInvoice(1068));
        $this->assertEquals($exceptedResponse2, $this->dataMock->createInvoice(1068));
        $this->assertEquals($exceptedResponse3, $this->dataMock->createInvoice(1068));
        $this->assertEquals($exceptedResponse4, $this->dataMock->createInvoice(''));
    }
    public function testCreateInvoiceWithException()
    {

        $phrase = new Phrase(__('Exception message'));
        $exception = new \Exception();

        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->orderMock);

        $this->orderMock->expects($this->any())->method('canInvoice')->willReturn(true);

        $this->invoiceService->expects($this->any())->method('prepareInvoice')->willThrowException($exception);

        $this->dataMock->createInvoice(1068);
    }
    public function testGetAllLocationsByZip()
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75026',
            "zipcode" => 10002
        ];
        $locationsessiondata='{"2935":{"locationId":"ADSL","officeLocationId":"2935"}}';
        $responseData['output']['locations'] = $this->getLocationData();

        $exceptedresponseArray['success'] = 1;
        $exceptedresponseArray['locations'] = $responseData['output']['locations'];
        $responseDatajson = json_encode($responseData);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/locations');
         $this->curlMock->expects($this->any())->method('getBody')->willReturn($responseDatajson);
         $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
         $this->_customerSessionMock->expects($this->any())->method('setAllLocations');
         $this->_customerSessionMock->expects($this->any())->method('getAllLocations')->willReturn($locationsessiondata);
        $this->assertEquals($exceptedresponseArray, $this->dataMock->getAllLocationsByZip($data));
    }

    public function testGetAllLocationsByZipWithToggleOff()
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75026',
            "zipcode" => 10002
        ];
        $responseData['output']['locations'] = $this->getLocationData();

        $exceptedresponseArray['success'] = 1;
        $exceptedresponseArray['locations'] = $responseData['output']['locations'];

        $responseDatajson = json_encode($responseData);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/locations');

         $this->curlMock->expects($this->any())->method('getBody')->willReturn($responseDatajson);
         $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
         $this->_customerSessionMock->expects($this->any())->method('setAllLocations');

        $this->assertEquals($exceptedresponseArray, $this->dataMock->getAllLocationsByZip($data));
    }

    public function testGetAllLocationsByZipWithoutApiUrl()
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75024',
            "zipcode" => 10002
        ];
        $exceptedresponseArray['error'] = 1;
        $exceptedresponseArray['message'] = "Location API URL is missing. Please check configuration setting";

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('');
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals($exceptedresponseArray, $this->dataMock->getAllLocationsByZip($data));
    }

    public function testGetAllLocationsByZipWithoutApiUrlAndTogglesOff()
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75024',
            "zipcode" => 10002
        ];
        $exceptedresponseArray['error'] = 1;
        $exceptedresponseArray['message'] = "Location API URL is missing. Please check configuration setting";

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('');

        $this->assertEquals($exceptedresponseArray, $this->dataMock->getAllLocationsByZip($data));
    }

    public function testGetAllLocationsByZipWithoutResponseCurl()
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75024',
            "zipcode" => 10002
        ];
        $responseData['output']['locations'] = [];

        $exceptedresponseArray['error'] = 1;
        $exceptedresponseArray['message'] = 'unknown error occur.';

        $responseDatajson = json_encode($responseData);
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/locations');

         $this->curlMock->expects($this->any())->method('getBody')->willReturn('');
         $this->_customerSessionMock->expects($this->any())->method('setAllLocations');

        $this->assertNotNull($this->dataMock->getAllLocationsByZip($data));
    }
    public function getLocationData()
    {

        $locationData = [];

        $locationData[0]["Id"] = "0883";
        $locationData[0]["address"]["address1"] = "110 William St";
        $locationData[0]["address"]["address2"] = "";
        $locationData[0]["address"]["city"] = "New York";
        $locationData[0]["address"]["stateOrProvinceCode"] = "NY";
        $locationData[0]["address"]["postalCode"] ="10038";
        $locationData[0]["address"]["countryCode"] = "US";
        $locationData[0]["address"]["addressType"] = "";

        $locationData[0]["name"] = "New York NY William Street";
        $locationData[0]["phone"] = "2127664646";
        $locationData[0]["email"] = "usa0883@fedex.com";
        $locationData[0]["locationType"] = "OFFICE_PRINT";
        $locationData[0]["available"] = true;
        $locationData[0]["availabilityReason"] = "AVAILABLE";
        $locationData[0]["pickupEnabled"] = true;
        $locationData[0]["geoCode"]["latitude"] = "40.708973";
        $locationData[0]["geoCode"]["longitude"] = "-74.00702";
        $locationData[0]["services"][] = "POS Active,Copy and Print,JetLite";
        $locationData[0]["hoursOfOperation"][] = ["date" => "Nov 28, 2021 12:00:00 AM",
                                                "day" => "SUNDAY","schedule" => "Closed"];

        return $locationData;
    }

    public function testGetAddressByLocationId(){
        $locationId = 123;
        $address = 	[
						'address' => [
										'countryCode' => 'US', 'name' => 'Test Name',
										'address1' => 'Test Address1', 'address2' => 'Test Address2',
										'city' => 'Test City', 'stateOrProvinceCode' => 'Test NY',
										'postalCode' => '12222',
									]
					];

		$curlResponse['output']['location'] = $address;
		$curlResponse['output']['name'] = 'Test Name';
		$curlResponse['output']['phone'] = '34567890';
		$curlResponse['output']['location']['services'] = [];
		$curlResponse['output']['location']['hoursOfOperation'] = [];
		$curlResponse['successful'] = '1';
		$responseDatajson = json_encode($curlResponse);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/locations');
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('1111');

        $this->curlMock->expects($this->any())->method('getBody')->willReturn($responseDatajson);
        $this->assertIsArray($this->dataMock->getAddressByLocationId($locationId));
    }

    public function testGetAddressWithBlankLocationId(){
        $locationId = null;
        $responseArray['error'] = 1;
        $responseArray['message'] = "Invalid Location Id";
        $this->assertEquals($responseArray, $this->dataMock->getAddressByLocationId($locationId));
    }

    public function testGetAddressWithMissingLocationUrl(){
        $locationId = 123;
        $responseArray['error'] = 1;
        $responseArray['message'] = "Location API URL is missing. Please check configuration setting";

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('');

        $this->assertEquals($responseArray, $this->dataMock->getAddressByLocationId($locationId));
    }

    public function testGetAddressWithCurlError(){
        $locationId = 123;
        $responseArray['error'] = 1;
        $responseArray['message'] = "unknown error occur.";

        $address = 	[
						'address' => [
										'countryCode' => 'US', 'name' => 'Test Name',
										'address1' => 'Test Address1', 'address2' => 'Test Address2',
										'city' => 'Test City', 'stateOrProvinceCode' => 'Test NY',
										'postalCode' => '12222',
									]
					];

		$curlResponse['output']['location'] = $address;
		$curlResponse['output']['name'] = 'Test Name';
		$curlResponse['output']['phone'] = '34567890';
		$curlResponse['successful'] = '0';
		$responseDatajson = json_encode($curlResponse);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/locations');
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('1111');

        $this->curlMock->expects($this->any())->method('getBody')->willReturn($responseDatajson);
        $this->assertEquals($responseArray, $this->dataMock->getAddressByLocationId($locationId));
    }

    public function testGetAddressWithCurlException(){
        $locationId = 123;
        $address = 	[
					'address' => [
									'countryCode' => 'US', 'name' => 'Test Name',
									'address1' => 'Test Address1', 'address2' => 'Test Address2',
									'city' => 'Test City', 'stateOrProvinceCode' => 'Test NY',
									'postalCode' => '12222',
								]
					];

		$curlResponse['output']['location'] = $address;
		$curlResponse['output']['name'] = 'Test Name';
		$curlResponse['output']['phone'] = '34567890';
		$curlResponse['successful'] = '0';
		$responseDatajson = json_encode($curlResponse);

        $exception = new \Exception();
        $response = $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(
                'fedex/general/all_location_api_url',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/locations');

        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willThrowException($exception);
        $response = $this->dataMock->getAddressByLocationId($locationId);
        $this->assertIsArray($response);
    }

    public function testFormatAddress(){
        $locationId = 123;
        $address = 	[
						'address' => [
										'countryCode' => 'US', 'name' => 'Test Name',
										'address1' => 'Test Address1', 'address2' => 'Test Address2',
										'city' => 'Test City', 'stateOrProvinceCode' => 'Test NY',
										'postalCode' => '12222',
									],
						'name' => 'Test Name',
						'phone' => '34567890'
					];

        $this->regionMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->regionMock->expects($this->any())->method('getName')->willReturn('Test NY');

        $this->countryMock->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->countryMock->expects($this->any())->method('getName')->willReturn('US');

        $response = $this->dataMock->formatAddress($address);
        $this->assertIsString($response);
    }
    
    public function testGetAllLocationUrl()
    {
        // Test Case 1: When $isRestrictedRecommendedToggle is true
        $isRestrictedRecommendedToggle = true;
        $this->assertNull($this->dataMock->getAllLocationUrl($isRestrictedRecommendedToggle));

        // Test Case 2: When $isRestrictedRecommendedToggle is false
        $this->assertNull($this->dataMock->getAllLocationUrl());
    }
    public function testValidateStoreNumbers()
    {
        // Test case 1: Valid store numbers
        $storeNumbers1 = ['123', '567', '901'];
        $expectedResult1 = ['0123', '0567', '0901'];
        $this->assertEquals($expectedResult1, $this->dataMock->validateStoreNumbers($storeNumbers1));

        // Test case 2: Valid store numbers with 3 digits
        $storeNumbers2 = ['123', '456', '789'];
        $expectedResult2 = ['0123', '0456', '0789'];
        $this->assertEquals($expectedResult2, $this->dataMock->validateStoreNumbers($storeNumbers2));

        // Test case 3: Store numbers with invalid format
        $storeNumbers3 = ['123', 'ABC', '7890'];
        $expectedResult3 = [];
        $this->assertEquals($expectedResult3, $this->dataMock->validateStoreNumbers($storeNumbers3));

        // Test case 4: Empty input array
        $storeNumbers4 = [];
        $expectedResult4 = [];
        $this->assertEquals($expectedResult4, $this->dataMock->validateStoreNumbers($storeNumbers4));

        // Test case 5: Null input
        $storeNumbers5 = null;
        $expectedResult5 = [];
        $this->assertEquals($expectedResult5, $this->dataMock->validateStoreNumbers($storeNumbers5));
    }
    public function testGetHeaders()
    {
        $mockHeaderData = $this->getMockBuilder(HeaderData::class)
                               ->disableOriginalConstructor()
                               ->getMock();
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn('TestToken');
        $mockHeaderData->expects($this->any())
                       ->method('getAuthHeaderValue')
                       ->willReturn('TestToken');
        $reflection = new ReflectionClass(Data::class);
        $property = $reflection->getProperty('isRestrictedRecommendedToggle');
        $property->setAccessible(true);
        
        // Set isRestrictedRecommendedToggle is ON
        $property->setValue($this->dataMock, true);
        $expectedHeaders = [
            "Content-Type: application/json",
            "Accept-Language: json",
            "TestToken",
            'Cookie: Bearer='
        ];
        $this->assertEquals($expectedHeaders, $this->dataMock->getHeaders());

        // Test case - isRestrictedRecommendedToggle is Off
        $property->setValue($this->dataMock, false);
        $expectedHeaders = [
            "Content-Type: application/json",
            "Accept-Language: json",
            "TestToken"
        ];
        $this->assertEquals($expectedHeaders, $this->dataMock->getHeaders());
    }
    public function testGetPostFields()
    {
        $reflection = new ReflectionClass(Data::class);
        $property = $reflection->getProperty('isRestrictedRecommendedToggle');
        $property->setAccessible(true);
        
        // Set isRestrictedRecommendedToggle to true
        $property->setValue($this->dataMock, true);

        // Test when isRestrictedRecommendedToggle is true and $isStoreSearchEnabled is true
        $isStoreSearchEnabled = true;
        $city = 'New York';
        $radius = '10';
        $zipCodeOrStoreCode = '10001';
        $expectedJsonData = [
            'locationSearchRequest' => [
                'officeLocationId' => $zipCodeOrStoreCode,
                'address' => [
                    'countryCode' => self::COUNTRYCODE,
                    'addressClassification' => self::ADDRESSCLASSIFICATION
                ],
                'searchRadius' => [
                    'value' => $radius,
                    'unit' => self::UNIT
                ]
            ]
        ];
        $this->assertEquals($expectedJsonData, $this->dataMock->getPostFields($isStoreSearchEnabled, null, $city, $radius, $zipCodeOrStoreCode));

        // Test when isRestrictedRecommendedToggle is true and $isStoreSearchEnabled is false
        $isStoreSearchEnabled = false;
        $expectedJsonData = [
            'locationSearchRequest' => [
                'address' => [
                    'city' => $city,
                    'stateOrProvinceCode' => null,
                    'postalCode' => $zipCodeOrStoreCode,
                    'countryCode' => self::COUNTRYCODE,
                    'addressClassification' => self::ADDRESSCLASSIFICATION
                ],
                'searchRadius' => [
                    'value' => $radius,
                    'unit' => self::UNIT
                ],
                'service' => [
                    'matchType' => self::MATCH_TYPE,
                    'serviceTypes' => null
                ],
                'include' => [
                    'printHubOnly' => false
                ],
                'resultOptions' => [
                    'resultCount' => 0,
                    'sort' => self::SORT_BY
                ]
            ]
        ];
        $this->assertEquals($expectedJsonData, $this->dataMock->getPostFields($isStoreSearchEnabled, null, $city, $radius, $zipCodeOrStoreCode));

        // Reset isRestrictedRecommendedToggle to false
        $property->setValue($this->dataMock, false);

        // Test when isRestrictedRecommendedToggle is false
        $expectedJsonData = [
            'input' => [
                'locationsFilters' => [
                    [
                        'address' => [
                            'countryCode' => self::COUNTRYCODE,
                            'postalCode' => $zipCodeOrStoreCode,
                            'stateOrProvinceCode' => null,
                            'city' => $city,
                        ],
                        'radius' => [
                            'unit' => 'mile',
                            'value' => $radius,
                        ],
                        'type' => self::TYPE,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedJsonData, $this->dataMock->getPostFields($isStoreSearchEnabled, null, $city, $radius, $zipCodeOrStoreCode));
    }
    public function testRetrieveCurlResponse()
    {
        $curlResponse = [
            'output' => [
                'search' => [
                    ['officeLocationId' => 1,],
                    ['officeLocationId' => 2,]
                ],
                'locations' => [
                    ['Id' => 1,],
                    ['Id' => 2,]
                ]
            ]
        ];

        $response1 = $this->dataMock->retrieveCurlResponse($curlResponse, true, '75048');
        $response2 = $this->dataMock->retrieveCurlResponse($curlResponse, false, '75048');

        // Test Assertions
        $this->assertArrayHasKey('success', $response1);
        $this->assertArrayHasKey('locations', $response1);
        $this->assertEquals(1, $response1['success']);
        $this->assertNotNull($response1['locations']);

        $this->assertArrayHasKey('success', $response2);
        $this->assertArrayHasKey('locations', $response2);
        $this->assertEquals(1, $response2['success']);
        $this->assertEquals($curlResponse['output']['locations'], $response2['locations']);
    }

    /**
     * Test getHcToggle when customer is commercial and company exists
     */
    public function testGetHcToggleForCommercialCustomer(): void
    {
        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->_customerSessionMock->expects($this->any())->method('getCustomer')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->once())->method('get')->with(1)->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->once())->method('getHcToggle')->willReturn(true);
        $result = $this->dataMock->getHcToggle(true);
        $this->assertTrue($result);
    }

    /**
     * Test getHcToggle when customer is not commercial
     */
    public function testGetHcToggleForNonCommercialCustomer(): void
    {
        $result = $this->dataMock->getHcToggle(false);
        $this->assertTrue($result);
    }

    /**
     * Test getHcToggle when customer session returns null customer
     */
    public function testGetHcToggleWhenCustomerIsNull(): void
    {
        $this->_customerSessionMock->expects($this->once())
            ->method('getCustomer')
            ->willReturn(null);

        $result = $this->dataMock->getHcToggle(true);
        $this->assertTrue($result); // Should return true when customer is null
    }

    /**
     * Test getHcToggle when exception occurs during company repository get
     */
    public function testGetHcToggleWithException(): void
    {
        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->_customerSessionMock->expects($this->any())->method('getCustomer')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->once())
            ->method('get')
            ->with(1)
            ->willThrowException(new \Exception('Company repository error'));
        $result = $this->dataMock->getHcToggle(true);
        $this->assertTrue($result); // Should return true on exception
    }

    /**
     * Test filterPremiumLocations - keeps premium locations when includePremium is true
     */
    public function testFilterPremiumLocationsWithIncludePremiumTrue(): void
    {
        $locations = [
            ['locationFormat' => 'HOTEL_CONVENTION', 'name' => 'Location 1'],
            ['locationFormat' => 'OFFICE_PRINT', 'name' => 'Location 2'],
            ['locationFormate' => 'hotel_convention', 'name' => 'Location 3']
        ];

        $reflection = new ReflectionClass(Data::class);
        $method = $reflection->getMethod('filterPremiumLocations');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->dataMock, [$locations, true]);
        $this->assertCount(3, $result);
    }

    /**
     * Test filterPremiumLocations - filters out premium locations when includePremium is false
     */
    public function testFilterPremiumLocationsWithIncludePremiumFalse(): void
    {
        $locations = [
            ['locationFormat' => 'HOTEL_CONVENTION', 'name' => 'Location 1'],
            ['locationFormat' => 'OFFICE_PRINT', 'name' => 'Location 2'],
            ['locationFormate' => 'OFFICE_PRINT', 'name' => 'Location 3']
        ];

        $reflection = new ReflectionClass(Data::class);
        $method = $reflection->getMethod('filterPremiumLocations');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->dataMock, [$locations, false]);
        $this->assertCount(2, $result);
        $this->assertEquals('Location 2', $result[0]['name']);
        $this->assertEquals('Location 3', $result[1]['name']);
    }

    /**
     * Test filterPremiumLocations with empty locations
     */
    public function testFilterPremiumLocationsWithEmptyArray(): void
    {
        $reflection = new ReflectionClass(Data::class);
        $method = $reflection->getMethod('filterPremiumLocations');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->dataMock, [[], false]);
        $this->assertEmpty($result);
    }

    /**
     * Test filterPremiumLocations with null locationFormat - verifies null formats are kept
     */
    public function testFilterPremiumLocationsWithNullLocationFormat(): void
    {
        $locations = [
            ['locationFormat' => null, 'name' => 'Location with null format'],
            ['locationFormat' => 'HOTEL_CONVENTION', 'name' => 'Premium Location'],
            ['locationFormat' => 'OFFICE_PRINT', 'name' => 'Standard Location']
        ];

        $reflection = new ReflectionClass(Data::class);
        $method = $reflection->getMethod('filterPremiumLocations');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->dataMock, [$locations, false]);

        $this->assertCount(2, $result);
        $this->assertEquals('Location with null format', $result[0]['name']);
        $this->assertEquals('Standard Location', $result[1]['name']);
    }

    /**
     * Test getAllLocationsByZip with premium location filtering - toggle OFF scenario
     */
    public function testGetAllLocationsByZipWithPremiumFilteringToggleOff(): void
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75026',
            "zipcode" => 10002
        ];

        $responseData['output']['locations'] = [
            [
                'Id' => '0001',
                'locationFormat' => 'HOTEL_CONVENTION',
                'name' => 'Premium Location',
                'address' => [
                    'city' => 'Test City',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75026'
                ]
            ],
            [
                'Id' => '0002',
                'locationFormat' => 'OFFICE_PRINT',
                'name' => 'Standard Location',
                'address' => [
                    'city' => 'Test City',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75026'
                ]
            ]
        ];

        $responseDatajson = json_encode($responseData);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/search');

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willReturn($responseDatajson);

        // Toggle is OFF - no filtering should happen
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(function($key) {
                if ($key === 'explorers_restricted_and_recommended_production') {
                    return false;
                }
                if ($key === 'tech_titans_d_217639') {
                    return false; // Toggle OFF
                }
                return false;
            });

        $this->_customerSessionMock->expects($this->any())
            ->method('setAllLocations');

        $result = $this->dataMock->getAllLocationsByZip($data, false);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('locations', $result);
        $this->assertCount(2, $result['locations']);
    }

    /**
     * Test getAllLocationsByZip with premium location filtering for commercial customer
     */
    public function testGetAllLocationsByZipWithPremiumFilteringForCommercialCustomer(): void
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75026',
            "zipcode" => 10002
        ];

        $responseData['output']['locations'] = [
            [
                'Id' => '0001',
                'locationFormat' => 'HOTEL_CONVENTION',
                'name' => 'Premium Location',
                'address' => [
                    'city' => 'Test City',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75026'
                ]
            ],
            [
                'Id' => '0002',
                'locationFormat' => 'OFFICE_PRINT',
                'name' => 'Standard Location',
                'address' => [
                    'city' => 'Test City',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75026'
                ]
            ]
        ];

        $responseDatajson = json_encode($responseData);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/search');

        $this->curlMock->expects($this->any())
            ->method('getBody')
            ->willReturn($responseDatajson);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(function($key) {
                if ($key === 'explorers_restricted_and_recommended_production') {
                    return false;
                }
                if ($key === 'tech_titans_d_217639') {
                    return true;
                }
                return false;
            });

        $deliveryHelperMock = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $deliveryHelperMock->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $reflection = new ReflectionClass($this->dataMock);
        $property = $reflection->getProperty('deliveryHelper');
        $property->setAccessible(true);
        $property->setValue($this->dataMock, $deliveryHelperMock);

        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->_customerSessionMock->expects($this->any())->method('getCustomer')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->once())->method('get')->with(1)->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->once())->method('getHcToggle')->willReturn(false);
        $this->_customerSessionMock->expects($this->any())
            ->method('setAllLocations');

        $result = $this->dataMock->getAllLocationsByZip($data, false);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('locations', $result);
        $this->assertCount(1, $result['locations']); // Only standard location should remain
        $this->assertEquals('Standard Location', $result['locations'][0]['name']);
    }

    /**
     * Test getAllLocationsByZip with restricted toggle ON and premium filtering
     */
    public function testGetAllLocationsByZipWithRestrictedToggleAndPremiumFiltering(): void
    {
        $data = [
            "radius" => 10,
            "city" => 'Plano',
            "stateCode" => '75026',
            "zipcode" => 10002
        ];

        $responseData = [
            [
                'officeLocationId' => '0001',
                'locationFormat' => 'HOTEL_CONVENTION',
                'name' => 'Premium Location',
                'address' => [
                    'city' => 'Test City',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75026'
                ]
            ],
            [
                'officeLocationId' => '0002',
                'locationFormat' => 'OFFICE_PRINT',
                'name' => 'Standard Location',
                'address' => [
                    'city' => 'Test City',
                    'stateOrProvinceCode' => 'TX',
                    'postalCode' => '75026'
                ]
            ]
        ];

        $curlResponse['output']['search'] = $responseData;
        $responseDatajson = json_encode($curlResponse);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturn('https://apitest.fedex.com/location/fedexoffice/v1/search');

        $this->curlMock->expects($this->any()) ->method('getBody')->willReturn($responseDatajson);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(function($key) {
                if ($key === 'explorers_restricted_and_recommended_production') {
                    return true;
                }
                if ($key === 'tech_titans_d_217639') {
                    return true;
                }
                return false;
            });

        $deliveryHelperMock = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $deliveryHelperMock->expects($this->once())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $reflection = new ReflectionClass($this->dataMock);
        $property = $reflection->getProperty('deliveryHelper');
        $property->setAccessible(true);
        $property->setValue($this->dataMock, $deliveryHelperMock);

        $this->_customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn(0);
        $this->_customerSessionMock->expects($this->any())->method('getCustomer')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')->willReturn($this->customerExtensionInterface);
        $this->customerExtensionInterface->expects($this->any())->method('getCompanyAttributes')->willReturn($this->companyCustomerInterface);
        $this->companyCustomerInterface->expects($this->any())->method('getCompanyId')->willReturn(1);
        $this->companyRepositoryMock->expects($this->once())->method('get')->with(1)->willReturn($this->companyInterface);

        $this->companyInterface->expects($this->once())->method('getHcToggle')->willReturn(false);
        $this->_customerSessionMock->expects($this->any())->method('setAllLocations');
        $result = $this->dataMock->getAllLocationsByZip($data, false);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Standard Location', $result[0]['name']);
    }
}
