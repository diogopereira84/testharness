<?php

namespace Fedex\Purchaseorder\Test\Unit\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Purchaseorder\Model\Po;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Company\Model\Config\Source\CredtiCardOptions;
use Fedex\Company\Model\Config\Source\FedExAccountOptions;
use Fedex\Company\Model\Config\Source\PaymentAcceptance;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Purchaseorder\Api\PoInterface;
use Fedex\Purchaseorder\Helper\Data as Pohelper;
use Fedex\ReorderInstance\Helper\ReorderInstanceHelper;
use Fedex\Shipto\Helper\Data as Shiptohelper;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\Email\Helper\Data as Emailhelper;
use Magento\Sales\Model\Order;

class PoTest extends TestCase
{
    protected $storeManager;
    protected $customerRepositoryInterface;
    protected $configInterface;
    protected $cartRepositoryInterface;
    protected $order;
    protected $orderCollection;
    protected $helper;
    protected $companyRepository;
    protected $regionFactory;
    protected $poHelper;
    protected $region;
    protected $logger;
    /**
     * @var (\Fedex\Email\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $rejectHelper;
    protected $shiptoHelper;
    protected $negotiableQuoteRepository;
    protected $negotiableQuoteInterface;
    protected $cartInterface;
    protected $companyMgmtInterface;
    protected $companyInterface;
    protected $orderInterface;
    protected $storeMock;
    /**
     * @var \PHPUnit\Framework\MockObject\Builder\InvocationMocker
     */
    protected $baseUrl;
    /**
     * @var (\Magento\Directory\Model\ResourceModel\Region\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionFactory;
    protected $quoteShippingAddress;
    protected $quote;
    protected $quoteItem;
    protected $Customermock;
    protected $_optionInterface;
    protected $companyHelper;
    protected $dataHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var ToggleConfig
     */
    protected $toggleConfigMock;
    /**
     * @var Po
     */
    protected $helperData;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartRepositoryInterface = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['getShippingAddress'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'loadByIncrementId', 'load', 'getId', 'getExtOrderId', 'setExtOrderId', 'save', 'getEntityId'])
            ->getMock();

        $this->orderCollection = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem', 'count'])
            ->getMock();

        /* B-1299551 toggle clean up start end */

        $this->helper = $this->getMockBuilder(PunchoutHelper::class)
            ->setMethods(['getTazToken', 'getAuthGatewayToken', 'removeSpaceFromNameToggle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['getPaymentOption', 'getFedexAccountNumber'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->regionFactory = $this->getMockBuilder(\Magento\Directory\Model\RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->poHelper = $this->getMockBuilder(Pohelper::class)
            ->setMethods(['sendError', 'getActionType', 'getPoNumber', 'changeQuoteStatusatdelete', 'sendSuccess', 'getRegionCode', 'getLocationID', 'adjustQuote', 'updateSnapshotForQuote'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->region = $this->getMockBuilder(\Magento\Directory\Model\Region::class)
            ->setMethods(['loadByCode', 'loadByName', 'getId', 'load', 'getCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->rejectHelper = $this->getMockBuilder(Emailhelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shiptoHelper = $this->getMockBuilder(\Fedex\Shipto\Helper\Data::class)
            ->setMethods(['sendOrderFailureNotification'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->negotiableQuoteRepository = $this->getMockBuilder(\Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->negotiableQuoteInterface = $this->getMockBuilder(NegotiableQuoteInterface::class)
            ->setMethods(['getQuoteId', 'getStatus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartInterface = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->setMethods(['getQuoteId', 'getReservedOrderId', 'getId', 'getShippingMethod', 'getItemsCollection', 'getCustomerId', 'getShippingAddress', 'getEstimatedPickupTime'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyMgmtInterface = $this->getMockBuilder(\Magento\Company\Api\CompanyManagementInterface::class)
            ->setMethods(['getByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyMgmtInterface = $this->getMockBuilder(\Magento\Company\Api\CompanyManagementInterface::class)
            ->setMethods(['getByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->setMethods(['getId', 'getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderInterface = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->setMethods(['getExtOrderId', 'setExtOrderId', 'save', 'getEntityId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->setMethods(['getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->baseUrl = $this->storeMock->expects($this->any())
            ->method('getBaseUrl')->willReturn('string');

        $this->collectionFactory = $this->getMockBuilder(\Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteShippingAddress = $this->getMockBuilder(Address::class)
            ->setMethods(['getShippingMethod'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getShippingAddress', 'getFedexShipAccountNumber', 'getEstimatedPickupTime'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->Customermock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['getId', 'getFirstname', 'getLastName', 'getEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_optionInterface = $this->getMockBuilder(OptionInterface::class)
            ->setMethods(['getValue', 'getProductId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyHelper = $this->getMockBuilder(\Fedex\Company\Helper\Data::class)
            ->setMethods(['getSiteName', 'getCompanyPaymentMethod', 'getFedexAccountNumber', 'getPaymentOption', 'getLegacyPaymentType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->setMethods(['getCompanySite'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->helperData = $this->objectManager->getObject(
            Po::class,
            [
                'storeManager' => $this->storeManager,
                'customerRepositoryInterface' => $this->customerRepositoryInterface,
                'configInterface' => $this->configInterface,
                'cartRepositoryInterface' => $this->cartRepositoryInterface,
                'order' => $this->order,
                'helper' => $this->helper,
                'companyRepository' => $this->companyRepository,
                'regionFactory' => $this->regionFactory,
                'poHelper' => $this->poHelper,
                'region' => $this->region,
                'logger' => $this->logger,
                'rejectHelper' => $this->rejectHelper,
                'shiotoHelper' => $this->shiptoHelper,
                'negotiableQuoteRepository' => $this->negotiableQuoteRepository,
                'companyMgmtRepository' => $this->companyMgmtInterface,
                'toggleConfig' => $this->toggleConfigMock,
            ]
        );
    }

    /**
     * getPoXmlData
     * @return array
     */
    public function getPoXmlData()
    {
        return [
            'Request' => [
                'OrderRequest' => [
                    'OrderRequestHeaders' => [
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * getPoXmlDatawithShipping
     * @return array
     */
    public function getPoXmlDatawithShipping()
    {
        return [
            'Request' => [
                'OrderRequest' => [
                    'ShipTo' => [
                        'Address' => [
                            'shipping_method' => 'fedexshipping_PICKUP',
                            'street' => "123 Anystreet",
                            'city' => 'Sunnyvale',
                            'State' => 'CA',
                            'postcode' => '90489',
                            'country_id' => '5',
                            'region_id' => '123',
                            'company' => 'Sunnyvale',
                            'firstname' => 'CA',
                            'lastname' => '90489',
                            'email' => '5',
                            'telephone' => '5123123123',
                            'Name' => 'Acme',
                            'PostalAddress' => [
                                '@attributes' => [
                                    'name' => 'default',
                                ],
                            ],
                        ],
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteId')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('created');
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn([$this->order]);
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('delete');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testGetPoxmlwithException()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willThrowException($exception);
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('delete');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testoneGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteID')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('ordered');
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("10202029");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('new');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);

        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testtwoGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteID')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('ordered');
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn(null);
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn(null);
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('new');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testthreeGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteId')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('closed');
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('delete');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testfourGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteId')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('expired');
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('delete');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testfiveGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteId')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn(null);
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('delete');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testsixGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteId')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('submitted');
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn(null);
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for getPoxml method.
     *
     * @return string
     */
    public function testsevenGetPoxml()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->negotiableQuoteRepository->expects($this->any())->method('getById')->willReturn($this->negotiableQuoteInterface);
        $this->negotiableQuoteInterface->expects($this->any())->method('getQuoteId')->willReturn(2);
        $this->negotiableQuoteInterface->expects($this->any())->method('getStatus')->willReturn('submitted');
        $this->poHelper->expects($this->any())->method('changeQuoteStatusatdelete')->willReturn(true);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("Error");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('updateSnapshotForQuote')->willReturn('');
        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");
        $this->cartInterface->expects($this->any())->method('getId')->willReturn(4);
        $poxml1 = $this->getPoXmlDatawithShipping();
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
            ],
        ];
        $this->poHelper->expects($this->any())->method('getActionType')->willReturn('string');
        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->helperData->GetPoxml($companyDetails, $poxml1);
    }

    /**
     * Test Case for convertQuoteToOrder method.
     *
     * @return string
     */
    public function testConvertQuoteToOrder()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poNumber = 'PO12313';
        $companyDetails = ['legacy_site_name' => 'Madmax'];
        $product = ['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]];
        $association = ['name' => 'Association'];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $billingAddress = [
            'billing_method' => 'fedexshipping_PICKUP',
            'billing_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $pickUpIdLocation = 'fedexPickup';

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->orderInterface->expects($this->any())->method('getEntityId')->willReturn("1212");
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('getLocationID')->willReturn("1212");
        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);

        $this->helper->expects($this->any())->method('getTazToken')->willReturn(json_encode(['access_token' => 'token', 'token_type' => 'type']));
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn('2342423');
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('adjustQuote')->willReturn(['error' => 1, 'msg' => 'Unable to submit order: internal token error', 'order_id' => '']);
        $this->helperData->ConvertQuoteToOrder($this->cartInterface, $poNumber, $companyDetails, $product, $association, $shippingAddress, $billingAddress, $availableOption, $pickUpIdLocation);
    }

    /**
     * Test Case for convertQuoteToOrder method.
     *
     * @return string
     */
    public function testConvertQuoteToOrderwithexception()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poNumber = 'PO12313';
        $companyDetails = ['legacy_site_name' => 'Madmax'];
        $product = ['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]];
        $association = ['name' => 'Association'];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $billingAddress = [
            'billing_method' => 'fedexshipping_PICKUP',
            'billing_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $pickUpIdLocation = 'fedexPickup';

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->orderInterface->expects($this->any())->method('getEntityId')->willReturn("1212");
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('getLocationID')->willReturn("1212");
        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);

        $this->helper->expects($this->any())->method('getTazToken')->willReturn(json_encode(['access_token' => '', 'token_type' => '']));
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn('');
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('adjustQuote')->willReturn(['error' => 1, 'msg' => 'Unable to submit order: internal token error', 'order_id' => '']);
        $this->helperData->ConvertQuoteToOrder($this->cartInterface, $poNumber, $companyDetails, $product, $association, $shippingAddress, $billingAddress, $availableOption, $pickUpIdLocation);
    }

    /**
     * Test Case for convertQuoteToOrder method.
     *
     * @return string
     */
    public function testsConvertQuoteToOrder()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poNumber = 'PO12313';
        $companyDetails = ['legacy_site_name' => 'Madmax'];
        $product = ['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]];
        $association = ['name' => 'Association'];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $billingAddress = [
            'billing_method' => 'fedexshipping_PICKUP',
            'billing_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $pickUpIdLocation = 'fedexPickup';

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn(null);
        $this->orderInterface->expects($this->any())->method('getEntityId')->willReturn("1212");
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('getLocationID')->willReturn("1212");
        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);
        $this->helper->expects($this->any())->method('getTazToken')->willReturn(null);
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn(null);
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('adjustQuote')->willReturn(['error' => 1, 'msg' => 'Unable to submit order: internal token error', 'order_id' => '']);
        $this->helperData->ConvertQuoteToOrder($this->cartInterface, $poNumber, $companyDetails, $product, $association, $shippingAddress, $billingAddress, $availableOption, $pickUpIdLocation);
    }

    /**
     * Test Case for constructOrderSubmissionAPIRequest method.
     *
     * @return string
     */
    public function testConstructOrderSubmissionAPIRequest()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'region_code' => 'TX',
            'countryCode' => 'US',
            'companyName' => 'USA',
            'phoneNumberExt' => '91',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $poNumber = 'PO23232';
        $contactDetails = ['fname' => 'john', 'lname' => 'dmitri', 'email' => 'john@msn.com', 'contact_number' => '2131231', 'contact_ext' => '23123123'];
        $companyId = 4;
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $quoteId = 5;
        $pickUpIdLocation = 'fedexpickup';
        $jsonData = json_encode(['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]]);
        $productAssociations = [];
        $magento_orderId = '31343414';
        $this->quote->expects($this->any())->method('getFedexShipAccountNumber')->willReturn('24231314');
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);

        $this->companyHelper->method('getSiteName')->withConsecutive([],[])->willReturnOnConsecutiveCalls(null,$this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())->method('getPaymentOption')->willReturn('fedexaccountnumber');
        $this->helperData->ConstructOrderSubmissionAPIRequest($this->quote, $dbItemDetails, $shippingAddress, $poNumber, $contactDetails, $companyId, $availableOption, $quoteId, $pickUpIdLocation, $jsonData, $productAssociations, $magento_orderId);
    }

    /**
     * Test Case for constructOrderSubmissionAPIRequest method.
     *
     * @return string
     */
    public function testConstructOrderSubmissionAPIRequestwithFedexAccountNumber()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'region_code' => 'TX',
            'countryCode' => 'US',
            'companyName' => 'USA',
            'phoneNumberExt' => '91',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $poNumber = 'PO23232';
        $contactDetails = ['fname' => 'john', 'lname' => 'dmitri', 'email' => 'john@msn.com', 'contact_number' => '2131231', 'contact_ext' => '23123123'];
        $companyId = 4;
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $quoteId = 5;
        $pickUpIdLocation = 'fedexpickup';
        $jsonData = json_encode(['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]]);
        $productAssociations = [];
        $magento_orderId = '31343414';
        $this->quote->expects($this->any())->method('getFedexShipAccountNumber')->willReturn('24231314');
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);

        $this->companyHelper->method('getSiteName')->withConsecutive([],[])->willReturnOnConsecutiveCalls(null,$this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')->willReturn('accountnumbers');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('accountnumbers');
        $this->companyHelper->expects($this->any())->method('getPaymentOption')->willReturn('accountnumbers');
        $this->helperData->ConstructOrderSubmissionAPIRequest($this->quote, $dbItemDetails, $shippingAddress, $poNumber, $contactDetails, $companyId, $availableOption, $quoteId, $pickUpIdLocation, $jsonData, $productAssociations, $magento_orderId);
    }

    /**
     * Test Case for constructOrderSubmissionAPIRequest method.
     *
     * @return string
     */
    public function testConstructOrderSubmissionAPIRequestwithPickupIdLocationZero()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'region_code' => 'TX',
            'countryCode' => 'US',
            'companyName' => 'USA',
            'phoneNumberExt' => '91',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $poNumber = 'PO23232';
        $contactDetails = ['fname' => 'john', 'lname' => 'dmitri', 'email' => 'john@msn.com', 'contact_number' => '2131231', 'contact_ext' => '23123123'];
        $companyId = 4;
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $quoteId = 5;
        $pickUpIdLocation = 0;
        $jsonData = json_encode(['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]]);
        $productAssociations = [];
        $magento_orderId = '31343414';
        $this->quote->expects($this->any())->method('getFedexShipAccountNumber')->willReturn('24231314');
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);

        $this->companyHelper->method('getSiteName')->withConsecutive([],[])->willReturnOnConsecutiveCalls(null,$this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')->willReturn('accountnumbers');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('accountnumbers');
        $this->companyHelper->expects($this->any())->method('getPaymentOption')->willReturn('accountnumbers');
        $this->helperData->ConstructOrderSubmissionAPIRequest($this->quote, $dbItemDetails, $shippingAddress, $poNumber, $contactDetails, $companyId, $availableOption, $quoteId, $pickUpIdLocation, $jsonData, $productAssociations, $magento_orderId);
    }

    /**
     * Test Case for Special Chars from PO
     *
     * @return void
     */
    public function testRemoveSpecialChars()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->helperData->removeSpecialChars('poNumber');
    }

    /**
     * Test Case for constructOrderSubmissionAPIRequest method.
     *
     * @return string
     */
    public function testedConstructOrderSubmissionAPIRequest()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'region_code' => 'TX',
            'countryCode' => 'US',
            'companyName' => 'USA',
            'phoneNumberExt' => '91',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $poNumber = 'PO23232';
        $contactDetails = ['fname' => 'john', 'lname' => 'dmitri', 'email' => 'john@msn.com', 'contact_number' => '2131231', 'contact_ext' => '23123123'];
        $companyId = 4;
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $quoteId = 5;
        $pickUpIdLocation = 'fedexpickup';
        $jsonData = json_encode(['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]]);
        $productAssociations = [];
        $magento_orderId = '31343414';
        $this->quote->expects($this->any())->method('getFedexShipAccountNumber')->willReturn('24231314');
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);
        $this->companyHelper->expects($this->any())->method('getSiteName')->willReturn($this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')->willReturn('legacyaccountnumber');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('legacyaccountnumber');
        $this->companyHelper->expects($this->any())->method('getPaymentOption')->willReturn('legacyaccountnumber');
        $this->helperData->ConstructOrderSubmissionAPIRequest($this->quote, $dbItemDetails, $shippingAddress, $poNumber, $contactDetails, $companyId, $availableOption, $quoteId, $pickUpIdLocation, $jsonData, $productAssociations, $magento_orderId);
    }

    /**
     * Test Case for constructOrderSubmissionAPIRequest method.
     *
     * @return string
     */
    public function testerConstructOrderSubmissionAPIRequest()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'region_code' => 'TX',
            'countryCode' => 'US',
            'companyName' => 'USA',
            'phoneNumberExt' => '91',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'pickupLocationId' => 1,
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $poNumber = 'PO23232';
        $contactDetails = ['fname' => 'john', 'lname' => 'dmitri', 'email' => 'john@msn.com', 'contact_number' => '2131231', 'contact_ext' => '23123123'];
        $companyId = 4;
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $quoteId = 5;
        $pickUpIdLocation = 'fedexpickup';
        $jsonData = json_encode(['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]]);
        $productAssociations = [];
        $magento_orderId = '31343414';
        $this->quote->expects($this->any())->method('getFedexShipAccountNumber')->willReturn('24231314');
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);
        $this->companyHelper->expects($this->any())->method('getSiteName')->willReturn($this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')->willReturn('sitecreditcard');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('sitecreditcard');
        $this->companyHelper->expects($this->any())->method('getPaymentOption')->willReturn('sitecreditcard');
        $this->helperData->ConstructOrderSubmissionAPIRequest($this->quote, $dbItemDetails, $shippingAddress, $poNumber, $contactDetails, $companyId, $availableOption, $quoteId, $pickUpIdLocation, $jsonData, $productAssociations, $magento_orderId);
    }

    /**
     * Test Case for constructOrderSubmissionAPIRequest method.
     *
     * @return string
     */
    public function testthreeConstructOrderSubmissionAPIRequest()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'region_code' => 'TX',
            'countryCode' => 'US',
            'companyName' => 'USA',
            'phoneNumberExt' => '91',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'pickupLocationId' => 1,
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $poNumber = 'PO23232';
        $contactDetails = ['fname' => 'john', 'lname' => 'dmitri', 'email' => 'john@msn.com', 'contact_number' => '2131231', 'contact_ext' => '23123123'];
        $companyId = 4;
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $quoteId = 5;
        $pickUpIdLocation = 1;
        $jsonData = json_encode(['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]]);
        $productAssociations = [];
        $magento_orderId = '31343414';
        $this->quote->expects($this->any())->method('getFedexShipAccountNumber')->willReturn('24231314');
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);
        $this->companyHelper->expects($this->any())->method('getSiteName')->willReturn($this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');
        $this->companyHelper->expects($this->any())->method('getCompanyPaymentMethod')->willReturn('sitecreditcard');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn('sitecreditcard');
        $this->companyHelper->expects($this->any())->method('getPaymentOption')->willReturn('sitecreditcard');
        $this->helperData->ConstructOrderSubmissionAPIRequest($this->quote, $dbItemDetails, $shippingAddress, $poNumber, $contactDetails, $companyId, $availableOption, $quoteId, $pickUpIdLocation, $jsonData, $productAssociations, $magento_orderId);
    }

    /**
     * Test Case for getExternalOrderId method.
     *
     * @return string
     */
    public function testGetExternalOrderId()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $array_data = [
            'transactionId' => '23124214',
            'output' => [
                'orderSubmission' => [
                    'orderNumber' => '121212',
                ],
            ],
        ];
        $this->helperData->GetExternalOrderId($array_data);
    }

    /**
     * Test Case for verifyShippingDetails method.
     *
     * @return string
     */
    public function testVerifyShippingDetails()
    {
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    'OrderRequestHeaders' => [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => 'sada@asd.com',
                                'telephone' => '5123123123',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '1231231313',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'Name' => 'Acme',
                                'PostalAddress' => [
                                    'Street' => ['Houstan', 'Montan'],
                                    'PostalCode' => '2424',
                                    'City' => 'texas',
                                    'State' => 'TX',
                                    'DeliverTo' => ['TYU', 'Thy', 'hsyy'],
                                    'Country' => 'US',
                                    '@attributes' => [
                                        'name' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(14);

        $this->cartInterface->expects($this->any())->method('getCustomerId')->willReturn(44);
        $this->companyMgmtInterface->expects($this->any())->method('getByCustomerId')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->companyInterface->expects($this->any())->method('getData')->with('recipient_address_from_po')->willReturn(1);

        $this->helperData->VerifyShippingDetails($this->cartInterface, $poxml1);
    }

    /**
     * Test Case for verifyShippingDetails method.
     *
     * @return string
     */
    public function testVerifyShippingDetailss()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    'OrderRequestHeaders' => [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => 'sada@asd.com',
                                'telephone' => '5123123123',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '1231231313',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'Name' => 'Acme',
                                'PostalAddress' => [
                                    'Street' => '',
                                    'PostalCode' => '',
                                    'City' => '',
                                    'State' => '',
                                    'DeliverTo' => '',
                                    'Country' => '',
                                    '@attributes' => '',
                                ],
                            ],
                        ],
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(null);
        $starray = [
            'code' => '1231231313',
            'region_id' => 'we3243',
        ];
        $this->poHelper->expects($this->any())->method('getRegionCode')->willReturn($starray);
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('load')->willReturnSelf();
        $this->helperData->VerifyShippingDetails($this->cartInterface, $poxml1);
    }

    /**
     * Test Case for verifyShippingDetails method.
     *
     * @return string
     */
    public function testerVerifyShippingDetails()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(0);
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    'OrderRequestHeaders' => [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => '',
                                'telephone' => '5123123123',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'Name' => 'Acme',
                                'PostalAddress' => [
                                    'Street' => '',
                                    'PostalCode' => '',
                                    'City' => '',
                                    'State' => '',
                                    'DeliverTo' => 'la',
                                    'Country' => '',
                                    '@attributes' => '',
                                ],
                            ],
                        ],
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(null);
        $starray = [
            'code' => '1231231313',
            'region_id' => 'we3243',
        ];
        $this->poHelper->expects($this->any())->method('getRegionCode')->willReturn($starray);
        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
                'email' => '',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($shippingaddress);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('load')->willReturnSelf();
        $this->helperData->VerifyShippingDetails($this->cartInterface, $poxml1);
    }

    /**
     * Test Case for verifyShippingDetails method with toggle on
     * for ship to fields validations
     */
    public function testVerifyShippingDetailsWithShipToFieldsValidation()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    'OrderRequestHeaders' => [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "Rm638 FL6 Sherrie A. King-Woods new tiger sippman volting power ignition plant",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => 'testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttestttesttesttes@yopmail.com',
                                'telephone' => '5123123123',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '123123131',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'Name' => 'Acme',
                                'PostalAddress' => [
                                    'Street' => ['Houstan', 'Montan'],
                                    'PostalCode' => '2424',
                                    'City' => 'texas',
                                    'State' => 'TX',
                                    'DeliverTo' => ['TYU', 'Thy', 'hsyy'],
                                    'Country' => 'US',
                                    '@attributes' => [
                                        'name' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->cartInterface->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($this->quoteShippingAddress);
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(14);

        $this->cartInterface->expects($this->any())->method('getCustomerId')->willReturn(44);
        $this->companyMgmtInterface->expects($this->any())
            ->method('getByCustomerId')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')->willReturn(1);
        $this->companyInterface->expects($this->any())->method('getData')
            ->with('recipient_address_from_po')->willReturn(1);
        $this->helperData->VerifyShippingDetails($this->cartInterface, $poxml1);
    }

    /**
     * Test Case for verifyQuoteDetails method.
     *
     * @return string
     */
    public function testVerifyQuoteDetails()
    {
        $poXml1 = [];
        $this->verifyOrderExist();
        $this->helperData->VerifyQuoteDetails($poXml1);
    }

    /**
     * Test Case for verifyQuoteDetails method.
     *
     * @return string
     */
    public function testVerifyQuoteDetailswithexception()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->verifyOrderExist();
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->negotiableQuoteRepository->expects($this->once())->method('getById')->willThrowException($exception);
        $poXml1 = $this->getPoXmlDatawithShipping();
        $this->helperData->VerifyQuoteDetails($poXml1);
    }

    /**
     * Test Case for verifyQuoteLineItems method.
     *
     * @return string
     */
    public function testVerifyQuoteLineItems()
    {
        $dbItemDetails = [
            'Request' => [
                'itemId' => 3,
                'qty' => 2,
                'productId' => 5,
            ],
        ];
        $poXml1 = [
            'Request' => [
                'OrderRequest' => [
                    'ItemOut' => [
                        '@attributes' => [
                            'quantity' => 3,
                        ],
                        'ItemID' => [
                            'SupplierPartAuxiliaryID' => 5,
                            'SupplierPartID' => 4,
                        ],
                    ],
                ],
            ],
        ];
        $this->helperData->VerifyQuoteLineItems($poXml1, $dbItemDetails);
    }

    /**
     * Test Case for verifyQuoteLineItems method.
     *
     * @return string
     */
    public function test1VerifyQuoteLineItems()
    {

        $dbItemDetails = [
            'Request' => [
                'itemId' => 3,
                'qty' => 2,
                'productId' => 5,
            ],
        ];
        $poXml1 = [
            'Request' => [
                'OrderRequest' => [
                    'ItemOut' => [
                        '@xattributes' => '',
                        'xItemID' => '',
                    ],
                ],
            ],
        ];
        $this->helperData->VerifyQuoteLineItems($poXml1, $dbItemDetails);
    }

    /**
     * Test Case for verifyQuoteLineItems method.
     *
     * @return string
     */
    public function test2VerifyQuoteLineItems()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [
            'Request' => [
                'itemId' => 5,
                'qty' => 2,
                'productId' => 5,
            ],
        ];
        $poXml1 = [
            'Request' => [
                'OrderRequest' => [
                    'ItemOut' => [
                        '@attributes' => [
                            'quantity' => 2,
                        ],
                        'ItemID' => [
                            'SupplierPartAuxiliaryID' => 5,
                            'SupplierPartID' => 4,
                        ],
                    ],
                ],
            ],
        ];
        $this->helperData->VerifyQuoteLineItems($poXml1, $dbItemDetails);
    }

    /**
     * Test Case for verifyQuoteLineItems method.
     *
     * @return string
     */
    public function test3VerifyQuoteLineItems()
    {

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [
            'Request' => [
                'itemId' => 1,
                'qty' => 2,
                'productId' => 4,
            ],
        ];
        $poXml1 = [
            'Request' => [
                'OrderRequest' => [
                    'ItemOut' => [
                        '@attributes' => [
                            'quantity' => 2,
                        ],
                        'ItemID' => [
                            'SupplierPartAuxiliaryID' => 5,
                            'SupplierPartID' => 4,
                        ],
                    ],
                ],
            ],
        ];
        $this->helperData->VerifyQuoteLineItems($poXml1, $dbItemDetails);
    }

    /**
     * Test Case for getOrderApiUrl method.
     *
     * @return string
     */
    public function testGetOrderApiUrl()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->configInterface->expects($this->any())->method('getValue')->willReturn("fedex/general/order_api_url");
        $this->helperData->GetOrderApiUrl();
    }

    /**
     * Test Case for saveExternalOrdId method.
     *
     * @return string
     */
    public function testSaveExternalOrdId()
    {
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn(null);
        $this->order->expects($this->any())->method('load')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('setExtOrderId')->willReturn(true);
        $this->orderInterface->expects($this->any())->method('save');
        $this->poHelper->expects($this->any())->method('sendSuccess')->willReturn("Success");
        $this->helperData->SaveExternalOrdId($this->cartInterface, 1231313, 534535);
    }

    /**
     * Test Case for saveExternalOrdId method.
     *
     * @return string
     */
    public function testSaveExternalOrdIdwithexception()
    {
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn(null);
        $this->order->expects($this->any())->method('load')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('setExtOrderId')->willReturn(false);
        $this->orderInterface->expects($this->once())->method('save')->willThrowException(new \Exception('Some error text'));
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("string");
        $this->helperData->SaveExternalOrdId($this->cartInterface, 1231313, 'asd');
    }

    /**
     * Test Case for getDelieveryOption method.
     *
     * @return string
     */
    public function testGetDelieveryOption()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlDatawithShipping();

        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('');
        $this->helperData->GetDelieveryOption($this->cartInterface, $poxml1);
    }

    /**
     * Test Case for validateRequiredShippingInfo method.
     *
     * @return string
     */
    public function testValidateRequiredShippingInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $shippingaddress1 = [
            'telephone' => '',
        ];
        $this->helperData->ValidateRequiredShippingInfo($shippingaddress1);
    }

    /**
     * Test Case for validateRequiredShippingInfo method.
     *
     * @return string
     */
    public function test1ValidateRequiredShippingInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $shippingaddress1 = [
            'telephone' => '1232141',
            'region_code' => '',
        ];
        $this->helperData->ValidateRequiredShippingInfo($shippingaddress1);
    }

    /**
     * Test Case for validateRequiredShippingInfo method.
     *
     * @return string
     */
    public function test2ValidateRequiredShippingInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $shippingaddress1 = [
            'telephone' => '1232141',
            'region_code' => '234',
            'postcode' => '',
        ];
        $this->helperData->ValidateRequiredShippingInfo($shippingaddress1);
    }

    /**
     * Test Case for validateRequiredShippingInfo method.
     *
     * @return string
     */
    public function test3ValidateRequiredShippingInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $shippingaddress1 = [
            'telephone' => '1232141',
            'region_code' => '234',
            'postcode' => '32335',
            'countryCode' => '',
        ];
        $this->helperData->ValidateRequiredShippingInfo($shippingaddress1);
    }

    /**
     * Test Case for ValidateRequiredContactInfo method.
     *
     * @return string
     */
    public function testValidateRequiredContactInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $contactaddress1 = [
            'fname' => '',
            'lname' => '',
            'email' => '',
        ];
        $this->helperData->ValidateRequiredContactInfo($contactaddress1);
    }

    /**
     * Test Case for ValidateRequiredContactInfo method.
     *
     * @return string
     */
    public function test1ValidateRequiredContactInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $contactaddress1 = [
            'fname' => 'john',
            'lname' => '',
            'email' => '',
        ];
        $this->helperData->ValidateRequiredContactInfo($contactaddress1);
    }

    /**
     * Test Case for ValidateRequiredContactInfo method.
     *
     * @return string
     */
    public function test2ValidateRequiredContactInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $contactaddress1 = [
            'fname' => 'john',
            'lname' => 'hunm',
            'email' => '',
        ];
        $this->helperData->ValidateRequiredContactInfo($contactaddress1);
    }

    /**
     * Test Case for GetProductData method.
     *
     * @return string
     */
    public function testGetProductData()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $productdata = ['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]];
        $this->helperData->GetProductData($productdata);
    }

    /**
     * Test Case for CallOrderSubmissionApi method.
     *
     * @return string
     */
    public function testCallOrderSubmissionApi()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $arrayRequest = [
            'Request' => [
                'OrderRequest' => [
                    'OrderRequestHeaders' => [
                        'Payment' => ['PCard' => ['@attributes' => ['number' => '232141', 'expiration' => '233424']]],
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => 'sada@asd.com',
                                'telephone' => '5123123123',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '1231231313',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'Name' => 'Acme',
                                'PostalAddress' => [
                                    'Street' => ['Houstan', 'Montan'],
                                    'PostalCode' => '2424',
                                    'City' => 'texas',
                                    'State' => 'TX',
                                    'DeliverTo' => ['TYU', 'Thy', 'hsyy'],
                                    'Country' => 'US',
                                    '@attributes' => [
                                        'name' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'OrderRequestHeader' => [
                        'Shipping' => [
                            'Description' => 'fedex flat rate',
                        ],
                        'SupplierOrderInfo' => [
                            '@attributes' => [
                                "orderID" => "po123",
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $jsonData = json_encode(['access_token' => 'token', 'token_type' => 'type']);
        $productAssociations = [
            'id' => 'john',
            'quantity' => 4,
        ];
        $this->helper->expects($this->any())->method('getTazToken')->willReturn(json_encode(['access_token' => 'token', 'token_type' => 'type']));
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn('2342423');
        $this->configInterface->expects($this->any())->method('getValue')->willReturn("fedex/general/order_api_url");
        $this->helperData->CallOrderSubmissionApi(123, $arrayRequest, $jsonData, $productAssociations);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrder()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => '5',
                                'telephone' => '5123123123',
                                'Name' => 'Acme',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '1231231313',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'PostalAddress' => [
                                    'Street' => ['Houstan', 'Montan'],
                                    'PostalCode' => '12312',
                                    'City' => 'Sunnyvale',
                                    'State' => 'CA',
                                    'Country' => 'US',
                                    'DeliverTo' => ['TYU', 'Thy', 'hsyy'],
                                    'Email' => '5',
                                    '@attributes' => [
                                        'name' => 'default',
                                    ],
                                ],
                            ],
                        ],
                        'OrderRequestHeader' => [
                            'Shipping' => [
                                'Description' => 'fedex flat rate',
                            ],
                            'SupplierOrderInfo' => [
                                '@attributes' => [
                                    "orderID" => "po123",
                                ],
                            ],
                        ],
                        0 => [
                            '@attributes' => [
                                'quantity' => 3,
                            ],
                            'ItemID' => [
                                'SupplierPartAuxiliaryID' => 12,
                                'SupplierPartID' => 6,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");

        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];

        $this->cartInterface->method('getShippingAddress')->withConsecutive([],[],[],[],[],[],[],[])->willReturnOnConsecutiveCalls($this->quoteShippingAddress,$this->quoteShippingAddress,$this->quoteShippingAddress,$shippingaddress,$shippingaddress,$shippingaddress,$this->quoteShippingAddress,$this->quoteShippingAddress);

        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(14);

        $this->cartInterface->method('getShippingAddress')->willReturn($this->quoteShippingAddress);

        $this->region->expects($this->any())->method('load')->willReturnSelf();
        $this->region->expects($this->any())->method('getCode')->willReturn('string');

        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);
        $this->customerRepositoryInterface->expects($this->any())->method('getById')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getFirstname')->willReturn('asd');
        $this->Customermock->expects($this->any())->method('getLastname')->willReturn('asd');
        $this->Customermock->expects($this->any())->method('getEmail')->willReturn('asd');

        $optionData = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => '',
                    ],
                ],
            ],
        ];
        $this->cartInterface->expects($this->any())->method('getItemsCollection')->willReturn([$this->quoteItem]);
        $this->quoteItem
            ->expects($this->any())
            ->method('getOptionByCode')
            ->willReturn($this->_optionInterface);

        $this->_optionInterface->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($optionData));
        $this->_optionInterface->expects($this->any())
            ->method('getProductId')
            ->willReturn(3);
        $this->quoteItem->expects($this->any())->method('getId')->willReturn(12);
        $this->quoteItem->expects($this->any())->method('getQty')->willReturn(3);
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');

        $this->assertEquals(null, $actualResult);
    }


    public function testProcessOrderwithavailable1()
    {

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $shippingaddress1 = [
            'telephone' => '1232141',
            'region_code' => '234',
            'postcode' => '32335',
            'countryCode' => '',
        ];
        $this->helperData->ValidateRequiredShippingInfo($shippingaddress1);


    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwitherrorinContactInfo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->helper->expects($this->any())->method('removeSpaceFromNameToggle')->willReturn(true);
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => '5',
                                'telephone' => '5123123123',
                                'Name' => 'Acme',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '1231231313',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'PostalAddress' => [
                                    'Street' => ['Houstan', 'Montan'],
                                    'PostalCode' => '12312',
                                    'City' => 'Sunnyvale',
                                    'State' => 'CA',
                                    'Country' => 'US',
                                    'DeliverTo' => ['TYU', 'Thy', 'hsyy'],
                                    'Email' => '5',
                                    '@attributes' => [
                                        'name' => 'default',
                                    ],
                                ],
                            ],
                        ],
                        'OrderRequestHeader' => [
                            'Shipping' => [
                                'Description' => 'fedex flat rate',
                            ],
                            'SupplierOrderInfo' => [
                                '@attributes' => [
                                    "orderID" => "po123",
                                ],
                            ],
                        ],
                        0 => [
                            '@attributes' => [
                                'quantity' => 3,
                            ],
                            'ItemID' => [
                                'SupplierPartAuxiliaryID' => 12,
                                'SupplierPartID' => 4,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");


        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];

        $this->cartInterface->method('getShippingAddress')->withConsecutive([],[],[],[],[],[],[],[])->willReturnOnConsecutiveCalls($this->quoteShippingAddress,$this->quoteShippingAddress,$this->quoteShippingAddress,$shippingaddress,$shippingaddress,$shippingaddress,$this->quoteShippingAddress,$this->quoteShippingAddress);


        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByName')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(14);


        $this->region->expects($this->any())->method('load')->willReturnSelf();
        $this->region->expects($this->any())->method('getCode')->willReturn('string');

        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);
        $this->customerRepositoryInterface->expects($this->any())->method('getById')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getFirstname')->willReturn('');
        $this->Customermock->expects($this->any())->method('getLastname')->willReturn('');
        $this->Customermock->expects($this->any())->method('getEmail')->willReturn('');
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');

        $this->assertEquals(null, $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwithstatusclosed()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlData();

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("string");
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = 'closed');

        $this->assertEquals('string', $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwithstatusordered()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlData();

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("string");
        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = 'ordered');

        $this->assertEquals('string', $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwithemptyPonumber()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlData();

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("");
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("string");
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');

        $this->assertEquals('string', $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwithgreterPonumber()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlData();

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("PONUMBERGRETERTHAN25CHARACTER");
        $this->poHelper->expects($this->any())->method('sendError')->willReturn("string");
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');

        $this->assertEquals('string', $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwitherrorinShippingDetails()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlData();

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);

        $this->cartInterface->method('getCustomerId')->withConsecutive([])->willReturnOnConsecutiveCalls(45);
        $this->companyMgmtInterface->expects($this->any())->method('getByCustomerId')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')->willReturn(3);
        $this->companyInterface->expects($this->any())->method('getData')->with('recipient_address_from_po')->willReturn(1);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('');
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');
        $this->assertEquals(null, $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwitherrorinDeliveryoption()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poxml1 = $this->getPoXmlData();

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);

        $this->cartInterface->method('getCustomerId')->withConsecutive([])->willReturnOnConsecutiveCalls(45);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('');
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');
        $this->assertEquals(null, $actualResult);
    }

    /**
     * Test Case for ProcessOrder method.
     *
     * @return string
     */
    public function testProcessOrderwitherrorinShippingadress()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->helper->expects($this->any())->method('removeSpaceFromNameToggle')->willReturn(true);

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $poxml1 = [
            'Request' => [
                'OrderRequest' => [
                    [
                        'ShipTo' => [
                            'Address' => [
                                'shipping_method' => 'fedexshipping_PICKUP',
                                'street' => "123 Anystreet",
                                'city' => 'Sunnyvale',
                                'State' => 'CA',
                                'postcode' => '90489',
                                'country_id' => '5',
                                'region_id' => '123',
                                'company' => 'Sunnyvale',
                                'firstname' => 'CA',
                                'lastname' => '90489',
                                'Email' => '5',
                                'telephone' => '5123123123',
                                'Name' => 'Acme',
                                'Phone' => [
                                    'TelephoneNumber' => [
                                        'Number' => '1231231313',
                                        'AreaOrCityCode' => 'we3243',
                                    ],
                                ],
                                'PostalAddress' => [
                                    'Street' => ['Houstan', 'Montan'],
                                    'PostalCode' => '12312',
                                    'City' => 'Sunnyvale',
                                    'State' => 'CA',
                                    'Country' => 'US',
                                    'DeliverTo' => ['TYU', 'Thy', 'hsyy'],
                                    'Email' => '5',
                                    '@attributes' => [
                                        'name' => 'default',
                                    ],
                                ],
                            ],
                        ],
                        'OrderRequestHeader' => [
                            'Shipping' => [
                                'Description' => 'fedex flat rate',
                            ],
                            'SupplierOrderInfo' => [
                                '@attributes' => [
                                    "orderID" => "po123",
                                ],
                            ],
                        ],
                        0 => [
                            '@attributes' => [
                                'quantity' => 3,
                            ],
                            'ItemID' => [
                                'SupplierPartAuxiliaryID' => 12,
                                'SupplierPartID' => 4,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $companyDetails = ['status' => 'ok', 'website_id' => 2, 'website_url' => 'https://shop-staging2.fedex.com/statefarm', 'group_id' => 1, 'company_id' => 1, 'company_name' => 'StateFarm', 'msg' => '',
            'store_id' => 2, 'rule' => '', 'type' => '', 'extra_data' => ['redirect_url' => '', 'response_url' => '', 'cookie' => ''], 'legacy_site_name' => 'testeprosite'];

        $this->poHelper->expects($this->any())->method('getPoNumber')->willReturn("P01212");

        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        //$this->quote->expects($this->any())->method('getShippingAddress')->willReturn($this->quoteShippingAddress);
        $this->quoteShippingAddress->expects($this->any())->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');
        $this->regionFactory->expects($this->any())->method('create')->willReturn($this->region);
        $this->region->expects($this->any())->method('loadByCode')->willReturn($this->region);
        $this->region->expects($this->any())->method('getId')->willReturn(14);

        $shippingaddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '',
                ],
            ],
        ];
        $this->region->expects($this->any())->method('load')->willReturnSelf();
        $this->region->expects($this->any())->method('getCode')->willReturn('string');
        $actualResult = $this->helperData->processOrder($poxml1, $quoteId = '', $companyDetails, $quoteStatus = '');

        $this->assertEquals(null, $actualResult);
    }

    /**
     * Test Case for VerifyQuoteLineItems method.
     *
     * @return string
     */
    public function test4VerifyQuoteLineItems()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $dbItemDetails = [
            'Request' => [
                'itemId' => 1,
                'qty' => 2,
                'productId' => 4,
            ],
        ];
        $poXml1 = [
            'Request' => [
                'OrderRequest' => [
                    'ItemOut' => [
                        0 => [
                            '@attributes' => [
                                'quantity' => 3,
                            ],
                            'ItemID' => [
                                'SupplierPartAuxiliaryID' => 5,
                                'SupplierPartID' => 4,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->helperData->VerifyQuoteLineItems($poXml1, $dbItemDetails);
    }

    /**
     * Test Case for VerifyQuoteLineItems method.
     *
     * @test testFilterPoNumber
     */
    public function testFilterPoNumber()
    {
        $poNumber = "Test.PO";
        $this->assertEquals($poNumber,$this->helperData->filterPoNumber($poNumber));
    }

    /**
     * Test Case for testProcessOrderShipingInfoValidation method.
     *
     * @test testProcessOrderShipingInfoValidation
     */

    public function testProcessOrderShipingInfoValidation()
    {
        $rejectionReason = "";
        $shipAddress = ['firstname' => 'neeraj','available' => 1,'address'=>'1 New York'];
        $quoteId = '23';
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $withCXML = " withCXML ";

        $this->assertEquals('Invalid/Missing telephone number',$this->helperData->processOrderShipingInfoValidation(
            $rejectionReason,
            $shipAddress,
            $quoteId,
            $xmlArray,
            $withCXML
        ));
    }

    public function testProcessOrderShipingInfoValidationTwo()
    {
        $rejectionReason = "";
        $shipAddress = ['firstname' => 'neeraj','available' => 1,'address'=>['telephone'=>'13446656','region_code'=>'region_code','postcode'=>'postcode','countryCode'=>'countryCode']];
        $quoteId = '23';
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $withCXML = " withCXML ";

        $this->assertEquals('',$this->helperData->processOrderShipingInfoValidation(
            $rejectionReason,
            $shipAddress,
            $quoteId,
            $xmlArray,
            $withCXML
        ));
    }

    public function testProcessOrderContactValidate()
    {

        $rejectionReason = "";
        $contactDetail = ['contact'=>[],'firstname' => 'neeraj','available' => 0,'address'=>['telephone'=>'13446656','region_code'=>'region_code','postcode'=>'postcode','countryCode'=>'countryCode']];
        $quoteId = '23';
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);

        $this->assertEquals('Contact Details are Missing',$this->helperData->processOrderContactValidate(
            $rejectionReason,
            $contactDetail,
            $xmlArray,
            $quoteId
        ));
    }

    public function testProcessOrderContactValidateTwo()
    {

        $rejectionReason = "";
        $contactDetail = ['msg'=>'Test Message','contact'=>['fname'=>'fname','email'=>'fname','lname'=>'lname'],'firstname' => 'neeraj','available' => 3,'address'=>['telephone'=>'13446656','region_code'=>'region_code','postcode'=>'postcode','countryCode'=>'countryCode']];
        $quoteId = '23';
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);

        $this->assertEquals('Test Message',$this->helperData->processOrderContactValidate(
            $rejectionReason,
            $contactDetail,
            $xmlArray,
            $quoteId
        ));
    }

    public function testProcessOrderContactValidateThree()
    {

        $rejectionReason = "";
        $contactDetail = ['msg'=>'Test Message','contact'=>[],'firstname' => 'neeraj','available' => 1,'address'=>['telephone'=>'13446656','region_code'=>'region_code','postcode'=>'postcode','countryCode'=>'countryCode']];
        $quoteId = '23';
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);

        $this->assertEquals('Missing firstname',$this->helperData->processOrderContactValidate(
            $rejectionReason,
            $contactDetail,
            $xmlArray,
            $quoteId
        ));
    }

    public function testProcessOrderAfterDbItemValid()
    {
        $dbItemDetails = [['external_product'=>'external_product','qty'=>'1'],['external_product'=>'external_product','qty'=>'1']];
        $quote = $this->cartInterface;

        $companyDetails = [];

        $availableOption = 1;

        $quoteId = 23;
        $withCXML = " withCXML ";
        $contactDetails = ['msg'=>'Test Message','contact'=>[],'firstname' => 'neeraj','available' => 1,'address'=>['telephone'=>'13446656','region_code'=>'region_code','postcode'=>'postcode','countryCode'=>'countryCode']];

        $companyId = 23;

        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poNumber = 'PO12313';
        $companyDetails = ['legacy_site_name' => 'Madmax'];
        $product = ['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]];
        $association = ['name' => 'Association'];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $billingAddress = [
            'billing_method' => 'fedexshipping_PICKUP',
            'billing_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $pickUpIdLocation = 'fedexPickup';

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('102002002');
        $this->orderInterface->expects($this->any())->method('getEntityId')->willReturn("1212");
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('getLocationID')->willReturn("1212");
        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);

        $this->helper->expects($this->any())->method('getTazToken')->willReturn(json_encode(['access_token' => 'token', 'token_type' => 'type']));
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn('2342423');
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('adjustQuote')->willReturn(['error' => 1, 'msg' => 'Unable to submit order: internal token error', 'order_id' => '']);

        $this->helperData->processOrderAfterDbItemValid(
            $dbItemDetails,
            $quote,
            $poNumber,
            $companyDetails,
            $shippingAddress,
            $billingAddress,
            $availableOption,
            $pickUpIdLocation,
            $quoteId,
            $withCXML,
            $xmlArray,
            $contactDetails,
            $companyId
        );
    }
    public function testProcessOrderAfterDbItemValidTwo()
    {
        $dbItemDetails = [['external_product'=>'external_product','qty'=>'1'],['external_product'=>'external_product','qty'=>'1']];
        $this->cartInterface->expects($this->atMost(2))->method('getEstimatedPickupTime')
            ->willReturn(null);
        $quote = $this->cartInterface;

        $companyDetails = [];

        $availableOption = 1;

        $quoteId = 23;
        $withCXML = " withCXML ";
        $contactDetails = ['contact_ext'=>'contact_ext','contact_number'=>'contact_number','fname'=>'fanme','email'=>'email','lname'=>'lname','msg'=>'Test Message','contact'=>['fname'=>'fanme','email'=>'email','lname'=>'lname'],'firstname' => 'neeraj','available' => 1,'address'=>['telephone'=>'13446656','region_code'=>'region_code','postcode'=>'postcode','countryCode'=>'countryCode']];

        $companyId = 23;

        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $poNumber = 'PO12313';
        $companyDetails = ['legacy_site_name' => 'Madmax'];
        $product = ['Request' => [
            'external_product' => 'john',
            'qty' => 4,
        ]];
        $association = ['name' => 'Association'];
        $shippingAddress = [
            'shipping_method' => 'fedexshipping_PICKUP',
            'shipping_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'phoneNumberExt'=>'phoneNumberExt',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $billingAddress = [
            'billing_method' => 'fedexshipping_PICKUP',
            'billing_description' => 'fedexshipping',
            'street' => "123 Anystreet",
            'city' => 'Sunnyvale',
            'State' => 'CA',
            'postcode' => '90489',
            'country_id' => '5',
            'region_id' => '123',
            'company' => 'Sunnyvale',
            'firstname' => 'CA',
            'lastname' => '90489',
            'email' => '5',
            'telephone' => '5123123123',
            'Name' => 'Acme',
            'phoneNumberExt'=>'phoneNumberExt',
            'address' => [
                'postcode' => '23123',
            ],
            'PostalAddress' => [
                '@attributes' => [
                    'name' => 'default',
                ],
                'address' => [
                    'postcode' => '23123',
                ],
            ],
        ];
        $availableOption = ['serviceType' => 'true', 'available' => 1];
        $pickUpIdLocation = 'fedexPickup';

        $this->cartRepositoryInterface->expects($this->any())->method('get')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getReservedOrderId')->willReturn("reserved");
        $this->order->expects($this->any())->method('loadByIncrementId')->willReturn($this->orderInterface);
        $this->orderInterface->expects($this->any())->method('getExtOrderId')->willReturn('');
        $this->orderInterface->expects($this->any())->method('getEntityId')->willReturn("1212");
        $this->order->expects($this->any())->method('getCollection')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getFirstItem')->willReturn($this->order);
        $this->poHelper->expects($this->any())->method('getLocationID')->willReturn("1212");
        $this->cartInterface->expects($this->any())->method('getCustomer')->willReturn($this->Customermock);
        $this->Customermock->expects($this->any())->method('getId')->willReturn(4);

        $this->helper->expects($this->any())->method('getTazToken')->willReturn(json_encode(['access_token' => 'token', 'token_type' => 'type']));
        $this->helper->expects($this->any())->method('getAuthGatewayToken')->willReturn('2342423');
        $this->shiptoHelper->expects($this->any())->method('sendOrderFailureNotification')->willReturn(false);
        $this->poHelper->expects($this->any())->method('adjustQuote')->willReturn(['error' => 1, 'msg' => 'Unable to submit order: internal token error', 'order_id' => '']);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyHelper);
        $this->companyHelper->expects($this->any())->method('getSiteName')->willReturn($this->companyHelper);
        $this->dataHelper->expects($this->any())->method('getCompanySite')->willReturn('Myepro');

        $this->helperData->processOrderAfterDbItemValid(
            $dbItemDetails,
            $quote,
            $poNumber,
            $companyDetails,
            $shippingAddress,
            $billingAddress,
            $availableOption,
            $pickUpIdLocation,
            $quoteId,
            $withCXML,
            $xmlArray,
            $contactDetails,
            $companyId
        );
    }

    public function testProcessOrderValidLineItems()
    {
        $validLineItems = 0;
        $quoteId = 1;
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $withCXML = " withCXML ";
        $rejectionReason = "";
        $this->helperData->processOrderValidLineItems(
            $validLineItems,
            $quoteId,
            $xmlArray,
            $withCXML,
            $rejectionReason
        );
    }

    public function testProcessOrderValidLineItemsTwo()
    {
        $validLineItems = 0;
        $quoteId = 1;
        $poxml = '{"@attributes":{"payloadID":"456778-199@cxml.fxo.com","timestamp":"2021-01-04T01:52:34-0800"},"Header":{"From":{"Credential":{"@attributes":{"domain":"MAGENTO1"},"Identity":"NetworkId1"}},"To":{"Credential":{"@attributes":{"domain":"privateid"},"Identity":"999032669"}},"Sender":{"Credential":{"@attributes":{"domain":"AribaNetworkUserId"},"Identity":"sysadmin@ariba.com","SharedSecret":"f3d3xs3rv1c3s"},"UserAgent":"Hubspan
        Translation
        Services"}},"Request":{"OrderRequest":{"OrderRequestHeader":{"@attributes":{"orderID":"po252","orderDate":"2021-01-04T01:52:34-0800","type":"new"},"Total":{"Money":"0.4900"},"BillTo":{"Address":{"Name":"Acme","PostalAddress":{"@attributes":{"name":"default"},"Street":"123
        Anystreet","City":"Sunnyvale","State":"CA","PostalCode":"90489","Country":"United
        States"}}},"Contact":{"Name":"sersinghs","Email":"bharavsingh@gmail.com"},"SupplierOrderInfo":{"@attributes":{"orderID":"252"}}},"ItemOut":{"@attributes":{"quantity":"1","lineNumber":"1"},"ItemID":{"SupplierPartID":"3","SupplierPartAuxiliaryID":"420"},"ItemDetail":{"UnitPrice":{"Money":"0.4900"},"Description":"test
        doc","UnitOfMeasure":"EA","Classification":"82121503","Extrinsic":{"@attributes":{"name":"ItemExtendedPrice"},"Money":"0.49"}}}}}}';
        $xmlArray = json_decode($poxml, true);
        $withCXML = " withCXML ";
        $rejectionReason = "";
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->helperData->processOrderValidLineItems(
            $validLineItems,
            $quoteId,
            $xmlArray,
            $withCXML,
            $rejectionReason
        );
    }

    public function testGetQuotePickupLocalTime()
    {
        $this->quote->expects($this->atLeast(2))->method('getEstimatedPickupTime')
            ->willReturn('Friday,February 10th At 05:00 PM');
        $this->assertNotNull(
            $this->helperData->getQuotePickupLocalTime($this->quote),
            '2023-02-10T17:00:00'
        );
    }

    public function testGetQuotePickupLocalTimeNull()
    {
        $this->quote->expects($this->once())->method('getEstimatedPickupTime')
            ->willReturn(null);
        $this->assertNull($this->helperData->getQuotePickupLocalTime($this->quote));
    }
    /**
     * Common Function for coverage VerifyOrder Function
     */
    public function verifyOrderExist()
    {
        $this->order->expects($this->any())->method('getCOllection')
            ->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('addFieldToFilter')
            ->willReturnSelf();
        $this->orderCollection->expects($this->any())->method('count')
            ->willReturn(0);
    }
}
