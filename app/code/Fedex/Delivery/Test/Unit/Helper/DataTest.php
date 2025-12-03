<?php
namespace Fedex\Delivery\Test\Unit\Helper;

use Exception;
use Fedex\Delivery\Helper\Data;
use Fedex\Email\Helper\SendEmail;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig as ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Purchaseorder\Helper\Notification;
use Fedex\SDE\Helper\SdeHelper as SdeHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as ResourceCustomer;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Catalog\Model\Product;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Base\Helper\Auth;
use Magento\Framework\Filesystem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\MarketplaceCheckout\Helper\Data AS MarketplaceCheckoutHelper;

class DataTest extends TestCase
{
    protected $toggleConfigMock;
    protected $selfRegHelperMock;
    /**
     * @var (\Magento\Customer\Model\ResourceModel\Customer & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $_customerResourceMock;
    /**
     * @var (\Fedex\Purchaseorder\Helper\Notification & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $notificationHelper;
    protected $punchoutHelper;
    protected $json;
    protected $jsonValidator;

    /**
     * @var (\Fedex\Email\Helper\SendEmail & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $mail;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    protected $url;
    /**
     * @var (\Magento\Framework\HTTP\Client\Curl & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $curl;
    protected $sdeHelper;
    protected $scopeConfig;
    protected $filesystemMock;
    protected $directoryReadMock;
    protected $imageFactoryMock;
    protected $customerSession;
    protected $customer;
    protected $customerFactoryMock;
    protected $data;
    protected $customerRepository;
    protected $customerInterface;
    protected $companyRepository;
    protected $companyInterface;
    protected $cartFactory;
    protected $timezone;
    protected $date;
    protected $storeManager;
    protected $contextMock;
    protected $customerExtensionInterfaceMock;
    protected $companyCustomerInterfaceMock;
    private $cartFactoryMock;
    private $cartMock;
    private $quoteMock;
    private $itemMock;
    private $itemOptionMock;
    private $storeInterfaceMock;
    private $attributeSetRepositoryInterfaceMock;
    private $attributeSetInterfaceMock;
    private $product;
    private $quoteDataHelper;
    private $productRepositoryMock;

    /**
     * @var SessionFactory $customerSessionFactory
     */
    protected $customerSessionFactory;
    protected Auth|MockObject $baseAuthMock;
    private MarketplaceCheckoutHelper $marketplaceCheckoutHelper;


    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getId',
                    'getCustomer',
                    'setCustomer',
                    'isLoggedIn',
                    'getCustomerCompany',
                    'getBackUrl',
                    'getCommunicationUrl',
                    'getCommunicationCookie',
                    'getApiAccessToken',
                    'getApiAccessType',
                    'getGatewayToken',
                    'getAuthGatewayToken',
                    'create',
                    'setApiAccessToken',
                    'setApiAccessType',
                    'setGatewayToken',
                    'getOnBehalfOf',
                    'getOndemandCompanyInfo'
                ]
            )
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->customerSessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'create',
                    'getSecondaryEmail',
                    'getCustomer',
                    'getId',
                    'getFirstname',
                    'getLastname',
                    'getContactNumber',
                    'getContactExt',
                    'getEmail',
                    'getOndemandCompanyInfo',
                    'isLoggedIn'
                ]
            )
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn','getCompanyAuthenticationMethod'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'get'])
            ->getMockForAbstractClass();

        $this->companyCustomerInterfaceMock = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId'])
            ->getMockForAbstractClass();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkCustomerIsCompanyAdmin'])
            ->getMock();

        $this->customerExtensionInterfaceMock = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();

        $this->_customerResourceMock = $this->getMockBuilder(ResourceCustomer::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'save'])
            ->getMock();

        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'getExtensionAttributes', 'getId', 'getIsPickup'])
            ->getMockForAbstractClass();

        $this->attributeSetRepositoryInterfaceMock = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->attributeSetInterfaceMock = $this->getMockBuilder(AttributeSetInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeSetName'])
            ->getMockForAbstractClass();

        $this->timezone = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['format'])
            ->getMockForAbstractClass();
        $this->date = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'getStore', 'getWebsite'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentCurrencyCode', 'getBaseUrl', 'getWebsiteId'])
            ->getMockForAbstractClass();

        $this->notificationHelper = $this->getMockBuilder(Notification::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonValidator = $this->getMockBuilder(JsonValidator::class)
            ->setMethods(['isValid'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mail = $this->getMockBuilder(SendEmail::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
              'getSiteName',
              'getIsPickup',
              'getIsDelivery',
              'getDomainName',
              'getNetworkId',
              'getAllowedDeliveryOptions',
              'getCompanyLogo',
              'getId',
              'getStorefrontLoginMethodOption',
              'getIsSensitiveDataEnabled',
              'getSsoLogoutUrl',
              'getCompanyUrlExtention',
                'getAdobeAnalytics'
            ])
            ->getMockForAbstractClass();
        $this->curl = $this->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->setMethods(['getBody'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface::class)
            ->setMethods(
                [
                    'getOptionByCode',
                    'getQty',
                    'getName',
                    'getProductId',
                    'getId',
                    'getPrice',
                    'getDiscount',
                    'getItemId',
                    'setBaseRowTotal',
                    'setRowTotal',
                    'setDiscount',
                    'setBaseRowTotalInclTax',
                    'setRowTotalInclTax',
                    'setPrice',
                    'setPriceInclTax',
                    'setBasePrice',
                    'setBasePriceInclTax',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setIsSuperMode',
                    'getMiraklOfferId',
                    'getProductType'
                ]
            )
            ->getMockForAbstractClass();

        $this->itemOptionMock = $this->getMockBuilder(Option::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['sdeCommercialCheckout', 'getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->setMethods(['load','getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->quoteDataHelper = $this->getMockBuilder(QuoteDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectSignatureOptionsParams'])
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDirectoryRead'])
            ->getMock();
        $this->directoryReadMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\ReadInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAbsolutePath',
                        'getRelativePath',
                        'read',
                        'search',
                        'isExist',
                        'stat',
                        'isReadable',
                        'isFile',
                        'isDirectory',
                        'openFile',
                        'readFile'])
            ->getMock();
        $this->imageFactoryMock = $this->getMockBuilder(\Magento\Framework\Image\AdapterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->marketplaceCheckoutHelper = $this->createMock(MarketplaceCheckoutHelper::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'customerSession' => $this->customerSession,
                'customerRepository' => $this->customerRepository,
                'notificationHelper' => $this->notificationHelper,
                'cartFactory' => $this->cartFactoryMock,
                'timezone' => $this->timezone,
                'date' => $this->date,
                'storeManager' => $this->storeManager,
                'mail' => $this->mail,
                'punchoutHelper' => $this->punchoutHelper,
                'companyRepository' => $this->companyRepository,
                'logger' => $this->logger,
                'attributeSetRepositoryInterface' => $this->attributeSetRepositoryInterfaceMock,
                'url' => $this->url,
                '_customerFactory' => $this->customerFactoryMock,
                'customerSessionFactory' => $this->customerSessionFactory,
                'sdeHelper' => $this->sdeHelper,
                'toggleConfig' => $this->toggleConfigMock,
                'json' => $this->json,
                'product' => $this->product,
                'quoteDataHelper' => $this->quoteDataHelper,
                'jsonValidator' => $this->jsonValidator,
                'curl' => $this->curl,
                'selfregHelper' => $this->selfRegHelperMock,
                'authHelper' => $this->baseAuthMock,
                'filesystem' => $this->filesystemMock,
                'directoryRead' => $this->directoryReadMock,
                'scopeConfig' => $this->scopeConfig,
                'productRepository'=> $this->productRepositoryMock,
                'marketplaceCheckoutHelper'=> $this->marketplaceCheckoutHelper,

            ]
        );
    }

    /**
     * Test getCustomer.
     *
     * @return array
     */
    public function testGetCustomer()
    {
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())
        ->method('getCustomer')->willReturn($this->customerSession);
        $this->assertEquals(false, $this->data->getCustomer());
    }

    public function testGetAssignedCompany()
    {
        $companyId = 1;
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn($companyId);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);

        $this->assertEquals($this->companyInterface, $this->data->getAssignedCompany($this->customerInterface));
    }

    public function testGetIsDelivery()
    {
        $companyId = 1;
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())
        ->method('getCustomer')->willReturn($this->customerSession);
        $this->customerSession->expects($this->any())->method('getId')->willReturn(4);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->companyInterface->expects($this->once())->method('getIsDelivery')->willReturn(true);
        $this->assertEquals(true, $this->data->getIsDelivery());
    }

    public function testGetIsDeliveryWithoutCustomerId()
    {
        $companyId = 1;
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);

        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn('');

        $this->companyInterface->expects($this->any())->method('getIsDelivery')->willReturn(true);

        $this->assertEquals(false, $this->data->getIsDelivery());
    }

    public function testGetIsDeliveryWithisCommercialCustomerAsFalse()
    {
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(0);
        $this->assertEquals(true, $this->data->getIsDelivery());
    }

    public function testGetIsPickup()
    {
        $companyId = 1;

        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);

        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
        ->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->once())->method('getId')->willReturn(2);
        $this->companyInterface->expects($this->once())->method('getIsPickup')->willReturn(true);

        $this->assertEquals(true, $this->data->getIsPickup());
    }


    public function testGetIsPickupAuthToggleOn()
    {
        $companyId = 1;
        $this->baseAuthMock->method('isLoggedIn')->willReturn(false);

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);

        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerInterface->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->once())->method('getId')->willReturn(2);
        $this->companyInterface->expects($this->once())->method('getIsPickup')->willReturn(true);

        $this->assertEquals(true, $this->data->getIsPickup());
    }

    public function testGetIsPickupWithoutCustomerId()
    {
        $companyId = 1;
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);

        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn('');

        $this->companyInterface->expects($this->any())->method('getIsPickup')->willReturn(true);

        $this->assertEquals(false, $this->data->getIsPickup());
    }

    public function testGetIsPickupWithisCommercialCustomerAsFalse()
    {
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(0);
        $this->assertEquals(true, $this->data->getIsPickup());
    }

    public function testGetRedirectUrl()
    {
        $this->customerSession->expects($this->any())->method('getBackUrl')->willReturn('https://shop.fedex');
        $this->url->expects($this->any())->method('getUrl')->willReturn('https://shop.fedex/success');
        $this->assertEquals('https://shop.fedex', $this->data->getRedirectUrl());
    }

    public function testGetRedirectUrlWithEmptyURL()
    {
        $this->customerSession->expects($this->any())->method('getBackUrl')->willReturn('');
        $this->url->expects($this->any())->method('getUrl')->willReturn('https://shop.fedex/success');
        $this->assertNotNull($this->data->getRedirectUrl());
    }

    public function testGetCompanySite()
    {
        $companyId = 1;
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getSiteName')->willReturn('company');
        $this->assertEquals('company', $this->data->getCompanySite());
    }

    public function testGetCompanySiteWithoutSiteName()
    {
        $companyId = 1;
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getSiteName')->willReturn('');
        $this->assertEquals(null, $this->data->getCompanySite());
    }

    public function testGetCompanySiteisCommercialCustomerAsFalse()
    {
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(0);

        $this->assertEquals(null, $this->data->getCompanySite());
    }

    public function testSendNotification()
    {
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn(2);
        $this->cartFactoryMock->expects($this->once())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);

        $this->date->expects($this->once())->method('gmtDate')->willReturn("2020-06-06");
        $this->timezone->expects($this->once())->method('date')->willReturnSelf();
        $this->timezone->expects($this->once())->method('format')->willReturn("2020-06-06 12:12:12");

        $this->customerSession->expects($this->any())->method('getCommunicationUrl')
        ->willReturn('https://shop.gedex.com');
        $this->companyInterface->expects($this->any())->method('getDomainName')->willReturn('domain_name');
        $this->companyInterface->expects($this->any())->method('getNetworkId')->willReturn('network_id');
        $this->customerSession->expects($this->any())
        ->method('getCommunicationCookie')->willReturn('communication_cookie');

        $this->quoteMock->expects($this->once())->method('getAllItems')->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())->method('getOptionByCode')->willReturn($this->itemOptionMock);
        $this->itemMock->expects($this->any())->method('getName')->willReturn('name');
        $this->itemMock->expects($this->any())->method('getPrice')->willReturn(123);
        $this->itemMock->expects($this->any())->method('getDiscount')->willReturn(12);
        $this->itemMock->expects($this->any())->method('getId')->willReturn(12);
        $this->itemMock->expects($this->any())->method('getProductId')->willReturn(12);
        $this->itemMock->expects($this->any())->method('getQty')->willReturn(2);

        $getValue = json_encode(
            ['external_prod' =>
            ['0' =>
            ['catalogReference' => 'value',
            'preview_url' => 'value2',
            'fxo_product' => 'value3'
            ]]]
        );
        $this->itemOptionMock->expects($this->once())->method('getValue')->willReturn($getValue);

        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->once())->method('getCurrentCurrencyCode')->willReturn("USD");
        $this->assertEquals(null, $this->data->sendNotification('create', 'final'));
    }
    public function testGetApiToken()
    {
        $accessToken = "accessToken";
        $tokenType = "tokenType";
        $return = ['token' => $accessToken, 'type' => $tokenType];

        $this->customerSession->expects($this->any())->method('getApiAccessToken')->willReturn($accessToken);
        $this->customerSession->expects($this->any())->method('setApiAccessType')->willReturnSelf();
        $this->customerSession->expects($this->any())->method('setApiAccessToken')->with($accessToken);
        $this->customerSession->expects($this->any())->method('getApiAccessType')->willReturn($tokenType);
        $this->customerSession->expects($this->any())->method('getOnBehalfOf')->willReturn(null);

        $this->assertEquals($return, $this->data->getApiToken());
    }

    public function testGetApiTokenInStore() {
        $this->customerSession->expects($this->any())->method('getOnBehalfOf')
            ->willReturn('someValue');

        $this->assertNull($this->data->getApiToken());
    }

    public function testGetApiTokenOnGetTazToken()
    {
        $accessToken = "";
        $tokenType = "";
        $return = ['token' => $accessToken, 'type' => $tokenType];
        $this->customerSession->expects($this->any())->method('getApiAccessToken')->willReturn('');
        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn('ABC');
        $this->customerSession->expects($this->any())->method('getApiAccessToken')->willReturn($accessToken);
        $this->customerSession->expects($this->any())->method('getApiAccessType')->willReturn($tokenType);
        $this->customerSession->expects($this->any())->method('getOnBehalfOf')->willReturn(null);
        $this->assertEquals($return, $this->data->getApiToken());
    }
    public function testGetApiTokenOnGetTazTokenonNull()
    {
        $accessToken = "";
        $tokenType = "";
        $return = ['token' => $accessToken, 'type' => $tokenType];
        $this->customerSession->expects($this->any())->method('getApiAccessToken')->willReturn('');
        $this->punchoutHelper->expects($this->any())->method('getTazToken')->willReturn('');
        $this->customerSession->expects($this->any())->method('getApiAccessToken')->willReturn($accessToken);
        $this->customerSession->expects($this->any())->method('getApiAccessType')->willReturn($tokenType);
        $this->assertEquals($return, $this->data->getApiToken());
    }
    public function testGetGateToken()
    {
        $accessToken = null;
        $this->customerSession->expects($this->any())->method('getAuthGatewayToken')->willReturn($accessToken);
        $this->assertEquals($accessToken, $this->data->getGateToken());
    }
    public function testGetProductAttributeName()
    {
        $this->attributeSetRepositoryInterfaceMock->expects($this->any())
        ->method('get')->willReturn($this->attributeSetInterfaceMock);

        $this->attributeSetInterfaceMock->expects($this->any())->method('getAttributeSetName')->willReturn("Default");
        $this->assertEquals("Default", $this->data->getProductAttributeName(2));
    }

    public function testProductAttributeNameIsReturnedWhenToggleEnabled()
    {
        $attributeSetId = 12;
        $attributeSetName = 'PrintOnDemand';

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('hawks_d_227849_performance_improvement_checkout_product_load')
            ->willReturn(true);

        $attributeSetMock = $this->createMock(AttributeSetInterface::class);
        $attributeSetMock->method('getAttributeSetName')->willReturn($attributeSetName);

        $this->attributeSetRepositoryInterfaceMock->method('get')->with($attributeSetId)->willReturn($attributeSetMock);

        $result = $this->data->getProductAttributeName($attributeSetId);

        $this->assertEquals($attributeSetName, $result);
    }

    public function testProductAttributeNameIsReturnedWhenToggleEnabledAndNotGetTwice()
    {
        $attributeSetId = 12;
        $attributeSetName = 'PrintOnDemand';

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('hawks_d_227849_performance_improvement_checkout_product_load')
            ->willReturn(true);

        $attributeSetMock = $this->createMock(AttributeSetInterface::class);
        $attributeSetMock->method('getAttributeSetName')->willReturn($attributeSetName);

        $this->attributeSetRepositoryInterfaceMock->expects($this->once())
            ->method('get')
            ->with($attributeSetId)
            ->willReturn($attributeSetMock);

        $result = $this->data->getProductAttributeName($attributeSetId);
        /**
         * Second request, no need to call repository again
         */
        $this->data->getProductAttributeName($attributeSetId);
        $this->assertEquals($attributeSetName, $result);
    }

    public function testEmptyStringIsReturnedWhenAttributeSetNotFound()
    {
        $attributeSetId = 999;

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('hawks_d_227849_performance_improvement_checkout_product_load')
            ->willReturn(true);

        $this->attributeSetRepositoryInterfaceMock->method('get')
            ->with($attributeSetId)
            ->willThrowException(new NoSuchEntityException(__('Not found')));

        $this->logger->expects($this->once())->method('error')
            ->with($this->stringContains('Attribute set not found for ID: 999'));

        $result = $this->data->getProductAttributeName($attributeSetId);

        $this->assertEquals('', $result);
    }

    public function testProductAttributeNameIsReturnedWhenToggleDisabled()
    {
        $attributeSetId = 12;
        $attributeSetName = 'PrintOnDemand';

        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('hawks_d_227849_performance_improvement_checkout_product_load')
            ->willReturn(false);

        $attributeSetMock = $this->createMock(AttributeSetInterface::class);
        $attributeSetMock->method('getAttributeSetName')->willReturn($attributeSetName);

        $this->attributeSetRepositoryInterfaceMock->method('get')->with($attributeSetId)->willReturn($attributeSetMock);

        $result = $this->data->getProductAttributeName($attributeSetId);

        $this->assertEquals($attributeSetName, $result);
    }

    /*
     * Test getFCLCustomerLoggedInInfo function
     */
    public function testGetFCLCustomerLoggedInInfo()
    {
        $expectedResult = [
            "first_name" => "Test",
            "last_name" => "Test",
            "contact_number" => "12346789",
            "contact_ext" => "01",
            "email_address" => "test@gmil.com",
        ];
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerSessionFactory->expects($this->any())->method('getFirstname')->willReturn("Test");
        $this->customerSessionFactory->expects($this->any())->method('getLastname')->willReturn("Test");
        $this->customerSessionFactory->expects($this->any())->method('getContactNumber')->willReturn("12346789");
        $this->customerSessionFactory->expects($this->any())->method('getContactExt')->willReturn("01");
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn("test@gmil.com");

        $this->assertSame($expectedResult, $this->data->getFCLCustomerLoggedInInfo());
    }

        /*
     * Test getFCLCustomerLoggedInInfo function
     */
    public function testGetFCLCustomerLoggedInithecondaryEmail()
    {
        $expectedResult = [
            "first_name" => "Test",
            "last_name" => "Test",
            "contact_number" => "123456789",
            "contact_ext" => "01",
            "email_address" => "tst@gmail.com",
        ];
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerSessionFactory->expects($this->any())->method('getFirstname')->willReturn("Test");
        $this->customerSessionFactory->expects($this->any())->method('getSecondaryEmail')->willReturn("tst@gmail.com");
        $this->customerSessionFactory->expects($this->any())->method('getLastname')->willReturn("Test");
        $this->customerSessionFactory->expects($this->any())->method('getContactNumber')->willReturn("123456789");
        $this->customerSessionFactory->expects($this->any())->method('getContactExt')->willReturn("01");
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn("test@gmail.com");

        $this->assertSame($expectedResult, $this->data->getFCLCustomerLoggedInInfo());
    }

    /*
     * Test getFCLCustomerLoggedInInfo with exception function
     */
    public function testGetFCLCustomerLoggedInInfoWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willThrowException($exception);

        $this->assertSame(null, $this->data->getFCLCustomerLoggedInInfo());
    }

    /*
     * Test isCommercialCustomer() function
     * TestUnit covered in story B-1163715
     */
    public function testIsCommercialCustomerWithFalse()
    {
        $return = false;
        $this->sdeHelper->expects($this->any())->method('sdeCommercialCheckout')->willReturn(true);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->assertEquals($return, $this->data->isCommercialCustomer());
    }

    /*
     * Test isCommercialCustomer() function
     * Test Unit covered in story B-1163715
     */
    public function testIsCommercialCustomerWithTrue()
    {
        $return = false;
        $this->sdeHelper->expects($this->any())->method('sdeCommercialCheckout')->willReturn(false);
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->assertEquals($return, $this->data->isCommercialCustomer());
    }

    public function testGetGateTokenForSde()
    {
        $accessToken = '{"accessToken":"accessToken","tokenType":"tokenType"}';
        $this->punchoutHelper->expects($this->any())->method('getAuthGatewayToken')->willReturn($accessToken);
        $this->customerSession->expects($this->any())->method('setGatewayToken')->with($accessToken);
        $this->customerSession->expects($this->any())->method('getGatewayToken')->willReturn($accessToken);

        $this->assertEquals($accessToken, $this->data->getGateToken());
    }

    public function testGetAllowedDeliveryOptions()
    {
        $companyId = 1;
        $allowedDeliveryOptions = ["LOCAL_DELIVERY_AM", "LOCAL_DELIVERY_PM"];
        $allowedDeliveryOptionsEncoded = "['LOCAL_DELIVERY_AM','LOCAL_DELIVERY_PM']";
        $allowedDeliveryOptionsResult = ['LOCAL_DELIVERY_AM' => 0, 'LOCAL_DELIVERY_PM' => 1];
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())
        ->method('getCustomer')->willReturn($this->customerSession);
        $this->customerSession->expects($this->any())->method('getId')->willReturn(4);
        $this->customerRepository->expects($this->any())->method('getById')->willReturn($this->customerInterface);
        $this->customerInterface->expects($this->any())->method('getId')->willReturn(2);
        $this->companyInterface->expects($this->any())
        ->method('getAllowedDeliveryOptions')->willReturn($allowedDeliveryOptionsEncoded);
        $this->json->expects($this->once())->method('unserialize')->willReturn($allowedDeliveryOptions);
        $this->assertEquals($allowedDeliveryOptionsResult, $this->data->getAllowedDeliveryOptions());
    }

    public function testGetAllowedDeliveryOptionsWithGetIsSdeStoreAsFalse()
    {
        $allowedDeliveryOptionsResult = [];
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())
            ->method('getCustomer')->willReturn($this->customerSession);
        $this->assertEquals($allowedDeliveryOptionsResult, $this->data->getAllowedDeliveryOptions());
    }

    /**
     * @test testGetAllowedDeliveryOptionsWithGetIsSdeStoreAsFalse
     */
    public function testGetAllowedDeliveryOptionsWithToggleShippingIssueFix()
    {
        $allowedDeliveryOptionsResult = [];
        // $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->testIsCommercialCustomerWithTrue();
        $this->baseAuthMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->testGetOrCreateCustomerSession();
        $this->assertEquals($allowedDeliveryOptionsResult, $this->data->getAllowedDeliveryOptions());
    }

    /**
     * Test to get rate request with signature options as special services for sde
     */
    public function testGetRateRequestShipmentSpecialServices()
    {
        $this->customerInterface->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $signatureOptions = [
          'specialServiceType' => 'SIGNATURE_OPTION',
          'specialServiceSubType' => 'DIRECT',
          'displayText' => 'Direct Signature Required',
          'description' => 'Direct Signature Required',
        ];
        $specialServices = [$signatureOptions];
        $this->quoteDataHelper->expects($this->any())
        ->method('getDirectSignatureOptionsParams')->willReturn($signatureOptions);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals($specialServices, $this->data->getRateRequestShipmentSpecialServices());
    }

    /**
     * Test to get rate request without signature options
     */
    public function testGetRateRequestShipmentSpecialServicesForNonSde()
    {
        $specialServices = [];
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->assertEquals($specialServices, $this->data->getRateRequestShipmentSpecialServices());
    }

    public function testisEproCustomer()
    {
        $this->testGetCompanySite();
        $this->baseAuthMock->expects($this->any())
            ->method('getCompanyAuthenticationMethod')
            ->willReturn('punchout');
        $this->assertEquals(true, $this->data->isEproCustomer());
    }

    public function testisEproCustomerWithoutFalse()
    {
        $this->testGetCompanySiteisCommercialCustomerAsFalse();
        $this->assertEquals(false, $this->data->isEproCustomer());
    }

    public function testgetProductCustomAttributeValue()
    {
        $productId = 2;
        $this->marketplaceCheckoutHelper->expects($this->any())->method('isEssendantToggleEnabled')->willReturn(true);
        $this->productRepositoryMock->expects($this->any())->method('getById')
            ->with($productId)->willReturn($this->product);
        $this->product->expects($this->any())->method('getData')->willReturnSelf();
        $this->assertEquals($this->product, $this->data->getProductCustomAttributeValue(2, '123'));
    }

    /**
     * Test Case for updateQuotePrice
     */
    public function testupdateQuotePriceWithToggle()
    {
        $response = '{
        "currency":"USD",
        "rateQuoteDetails":[
           {
              "productLines":[
                 {
                    "instanceId":"38659",
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
                    "estimatedDeliveryLocalTime":"2022-12-01T16:30:00",
                    "estimatedShipDate":"2022-11-30",
                    "deliveryLinePrice":"$37.31",
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
                       "serviceType":"STANDARD_OVERNIGHT"
                    },
                    "deliveryRetailPrice":"$37.31",
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
              "grossAmount":"$72.30",
              "totalDiscountAmount":"$0.00",
              "netAmount":"$72.30",
              "taxableAmount":"$72.30",
              "taxAmount":"$2.45",
              "totalAmount":"$74.75",
              "estimatedVsActual":"ACTUAL"
           }
        ]
     }';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->testisEproCustomerWithoutFalse();
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->assertNull($this->data->updateQuotePrice($this->quoteMock, json_decode($response, true), 'MGT001'));
    }

    /**
     * Test case for setUnitPriceForEPRO
     */
    public function testSetUnitPriceForEPRO()
    {
        $this->testisEproCustomer();
        $this->assertNotNull($this->data->setUnitPriceForEPRO(23.03456));
    }

    /**
     * Test case for updateCartItemPrice
     */
    public function testUpdateCartItemPrice()
    {
        $response = '{
            "currency":"USD",
            "rateDetails":[
               {
                  "productLines":[
                     {
                        "instanceId":"38659",
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
                        "estimatedDeliveryLocalTime":"2022-12-01T16:30:00",
                        "estimatedShipDate":"2022-11-30",
                        "deliveryLinePrice":"$37.31",
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
                           "serviceType":"STANDARD_OVERNIGHT"
                        },
                        "deliveryRetailPrice":"$37.31",
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
                  "grossAmount":"$72.30",
                  "totalDiscountAmount":"$0.00",
                  "netAmount":"$72.30",
                  "taxableAmount":"$72.30",
                  "taxAmount":"$2.45",
                  "totalAmount":"$74.75",
                  "estimatedVsActual":"ACTUAL"
               }
            ]
         }';
        $this->testSetUnitPriceForEPRO();
        $this->itemMock->expects($this->any())->method('getItemId')->willreturn('38659');
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setDiscount')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setBaseRowTotalInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotalInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setPrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setPriceInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setBasePrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setBasePriceInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setCustomPrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setOriginalCustomPrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('getMiraklOfferId')->willReturn(true);
        $this->assertNull($this->data->updateCartItemPrice([$this->itemMock], json_decode($response, true)));
    }

    /**
     * Test case for updateCartItemPrice
     */
    public function testGetProductLinesDetails()
    {
        $response = [];
        $this->assertNotNull($this->data->getProductLinesDetails($response));
    }

    /**
     * Test case for updateCartItemPriceWithToggleOn
     */
    public function testUpdateCartItemPriceWithToggleOn()
    {
        $response = '{
            "currency":"USD",
            "rateQuoteDetails":[
               {
                  "productLines":[
                     {
                        "instanceId":"38659",
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
                        "estimatedDeliveryLocalTime":"2022-12-01T16:30:00",
                        "estimatedShipDate":"2022-11-30",
                        "deliveryLinePrice":"$37.31",
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
                           "serviceType":"STANDARD_OVERNIGHT"
                        },
                        "deliveryRetailPrice":"$37.31",
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
                  "grossAmount":"$72.30",
                  "totalDiscountAmount":"$0.00",
                  "netAmount":"$72.30",
                  "taxableAmount":"$72.30",
                  "taxAmount":"$2.45",
                  "totalAmount":"$74.75",
                  "estimatedVsActual":"ACTUAL"
               }
            ]
         }';
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->testisEproCustomerWithoutFalse();
        $this->itemMock->expects($this->any())->method('getItemId')->willreturn('38659');
        $this->itemMock->expects($this->any())->method('setBaseRowTotal')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotal')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setDiscount')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setBaseRowTotalInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setRowTotalInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setPrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setPriceInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setBasePrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setBasePriceInclTax')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setCustomPrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setOriginalCustomPrice')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('setIsSuperMode')->willreturnSelf();
        $this->itemMock->expects($this->any())->method('getMiraklOfferId')->willReturn(true);

        $this->assertNull($this->data->updateCartItemPrice([$this->itemMock], json_decode($response, true)));
    }

    public function testsetIsOutSourceCheck()
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
        $fxoProduct = [
            'fxoProductInstance' => [
                'productConfig' => [
                    'product' => [
                        'isOutSourced' => 1
                    ]
                ]
            ]
        ];
        $this->jsonValidator->expects($this->any())->method('isValid')->willReturn(true);
        $this->json->expects($this->any())->method('unserialize')->willReturn($fxoProduct);
        $this->assertNull($this->data->setIsOutSourceCheck($decodedValue['external_prod'][0]));
    }

    public function testsetIsOutSourceCheckWithOutOutSourced()
    {
        $decodedValue = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'isOutSourced' => 1,
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => ''
                    ],
                ],
            ],
        ];
        $fxoProduct = [
            'fxoProductInstance' => [
                'productConfig' => [
                    'product' => [
                        'isOutSourced' => 1
                    ]
                ]
            ]
        ];
        $this->jsonValidator->expects($this->any())->method('isValid')->willReturn(true);
        $this->json->expects($this->any())->method('unserialize')->willReturn($fxoProduct);
        $this->assertNull($this->data->setIsOutSourceCheck($decodedValue['external_prod'][0]));
    }

    /**
     * Test case for isOurSourced
     */
    public function testisOurSourced()
    {
        $decodedValue = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'isOutSourced' => 1,
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => ''
                    ],
                ],
            ],
        ];
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getAllItems')->willReturn([0 => $this->itemMock]);
        $this->itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->itemOptionMock);
        $this->itemOptionMock->expects($this->any())->method('getValue')->willReturn($decodedValue);
        $this->jsonValidator->expects($this->any())->method('isValid')->willReturn(true);
        $this->json->expects($this->any())->method('unserialize')->willReturn($decodedValue);
        $this->assertNotNull($this->data->isOurSourced());
    }

    /**
     * Test case for isOurSourcedWithException
     */
    public function testisOurSourcedWithException()
    {
        $exception = new Exception();
        $decodedValue = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'isOutSourced' => 1,
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => ''
                    ],
                ],
            ],
        ];
        $this->cartFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->assertNotNull($this->data->isOurSourced());
    }

    /**
     * Test getComanyLogo
     * B-1473176
     */
    public function testGetCompanyLogo()
    {
        $companyId = 9;
        $companyLogo = '{"name":"Screenshot_from_2022-01-11_10-32-33.png","full_path":"Screenshot from 2022-01-11 10-32-33.png","type":"image\/png","tmp_name":"\/tmp\/phpr9V0u2","error":"0","size":"134446","path":"\/var\/www\/html\/staging3.office.fedex.com\/pub\/media\/Company\/Logo","file":"Screenshot_from_2022-01-11_10-32-33.png","url":"\/media\/Company\/Logo\/Screenshot_from_2022-01-11_10-32-33.png","previewType":"image","id":"U2NyZWVuc2hvdF9mcm9tXzIwMjItMDEtMTFfMTAtMzItMzMucG5n"}';
        $companyLogoArray = ['url' => '/media/wysiwyg/FedEx_Office_Logo.png'];
        $baseUrl = 'https://staging3.office.fedex.com/media/';

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn($companyId);
        $this->customerInterface->expects($this->any())
            ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getCompanyLogo')->willReturn($companyLogo);
        $this->json->expects($this->any())->method('unserialize')->willReturn($companyLogoArray);

        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->testResize();
        $this->assertNotNull($this->data->getCompanyLogo());
    }

    /**
     * Test case for updateDateTimeFormat
     */
    public function testUpdateDateTimeFormat()
    {
        $localTime = "2023-04-20T17:00:00";
        $this->assertNotNull($this->data->updateDateTimeFormat($localTime));
    }

    /**
     * Test getMediaUrl
     */
    public function testGetMediaUrl() {
        $baseUrl = 'https://staging3.office.fedex.com/media/';
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->assertNotNull($this->data->getMediaUrl());
    }

    public function testGetConfiguratorUrl()
    {
        $configuratorUrl = "https://wwwtest.fedex.com/app/ondemand";
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn($configuratorUrl);
        $this->assertEquals($configuratorUrl, $this->data->getConfiguratorUrl());
    }

    /**
     * Test getConfigurationValue
     *
     * @return void
     */
    public function testGetConfigurationValue()
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn(true);
        $this->assertEquals(true, $this->data->getConfigurationValue('test'));
    }

    /**
     * Test getToggleConfigurationValue
     *
     * @return void
     */
    public function testGetToggleConfigurationValue()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(true, $this->data->getToggleConfigurationValue('test'));
    }

    public function testGetOnDemandCompInfo()
    {
        $companyId = 1;
        $companyInfo = [
            'company_id' => 1,
            'login_method' => 'commercial_store_self',
            'is_sensitive_data_enabled' => false,
            'logoutUrl' => 'test',
            'company_url_extension' => 'test',
            'adobe_analytics' => true
        ];
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')->willReturn($companyId);
        $this->companyInterface->expects($this->any())->method('getStorefrontLoginMethodOption')
        ->willReturn('commercial_store_self');
        $this->companyInterface->expects($this->any())->method('getIsSensitiveDataEnabled')->willReturn(false);
        $this->companyInterface->expects($this->any())->method('getSsoLogoutUrl')->willReturn('test');
        $this->companyInterface->expects($this->any())->method('getCompanyUrlExtention')->willReturn('test');
        $this->companyInterface->expects($this->any())->method('getAdobeAnalytics')->willReturn(true);

        $this->assertEquals($companyInfo, $this->data->getOnDemandCompInfo());
    }

    public function testIsSelfRegCustomerAdminUser()
    {
        $this->testGetOnDemandCompInfo();
        $this->storeManager->expects($this->any())->method('getWebsite')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getWebsiteId')->willReturn(1);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn('heena.lnu.osv@fedex.com');
        $this->selfRegHelperMock->expects($this->any())->method('checkCustomerIsCompanyAdmin')->willReturn(true);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('explorers_epro_customer_admin')
            ->willReturn(true);
    }

    public function testIsSelfRegCustomerAdminUserWithToggleFalse()
    {
        $this->testGetOnDemandCompInfo();
        $this->storeManager->expects($this->any())->method('getWebsite')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getWebsiteId')->willReturn(1);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn('heena.lnu.osv@fedex.com');
        $this->selfRegHelperMock->expects($this->any())->method('checkCustomerIsCompanyAdmin')->willReturn(true);
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('explorers_epro_customer_admin')
            ->willReturn(false);
        //$this->assertTrue($this->data->isSelfRegCustomerAdminUser());
       // $this->assertNull($this->data->isSelfRegCustomerAdminUser());
    }

    public function testIsSelfRegCustomerAdminUserWithFalse()
    {
        $this->assertFalse($this->data->isSelfRegCustomerAdminUser());
    }

    public function testIsCompanyAdminUser()
    {
        $this->testGetOnDemandCompInfo();
        $this->storeManager->expects($this->once())->method('getWebsite')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn('heena.lnu.osv@fedex.com');
        $this->selfRegHelperMock->expects($this->any())->method('checkCustomerIsCompanyAdmin')->willReturn(true);

        $this->assertTrue($this->data->isCompanyAdminUser());
    }

    public function testIsCompanyAdminUserWithFalse()
    {
        $this->assertFalse($this->data->isCompanyAdminUser());
    }

    /*
    * Test case for resize
    */
    public function testResize()
    {
        $image = 'test.jpg';
        $width = 100;
        $height = 100;

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryRead')
            ->willReturn($this->directoryReadMock);

        $this->directoryReadMock->expects($this->any())
            ->method('getAbsolutePath')
            ->willReturn(true);

        $imageResizeMock = $this->createMock(\Magento\Framework\Image::class);
        $this->imageFactoryMock->method('create')->willReturn($imageResizeMock);

        $imageResizeMock->expects($this->any())->method('save');

        $response = 'https://staging3.office.fedex.com/media/resize/100/test.jpg';
        // Add additional expectations and method stubs as needed

        $result = $this->data->resize($image, $width, $height);
        $this->assertNotNull($response,$result);
    }


    /*
    * Test case for get company name
    */
    public function testGetCompanyName()
    {
        $companyId = 1;
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getCompanyName')->willReturn('company');
        $this->assertEquals('company', $this->data->getCompanyName());
    }

    /*
    * Test case if company name is blank
    */
    public function testGetCompanyNameWithoutCompanyName()
    {
        $companyId = 1;
        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(2);
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(4);
        $this->customerInterface->expects($this->any())
        ->method('getExtensionAttributes')->willReturn($this->customerExtensionInterfaceMock);
        $this->customerExtensionInterfaceMock->expects($this->any())
        ->method('getCompanyAttributes')->willReturn($this->companyCustomerInterfaceMock);

        $this->companyCustomerInterfaceMock->expects($this->any())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getCompanyName')->willReturn('');
        $this->assertEquals(null, $this->data->getCompanyName());
    }

    /*
    * Test case if commercial user is false
    */
    public function testGetCompanyNameisCommercialCustomerAsFalse()
    {
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn('');
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getId')->willReturn(0);

        $this->assertEquals(null, $this->data->getCompanyName());
    }
    /**
     * Test Is Customer Ero Admin User
     */
    public function testIsCustomerEproAdminUser()
    {
        $companyId = 1;
        $this->customerSession->expects($this->any())->method('getCustomerCompany')->willReturn($companyId);
        $this->companyRepository->expects($this->any())->method('get')->willReturn($this->companyInterface);
        $this->companyInterface->expects($this->any())->method('getId')->willReturn($companyId);
        $this->companyInterface->expects($this->any())->method('getStorefrontLoginMethodOption')
        ->willReturn('commercial_store_epro');
        $this->storeManager->expects($this->once())->method('getWebsite')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn('heena.lnu.osv@fedex.com');
        $this->selfRegHelperMock->expects($this->any())->method('checkCustomerIsCompanyAdmin')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);

        $this->assertEquals(true, $this->data->isCustomerEproAdminUser());
    }
    /**
     * Test Is Customer Ero Admin User with false
     */
    public function testIsCustomerEproAdminUserwithFalse()
    {
        $this->testGetOnDemandCompInfo();
        $this->storeManager->expects($this->once())->method('getWebsite')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->customerSessionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getCustomer')->willReturnSelf();
        $this->customerSessionFactory->expects($this->any())->method('getEmail')->willReturn('heena.lnu.osv@fedex.com');
        $this->selfRegHelperMock->expects($this->any())->method('checkCustomerIsCompanyAdmin')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())
        ->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(false, $this->data->isCustomerEproAdminUser());
    }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->customerInterface->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterface);

        $result = $this->data->getOrCreateCustomerSession();
        $this->assertSame($this->customerSession, $result);
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

    /**
     * Get Toggle Value epro Custom doc for migrated Document Toggle
     * @return void
     */
    public function testGetEproMigratedCustomDocToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->getEproMigratedCustomDocToggle());
    }



}
