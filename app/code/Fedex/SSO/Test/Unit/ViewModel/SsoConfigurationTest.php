<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SSO\Test\Unit\ViewModel;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnhancedProfile\Helper\Account;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Test\Unit\Plugin\Person;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Laminas\Uri\Http;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Fedex\Delivery\Helper\Data as CompanyHelper;
use Magento\Company\Api\CompanyRepositoryInterface;

/**
 * Test class for SsoConfiguration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SsoConfigurationTest extends TestCase
{
    /**
     * @var never[]
     */
    protected $sizesArray;
    protected $urlInterface;
    protected $customerRepository;
    protected $accountManagement;
    protected $cookieManagerInterface;
    protected $sdeHelper;
    protected $selfregHelper;
    protected $accountHelper;
    protected $dataHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * FedEx Section Id
     */
    public const XML_PATH_FEDEX_SSO = 'sso/';

    /**
     * FCL My Profile Id
     */
    public const FCL_MY_PROFILE_URL = 'sso/general/fcl_my_profile_url';

    /**
     * @var $ssoConfigurationData SsoConfiguration
     */
    protected $ssoConfigurationData;

    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * @var Http $zendUriMock
     */
    protected $zendUriMock;

    /**
     * @var LoggerInterface $loggerInterfaceMock
     */
    protected $loggerInterfaceMock;

    /**
     * @var DeliveryHelper $deliveryHelper
     */
    protected $deliveryHelper;

    /**
     * @var PublicCookieMetadata $publicCookieMetadata
     */
    protected $publicCookieMetadata;

    /**
     * @var CookieMetadataFactory $loggerInterfaceMock
     */
    protected $cookieMetadataFactory;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var Data $dataHelper
     */
    protected Data $dataHelper;

    /**
     * @var ToggleConfig
     */
    protected ToggleConfig $toggleConfigMock;

    /**
     * @var RequestInterface $request
     */
    protected $request;

    /**
     * @var FuseBidHelper $fuseBidHelper
     */
    protected $fuseBidHelper;

    /**
     * @var CompanyHelper $companyHelper
     */
    protected $companyHelper;

    /**
     * @var CompanyRepositoryInterface $companyMock
     */
    protected $companyMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->sizesArray = [];

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical'])
            ->getMockForAbstractClass();

        $this->zendUriMock = $this->getMockBuilder(Http::class)
            ->setMethods(
                [
                    'parse', 'getScheme', 'getHost',
                ]
            )
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(
                [
                    'getValue',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomer',
                    'getId',
                    'getCustomerCompany',
                    'getFirstname',
                    'getCustomerId',
                    'getLoginErrorCode'
                ]
            )
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(
                [
                    'getCurrentUrl',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(
                [
                    'getById',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->accountManagement = $this->getMockBuilder(AccountManagementInterface::class)
            ->setMethods(
                [
                    'getDefaultBillingAddress',
                    'getDefaultShippingAddress',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cookieManagerInterface = $this->getMockBuilder(CookieManagerInterface::class)
            ->setMethods(
                [
                    'getCookie',
                    'deleteCookie',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->publicCookieMetadata = $this->getMockBuilder(PublicCookieMetadata::class)
            ->setMethods(
                [
                    'setDomain',
                    'setPath',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieMetadataFactory = $this->getMockBuilder(CookieMetadataFactory::class)
            ->setMethods(
                [
                    'createPublicCookieMetadata',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(
                [
                    'isCommercialCustomer',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(
                [
                    'getGroup',
                    'getCode',
                    'getStoreId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(
                [
                    'getIsSdeStore',
                    'getLogoutUrl',
                    'getIsRequestFromSdeStoreFclLogin'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfregHelper = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['getIsRequestFromSdeStoreFclLogin',
            'isSelfRegCustomerWithFclEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountHelper = $this->getMockBuilder(Account::class)
            ->setMethods(['isRetail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods(['getFCLCookieNameToggle','getFCLCookieConfigValue','isSSOlogin'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fuseBidHelper = $this->getMockBuilder(FuseBidHelper::class)
            ->setMethods(['isFuseBidGloballyEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->setMethods(['getAssignedCompany'])
            ->disableOriginalConstructor()
            ->getMock();    
            
        $this->companyMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['getSsoGroup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();        
        $this->objectManager = new ObjectManager($this);
        $this->ssoConfigurationData = $this->objectManager->getObject(
            SsoConfiguration::class,
            [
                'customerSession' => $this->customerSession,
                'scopeConfig' => $this->scopeConfigMock,
                'urlInterface' => $this->urlInterface,
                'customerRepository' => $this->customerRepository,
                'accountManagement' => $this->accountManagement,
                'cookieManagerInterface' => $this->cookieManagerInterface,
                'zendUri' => $this->zendUriMock,
                'logger' => $this->loggerInterfaceMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactory,
                'deliveryHelper' => $this->deliveryHelper,
                'storeManager' => $this->storeManager,
                'sdeHelper' => $this->sdeHelper,
                'selfReg' => $this->selfregHelper,
                'accountHelper' => $this->accountHelper,
                'dataHelper' => $this->dataHelperMock,
                'toggleConfig' => $this->toggleConfigMock,
                'request' => $this->request,
                'fuseBidHelper' => $this->fuseBidHelper,
                'companyHelper' =>  $this->companyHelper
            ]
        );
    }

    /**
     * Test getCurrentUrlPath
     *
     * @return void
     */
    public function testGetCurrentUrlPath()
    {
        $currentUrl = "https://staging3.office.fedex.com/default/flyers.html";
        $this->zendUriMock->expects($this->any())
            ->method('parse')
            ->with($currentUrl)
            ->willReturnSelf();

        $this->zendUriMock->expects($this->any())
            ->method('getHost')
            ->willReturn("staging3.office.fedex.com");

        $this->zendUriMock->expects($this->any())
            ->method('getScheme')
            ->willReturn("https");

        $expectedResult = "/default/flyers.html";
        $this->assertEquals($expectedResult, $this->ssoConfigurationData->getCurrentUrlPath($currentUrl));
    }

    /**
     * Test getCurrentUrlPath with exception
     *
     * @return void
     */
    public function testGetCurrentUrlPathWithException()
    {
        $currentUrl = "https://staging3.office.fedex.com/default/flyers.html";
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->zendUriMock->expects($this->any())
            ->method('parse')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->ssoConfigurationData->getCurrentUrlPath($currentUrl));
    }

    /**
     * Test getGeneralConfig
     *
     * @return void
     */
    public function testgetConfigValue()
    {
        $mixedValue = 1;
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(self::XML_PATH_FEDEX_SSO . "general/is_enable", ScopeInterface::SCOPE_STORE)
            ->willReturn($mixedValue);
        $this->assertEquals($mixedValue, $this->ssoConfigurationData->getGeneralConfig("is_enable"));
    }

    /**
     * Test getLoginMockToggle
     *
     * @return void
     */
    public function testgetLoginMockToggle()
    {
        $mixedValue = 1;
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with("wiremock_service/selfreg_wiremock_group/fcl_login_api_wiremock_enable", ScopeInterface::SCOPE_STORE)
            ->willReturn($mixedValue);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getStoreId')->willReturn('108');
        $this->assertEquals($mixedValue, $this->ssoConfigurationData->getLoginMockToggle());
    }

    /**
     * Test getWebCookieConfig
     *
     * @return void
     */
    public function testGetWebCookieConfig()
    {
        $timeOutTime = 3600;
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn($timeOutTime);
        $this->assertEquals($timeOutTime, $this->ssoConfigurationData->getWebCookieConfig("cookie_lifetime"));
    }

    /**
     * Test getLoginPopupConfig
     *
     * @return void
     */
    public function testGetLoginPopupConfig()
    {
        $mixedValue = 'Create User';
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn($mixedValue);
        $this->assertEquals($mixedValue, $this->ssoConfigurationData->getLoginPopupConfig("login_popup_message"));
    }

    /**
     * Test getCheckoutLoginPopupConfig
     *
     * @return void
     */
    public function testCheckoutGetLoginPopupConfig()
    {
        $mixedValue = 'Create User';
        $this->scopeConfigMock->expects($this->any())->method('getValue')->willReturn($mixedValue);
        $this->assertEquals(
            $mixedValue,
            $this->ssoConfigurationData->getCheckoutLoginPopupConfig("checkout_login_popup_message")
        );
    }

    /**
     * Test isFclCustomer
     *
     * @return void
     */
    public function testIsFclCustomer()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturnSelf();

        $this->accountHelper->expects($this->any())
            ->method('isRetail')
            ->willReturn(true);

        $this->assertEquals(true, $this->ssoConfigurationData->isFclCustomer());
    }

    /**
     * Test isFclCustomer without login
     *
     * @return void
     */
    public function testIsFclCustomerWithoutLogin()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(0);

        $this->assertEquals(false, $this->ssoConfigurationData->isFclCustomer());
    }

    /**
     * Test getFclCustomerName
     *
     * @return void
     */
    public function testGetFclCustomerName()
    {
        $customerName = 'Jon Deo';
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $customer->method('getFirstname')
            ->willReturn('test');

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($customer);

        $this->assertEquals('test', $this->ssoConfigurationData->getFclCustomerName());
    }

    /**
     * Test getFclCustomerName for without login customer
     *
     * @return void
     */
    public function testGetFclCustomerNameWithoutLogin()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomer')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals(false, $this->ssoConfigurationData->getFclCustomerName());
    }

    /**
     * Test getCurrentUrl
     *
     * @return void
     */
    public function testGetCurrentUrl()
    {
        $currentUrl = 'https://staging3.office.fedex.com';

        $this->urlInterface->expects($this->any())->method('getCurrentUrl')->willReturn($currentUrl);

        $this->assertEquals($currentUrl, $this->ssoConfigurationData->getCurrentUrl());
    }

    /**
     * Test getOrderSuccessUrl
     *
     * @return void
     */
    public function testGetOrderSuccessUrl()
    {
        $orderSuccessUrl = 'https://staging3.office.fedex.com/default/submitorder/index/ordersuccess';

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($orderSuccessUrl);

        $this->assertEquals($orderSuccessUrl, $this->ssoConfigurationData->getOrderSuccessUrl());
    }

    /**
     * Test getHomeUrl
     *
     * @return void
     */
    public function testGetHomeUrl()
    {
        $homeUrl = 'https://staging3.office.fedex.com';

        $this->urlInterface->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($homeUrl);

        $this->assertEquals($homeUrl, $this->ssoConfigurationData->getHomeUrl());
    }

    /**
     * Test getFclMyProfileUrl
     *
     * @return void
     */
    public function testGetFclMyProfileUrl()
    {
        $FclUrl = 'https://www.fedex.com/profile/';
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(self::FCL_MY_PROFILE_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn($FclUrl);
        $this->assertEquals($FclUrl, $this->ssoConfigurationData->getFclMyProfileUrl());
    }

    /**
     * Test getDefaultBillingAddressById
     *
     * @return void
     */
    public function testGetDefaultBillingAddressById()
    {
        $customerId = 355;
        $person = new Person('John Doe', 'j.doe@example.com');

        $this->accountManagement->expects($this->any())->method('getDefaultBillingAddress')->willReturn($person);
        $arrPersion = ['name' => 'John Doe', 'email' => 'j.doe@example.com'];
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultBillingAddressById($customerId));
    }

    /**
     * Test getDefaultBillingAddressById with no data
     *
     * @return void
     */
    public function testGetDefaultBillingAddressByIdWithNoData()
    {
        $customerId = 355;

        $this->accountManagement->expects($this->any())->method('getDefaultBillingAddress')->willReturn(0);
        $returnString = 'Customer has not set a default billing address.';
        $this->assertEquals($returnString, $this->ssoConfigurationData->getDefaultBillingAddressById($customerId));
    }

    /**
     * Test getDefaultBillingAddress
     *
     * @return void
     */
    public function testGetDefaultBillingAddress()
    {
        $person = new Person('John Doe', 'j.doe@example.com');

        $this->accountManagement->expects($this->any())->method('getDefaultBillingAddress')->willReturn($person);
        $arrPersion = ['name' => 'John Doe', 'email' => 'j.doe@example.com'];
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultBillingAddress());
    }

    /**
     * Test getDefaultBillingAddress with no data
     *
     * @return void
     */
    public function testGetDefaultBillingAddressWithNoData()
    {
        $this->accountManagement->expects($this->any())->method('getDefaultBillingAddress')->willReturn(0);
        $returnString = 'You have not set a default billing address.';
        $this->assertEquals($returnString, $this->ssoConfigurationData->getDefaultBillingAddress());
    }

    /**
     * Test getCustomCookie
     *
     * @return string
     */
    public function testGetCustomCookie()
    {
        $cookieName = 'test';

        $this->cookieManagerInterface
            ->expects($this->any())->method('getCookie')->willReturn($cookieName);

        $this->assertEquals($cookieName, $this->ssoConfigurationData->getCustomCookie($cookieName));
    }

    /**
     * Test deleteCustomCookie
     *
     * @return string
     */
    public function testDeleteCustomCookie()
    {
        $cookieName = 'test';

        $this->cookieMetadataFactory->expects($this->any())
            ->method('createPublicCookieMetadata')->willReturn($this->publicCookieMetadata);
        $this->publicCookieMetadata->expects($this->any())
            ->method('setDomain')->willReturnSelf();
        $this->publicCookieMetadata->expects($this->any())
            ->method('setPath')->willReturnSelf();
        $this->cookieManagerInterface
            ->expects($this->any())->method('deleteCookie')->willReturnSelf();

        $this->assertEquals(
            $this->cookieManagerInterface,
            $this->ssoConfigurationData->deleteCustomCookie($cookieName)
        );
    }

    /**
     * Test getDefaultShippingAddressById
     *
     * @return array
     */
    public function testGetDefaultShippingAddressById()
    {
        $customerId = 355;
        $person = new Person('John Doe', 'j.doe@example.com');

        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn($person);
        $arrPersion = ['name' => 'John Doe', 'email' => 'j.doe@example.com'];
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultShippingAddressById($customerId));
    }

    /**
     * Test getDefaultShippingAddressById with no data
     *
     * @return string
     */
    public function testGetDefaultShippingAddressByIddWithNoData()
    {
        $customerId = 355;

        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn(0);
        $returnString = 'Customer has not set a default shipping address.';
        $this->assertEquals($returnString, $this->ssoConfigurationData->getDefaultShippingAddressById($customerId));
    }

    /**
     * Test getDefaultShippingAddress
     *
     * @return void
     */
    public function testGetDefaultShippingAddress()
    {
        $person = new Person('Ravi', 'ravi5.kumar@infogain.com');

        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn($person);
        $arrPersion = ['name' => 'Ravi', 'email' => 'ravi5.kumar@infogain.com'];
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers')
            ->willReturn(true);
        $this->companyHelper->expects($this->once())->method('getAssignedCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->once())->method('getSsoGroup')->willReturn("Group01");
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultShippingAddress());
    }

    /**
     * Test getDefaultShippingAddress
     *
     * @return void
     */
    public function testGetDefaultShippingAddress2()
    {
        $arrPersion = __('You have not set a default shipping address.');
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers')
            ->willReturn(true);
        $this->companyHelper->expects($this->once())->method('getAssignedCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->once())->method('getSsoGroup')->willReturn("Group01");
        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn(null);
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultShippingAddress());
    }

     /**
     * Test getDefaultShippingAddress
     *
     * @return void
     */
    public function testGetDefaultShippingAddress3()
    {
        $arrPersion = __('');
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers')
            ->willReturn(true);
        $this->companyHelper->expects($this->once())->method('getAssignedCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->once())->method('getSsoGroup')->willReturn(null);
        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn(null);
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultShippingAddress());
    }

    /**
     * Test getDefaultShippingAddress
     *
     * @return void
     */
    public function testGetDefaultShippingAddress4()
    {
        $person = new Person('Ravi', 'ravi5.kumar@infogain.com');

        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn($person);
        $arrPersion = ['name' => 'Ravi', 'email' => 'ravi5.kumar@infogain.com'];
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('magegeeks_E_464167_ExposeEnhancedProfileTabsforSSOCustomers')
            ->willReturn(false);
        $this->assertEquals($arrPersion, $this->ssoConfigurationData->getDefaultShippingAddress());
    }

    /**
     * Test getDefaultShippingAddress with no data
     *
     * @return void
     */
    public function testGetDefaultShippingAddressWithNoData()
    {
        $this->accountManagement->expects($this->any())->method('getDefaultShippingAddress')->willReturn(0);
        $this->assertNotNull($this->ssoConfigurationData->getDefaultShippingAddress());
    }

    /**
     * Test getFclCustomerInfo
     *
     * @return void
     */
    public function testGetFclCustomerInfo()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($customer);
        $this->assertNotNull($this->ssoConfigurationData->getFclCustomerInfo());
    }

    /**
     * Test getFclCustomerInfo
     *
     * @return void
     */
    public function testGetFclCustomerInfoWithFalseResponse()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn('');

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerRepository->expects($this->any())->method('getById')->willReturn($customer);
        $this->assertNotNull($this->ssoConfigurationData->getFclCustomerInfo());
    }

    /**
     * Test fclCustomerAccountIcon
     *
     * @return void
     */
    public function testFclCustomerAccountIcon()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturnSelf();

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(null);

        $this->assertEquals(true, $this->ssoConfigurationData->fclCustomerAccountIcon());
    }

    /**
     * Test testFclCustomerAccountIconWithRetail
     *
     * @return void
     */
    public function testFclCustomerAccountIconWithRetail()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn('');

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(null);

        $this->assertEquals(true, $this->ssoConfigurationData->fclCustomerAccountIcon());
    }

    /**
     * Test testFclCustomerAccountIconWithEpro
     *
     * @return void
     */
    public function testFclCustomerAccountIconWithEpro()
    {
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);

        $this->customerSession->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(1);

        $this->assertEquals(false, $this->ssoConfigurationData->fclCustomerAccountIcon());
    }

    /**
     * Test isCommercialCustomer
     *
     */
    public function testIsCommercialCustomer()
    {
        $this->deliveryHelper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(false);
        $this->assertEquals(false, $this->ssoConfigurationData->isCommercialCustomer());
    }

    /**
     * Test getCurrentStoreCode
     *
     */
    public function testGetCurrentStoreCode()
    {
        $this->storeManager->expects($this->any())
            ->method('getGroup')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getCode')
            ->willReturn('test');
        $this->assertEquals('test', $this->ssoConfigurationData->getCurrentStoreCode());
    }

    /**
     * @test testIsSdeStore
     */
    public function testIsSdeStore()
    {
        $this->sdeHelper->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);

        $this->assertEquals(1, $this->ssoConfigurationData->isSdeStore());
    }

    /**
     * @test testGetSdeLogoutUrl
     */
    public function testGetSdeLogoutUrl()
    {
        $logoutUrl = 'https://www.fedex.com/en-us/home.html';

        $this->sdeHelper->expects($this->any())
            ->method('getLogoutUrl')
            ->willReturn($logoutUrl);

        $this->assertEquals($logoutUrl, $this->ssoConfigurationData->getSdeLogoutUrl());
    }

    /**
     * @test testGetSdeLogoutIdleTimeOut
     */
    public function testGetSdeLogoutIdleTimeOut()
    {
        $idleTimeout = '3600';
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(self::XML_PATH_FEDEX_SSO . 'login_session/login_session_idle_timeout', ScopeInterface::SCOPE_STORE)
            ->willReturn($idleTimeout);
        $this->assertEquals($idleTimeout, $this->ssoConfigurationData->getSdeLogoutIdleTimeOut());
    }

    /**
     * Test is retail
     */
    public function testIsRetail()
    {
        $this->storeManager->expects($this->any())
            ->method('getGroup')
            ->willReturnSelf();

        $this->storeManager->expects($this->any())
            ->method('getCode')
            ->willReturn('main_website_store');
        $this->assertEquals(true, $this->ssoConfigurationData->isRetail());
    }

    /**
     * @return void
     */
    public function testGetCanvaDesignEnabled()
    {
        $value = 1;
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn($value);
        $this->assertEquals($value, $this->ssoConfigurationData->getCanvaDesignEnabled());
    }

    public function testGetloginErrorCode()
    {
        $this->customerSession->expects($this->any())->method('getLoginErrorCode')->willReturn(true);
        $this->ssoConfigurationData->getloginErrorCode();
    }

    public function testGetloginErrorCodeFalse()
    {
        $this->assertFalse($this->ssoConfigurationData->getloginErrorCode());
    }

    public function testGetIsRequestFromSdeStoreFclLogin()
    {
        $this->sdeHelper->expects($this->any())->method('getIsRequestFromSdeStoreFclLogin')->willReturn(true);
        $this->ssoConfigurationData->getIsRequestFromSdeStoreFclLogin();
    }

    public function testIsSelfRegCustomerWithFclEnabled()
    {
        $this->selfregHelper->expects($this->any())->method('isSelfRegCustomerWithFclEnabled')->willReturn(true);
        $this->ssoConfigurationData->isSelfRegCustomerWithFclEnabled();
    }


    /**
     * Test isFCLCookieNameToggle
     *
     * @return void
     */
    public function testGetFCLCookieNameToggle()
    {
        $this->dataHelperMock->expects($this->once())
        ->method('getFCLCookieNameToggle')
        ->willReturn(true);

        $this->assertEquals(true, $this->ssoConfigurationData->getFCLCookieNameToggle());
    }

    /**
     * Test GetFCLCookieName
     *
     * @return void
     */
    public function testGetFCLCookieConfigValue()
    {
        $returnValue = 'test';
        $this->dataHelperMock->expects($this->once())
        ->method('getFCLCookieConfigValue')
        ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->ssoConfigurationData->getFCLCookieConfigValue());
    }

    /**
     * Test isBidQuoteParamSet
     *
     * @return void
     */
    public function testIsBidQuoteParamSet()
    {
        $returnValue = 'test';
        $this->request->expects($this->once())
            ->method('getParam')
            ->willReturn($returnValue);

        $this->assertEquals($returnValue, $this->ssoConfigurationData->isBidQuoteParamSet());
    }

    /**
     * Test isFuseBidToggleEnabled
     *
     * @return void
     */
    public function testIsFuseBidToggleEnabled()
    {
        $this->fuseBidHelper->expects($this->once())
            ->method('isFuseBidGloballyEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->ssoConfigurationData->isFuseBidToggleEnabled());
    }

    /**
     * Test getcontactinformationprofileurl
     *
     * @return void
     */
    public function testGetContactInformationProfileUrl()
    {
        $contactUrl = 'https://www.fedex.com/contact-info/';
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(SsoConfiguration::CONTACT_INFORMATION_PROFILE_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn($contactUrl);

        $this->assertEquals($contactUrl, $this->ssoConfigurationData->getcontactinformationprofileurl());
    }

    /**
     * Test isimprovingpasswordtoggle
     *
     * @return void
     */
    public function testIsImprovingPasswordToggle()
    {
        $toggleValue = true;
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('sgc_improving_visibility_to_change_password')
            ->willReturn($toggleValue);

        $this->assertEquals($toggleValue, $this->ssoConfigurationData->isimprovingpasswordtoggle());
    }

    /**
     * Test isSSOlogin
     *
     * @return void
     */
    public function testIsSSOlogin()
    {
        $toggleValue = true;
        $this->dataHelperMock->expects($this->once())
            ->method('isSSOlogin')
            ->willReturn(true);

        $this->assertTrue($this->ssoConfigurationData->isSSOlogin());
    }

}
