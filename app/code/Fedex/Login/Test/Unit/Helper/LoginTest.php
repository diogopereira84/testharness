<?php
/**
 * Copyright © By Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Test\Unit\Helper;

use Fedex\CIDPSG\Helper\Email;
use Fedex\Customer\Helper\Customer as CustomerHelper;
use Fedex\EmailVerification\Model\EmailVerification;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Login\Model\Config;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\Helper\Data as SSOHelper;
use Fedex\SSO\Model\Config as SSOConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\Login\Helper\Login;
use Fedex\SDE\Model\ForgeRock;
use Fedex\Canva\Model\CanvaCredentials;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

class LoginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfig;
    /**
     * @var \Fedex\Login\Model\Config
     */
    protected $moduleConfig;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Magento\Company\Api\Data\CompanyCustomerInterfaceFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $compCustInterface;
    protected $storeFactory;
    /**
     * @var (\Fedex\Login\Test\Unit\Helper\Store & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $store;
    protected $punchoutHelper;
    protected $cookieManager;
    protected $cookieMetadataFactoryMock;
    protected $publicCookieMetadataMock;
    protected $storeManagerMock;
    /**
     * @var (\Magento\Store\Api\Data\WebsiteInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $websiteInterfaceMock;
    /**
     * @var (\Magento\Store\Api\Data\StoreInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeInterfaceMock;
    /**
     * @var (\Fedex\Login\Test\Unit\Helper\Store & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeMock;
    protected $canvaCredentialsMock;
    protected $logger;
    protected $customerHelper;
    protected $toggleConfig;
    protected $sdeHelper;
    protected $selfRegHelper;
    protected $ssoHelper;
    protected $ssoConfig;
    protected $companyManagement;
    protected $customerFactory;
    protected $companyFactory;
    /**
     * @var (\Magento\Company\Model\Company & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $company;
    /**
     * @var (\Magento\Customer\Api\CustomerRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerRepositoryInterface;
    protected $customerInterfaceFactory;
    protected $forgeRockMock;
    /**
     * @var (\Fedex\Login\Test\Unit\Helper\CustomerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerInterface;
    protected $customerSession;
    protected $session;
    /**
     * @var (\Fedex\Login\Test\Unit\Helper\Customer & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerModel;
    protected $moduleConfigMock;
    protected $sendEmailMock;
    protected $emailVerificationMock;
    protected $fuseBidViewModel;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $login;
    public const FCL_LOGIN_TYPE = "fcl";
    public const COMPANY_ID = 48;

    protected function setUp(): void
    {
        // Mock ScopeConfigInterface
        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        // Initialize moduleConfig with scopeConfig
        $this->moduleConfig = new \Fedex\Login\Model\Config($this->scopeConfig);

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();
        $this->compCustInterface = $this->getMockBuilder(CompanyCustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId','create'])
            ->getMock();
        $this->storeFactory = $this->getMockBuilder(StoreFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['load','getUrl'])
            ->getMock();
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isActiveCustomer'])
            ->getMock();
        $this->cookieManager = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCookie','setPublicCookie'])
            ->getMockForAbstractClass();
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(cookieMetadataFactory::class)
            ->setMethods(['createPublicCookieMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->publicCookieMetadataMock = $this->getMockBuilder(PublicCookieMetadata::class)
            ->setMethods(['setPath', 'setHttpOnly','setSecure', 'setSameSite','setDuration'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getCode', 'getWebsite', 'getWebsiteId', 'getId', 'getStoreId'])
            ->getMockForAbstractClass();
        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->setMethods(['getId','getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite', 'getStoreId'])
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $this->canvaCredentialsMock = $this->getMockBuilder(CanvaCredentials::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetch'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['critical','info'])
            ->getMockForAbstractClass();
        $this->customerHelper = $this->getMockBuilder(CustomerHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerByUuid', 'updateExternalIdentifier', 'getCustomerStatus'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCustomerActiveSessionCookie'])
            ->getMock();
        $this->selfRegHelper = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkCustomerIsCompanyAdmin', 'validateDomain', 'getSettingByCompanyId', 'checkSelfRegEnable'])
            ->getMock();
        $this->ssoHelper = $this->getMockBuilder(SSOHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'saveAddress',
                    'getProfileByProfileApi',
                    'getCompanyCustomerGroupId',
                    'updateCustomerCanvaId',
                    'getCustomerCanvaIdByUuid',
                    'setCanvaIdByProfileApi',
                    'setCustomerCanvaIdAfterMigration',
                    'getToggleConfigValue',
                    'isCanvaIdMigrationEnabled',
                    'getcustomerCanvaId',
                    'getUserProfileId',
                    'setFclMetaDataCookies',
                    'getFCLCookieNameToggle',
                    'getFCLCookieConfigValue',
                    'getSSOWithFCLToggle'
                ]
            )
            ->getMock();
        $this->ssoConfig = $this->getMockBuilder(SSOConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProfileApiUrl','isWireMockLoginEnable','getWireMockProfileUrl'])
            ->getMock();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId', 'assignCustomer'])
            ->getMockForAbstractClass();
        $this->customerFactory = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
                'isLoggedIn',
                'getCustomer',
                'getSecondaryEmail',
                'getOndemandCompanyInfo',
                'unsOndemandCompanyInfo',
                'unsCustomerCompany',
                'setWebsiteId',
                'setFirstname',
                'setLastname',
                'setEmail',
                'setStoreId',
                'setSecondaryEmail',
                'setFclProfileContactNumber',
                'setContactNumber',
                'setContactExt',
                'setCustomerUuidValue',
                'save',
                'getId',
                'setLoginErrorCode',
                'setSelfRegLoginError',
                'setOndemandCompanyInfo',
                'setCustomerCompany',
                'getSelfRegLoginError',
                'getLoginErrorCode',
                'unsSelfRegLoginError',
                'getcustomerCanvaId',
                'getUserProfileId',
                'setCustomerCanvaId',
                'unsLoginErrorCode',
                'setCustomerAsLoggedIn',
                'setInvalidLoginCustomerId',
                'getInvalidLoginCustomerId',
                'unsInvalidLoginCustomerId',
                'unsLoginMethod',
                'getLoginMethod',
                'load',
                'getName',
                'getEmail'
            ])
            ->getMock();
        $this->companyFactory = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'create'])
            ->getMock();
        $this->company = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load'])
            ->getMock();
        $this->customerRepositoryInterface = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMockForAbstractClass();
        $this->customerInterfaceFactory = $this->getMockBuilder(CustomerInterfaceFactory::class)
            ->setMethods(['create', 'getId', 'setWebsiteId', 'save', 'setFirstname', 'setLastname', 'setEmail', 'setStoreId', 'getSecondaryEmail', 'setGroupId', 'getIsIdentifierExist'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->forgeRockMock = $this->createMock(ForgeRock::class);
        $this->customerInterface = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getId', 'setWebsiteId', 'save', 'setFirstname', 'setLastname', 'setEmail', 'setStoreId'])
            ->getMock();
        $this->customerSession = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setLoginErrorCode', 'unsFclFdxLogin', 'setSelfRegLoginError', 'getId'])
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getCustomer', 'isLoggedIn', 'getSelfRegLoginError',
                'unsSelfRegLoginError', 'setOndemandCompanyInfo', 'getLoginErrorCode', 'getcustomerCanvaId', 'getUserProfileId', 'setCustomerCanvaId', 'unsFclFdxLogin',
                'setLoginErrorCode', 'setSelfRegLoginError'])
            ->getMock();

        $this->customerModel = $this->getMockBuilder(Customer::class)
            ->setMethods([
                'setWebsiteId',
                'setFirstname',
                'setLastname',
                'setEmail',
                'save',
                'loadByEmail',
                'load',
                'getId',
                'getHash',
                'setData',
                'getExtensionAttributes',
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->moduleConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVerificationEmailSubject', 'getVerificationEmailFrom', 'getEmailVerificationTemplate',
                'getLinkExpirationTime', 'isConfirmationEmailRequired'])
            ->getMock();
        $this->sendEmailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadEmailTemplate', 'sendEmail'])
            ->getMock();
        $this->emailVerificationMock = $this->getMockBuilder(EmailVerification::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generateCustomerEmailUuid', 'getEmailVerificationLink', 'updateEmailVerificationCustomer'])
            ->getMock();
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateCustomerQuote'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->login = $this->objectManager->getObject(
            Login::class,
            [
                'customerSession' => $this->customerSession,
                'companyFactory' => $this->companyFactory,
                'storeManager' => $this->storeManagerMock,
                'logger' => $this->logger,
                'customerHelper' => $this->customerHelper,
                'ssoConfig' => $this->ssoConfig,
                'cookieManager' => $this->cookieManager,
                'ssoHelper' => $this->ssoHelper,
                'customerRepositoryInterface' => $this->customerRepositoryInterface,
                'customerInterfaceFactory' => $this->customerInterfaceFactory,
                'companyManagement' => $this->companyManagement,
                'customerFactory' => $this->customerFactory,
                'sdeHelper' => $this->sdeHelper,
                'forgeRock' => $this->forgeRockMock,
                'toggleConfig' => $this->toggleConfig,
                'selfRegHelper' => $this->selfRegHelper,
                'compCustInterface' => $this->compCustInterface,
                'storeFactory' => $this->storeFactory,
                'punchoutHelper' => $this->punchoutHelper,
                'customerInterface' => $this->customerInterface,
                'canvaCredentials' => $this->canvaCredentialsMock,
                'cookieMetadataFactory' => $this->cookieMetadataFactoryMock,
                'moduleConfig' => $this->moduleConfigMock,
                'sendEmail' => $this->sendEmailMock,
                'moduleConfig' => $this->moduleConfig,
                'emailVerification' => $this->emailVerificationMock,
                'fuseBidViewModel' => $this->fuseBidViewModel
            ]
        );
    }

    /**
     * Test the getProfileData method.
     * @param string $scope
     *
     * @dataProvider dataProviderScopesForGetProfile
     */
    public function testGetProfileData(string $scope)
    {
        // Define test input data
        $loginType = $scope;
        $endUrl = 'https://example.com/profile-api';
        $cookieData = 'sso-cookie-data';
        $profileData = [
            'address' => [
                'uuId' => '',
                'customerId' => 'httpwww.okta.comexk9p9nmi2mDr6K0L5d7_sreejith.b.osv@fedex.com'
            ]
        ];
        // Mock the behavior of dependencies
        $this->ssoConfig->expects($this->any())
            ->method('getProfileApiUrl')
            ->willReturn($endUrl);

        if ($scope == 'sso') {
            $this->cookieManager->expects($this->any())
                ->method('getCookie')
                ->with(SSOHelper::SDE_COOKIE_NAME)
                ->willReturn($cookieData);
        } else {
            $this->cookieManager->expects($this->any())
                ->method('getCookie')
                ->with('fdx_login')
                ->willReturn($cookieData);
        }

        $this->ssoHelper->expects($this->any())
            ->method('getProfileByProfileApi')
            ->with($endUrl, $cookieData)
            ->willReturn($profileData);

        // Call the method to be tested
        $result = $this->login->getProfileData($loginType);

        // Assert that the result matches the expected profile data
        $this->assertEquals($profileData, $result);

        return $result;
    }

    /**
     * @return void
     */
    public function testGetProfileDataWithCompanyLookup()
    {
        // Test setup
        $loginType = "sso";
        $endUrl = 'https://example.com/profile-api';
        $cookieData = 'sso-cookie-data';
        $profileData = [
            'address' => [
                'uuId' => '',
                'customerId' => 'idp_123_user'
            ]
        ];

        // Mock toggleConfig
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('techtitans_d_193751')
            ->willReturn(true);

        // Mock ssoConfig
        $this->ssoConfig->expects($this->once())
            ->method('getProfileApiUrl')
            ->willReturn($endUrl);

        // Mock cookieManager
        $this->cookieManager->expects($this->once())
            ->method('getCookie')
            ->with(SSOHelper::SDE_COOKIE_NAME)
            ->willReturn($cookieData);

        // Mock ssoHelper
        $this->ssoHelper->expects($this->once())
            ->method('getProfileByProfileApi')
            ->with($endUrl, $cookieData)
            ->willReturn($profileData);

        // Mock company model
        $companyModel = $this->createMock(\Magento\Company\Model\Company::class);

        // Mock collection
        $companyCollection = $this->createMock(\Magento\Company\Model\ResourceModel\Company\Collection::class);

        // Set up the method getCollection on company model
        $companyModel->expects($this->any())
            ->method('getCollection')
            ->willReturn($companyCollection);

        // Mock companyFactory->create() to return the company model
        $this->companyFactory->expects($this->once())
            ->method('create')
            ->willReturn($companyModel);

        // Set up collection method expectations
        $companyCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('sso_idp', 'idp')
            ->willReturnSelf();

        $companyCollection->expects($this->once())
            ->method('setPageSize')
            ->with(1)
            ->willReturnSelf();

        $companyCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn(new \Magento\Framework\DataObject([
                'entity_id' => 2,
                'company_url_extention' => 'test-extension'
            ]));

        // Execute test
        $result = $this->login->getProfileData($loginType);

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('company_url_extension', $result);
        $this->assertEquals('test-extension', $result['company_url_extension']);
    }

    /**
     * @return array
     */
    public function dataProviderScopesForGetProfile(): array
    {
        return [
            ['sde_sso'],
            ['sde_ssoo'],
        ];
    }

    /**
     * Test the getCompanyId
     * @param bool $scope
     *
     * @dataProvider dataProviderScopesForGetCompanyId
     */
    public function testGetCompanyId(bool $scope)
    {
        $customerId = 1;

        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);

        if ($scope) {
            $this->customerFactory->expects($this->any())
                ->method('isLoggedIn')
                ->willReturn($scope);
            $this->customerFactory->expects($this->any())
                ->method('getCustomer')
                ->willReturn($this->customerInterfaceFactory);
            $this->customerInterfaceFactory->expects($this->any())
                ->method('getId')
                ->willReturn($customerId);
            $this->companyManagement->expects($this->any())
                ->method('getByCustomerId')
                ->with($customerId)
                ->willReturn($this->createMock(\Magento\Company\Api\Data\CompanyInterface::class));
        } else {
            $this->customerFactory->expects($this->any())
                ->method('isLoggedIn')
                ->willReturn($scope);
        }

        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);

        $this->customerFactory->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(['company_id' => 1]);

        // Call the method to be tested
        $result = $this->login->getCompanyId();

        if ($result) {
            $this->assertNotFalse($result);
        }
    }

    /**
     * @return array
     */
    public function dataProviderScopesForGetCompanyId(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Test the isCompanyAdminUser method.
     */
    public function testIsCompanyAdminUser()
    {
        // Prepare test data
        $profileData = [
            'address' => [
                'email' => 'test@example.com',
            ],
        ];

        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->createMock(\Magento\Store\Model\Website::class));
        $this->selfRegHelper->expects($this->any())
            ->method('checkCustomerIsCompanyAdmin')
            ->willReturn(true);

        $result = $this->login->isCompanyAdminUser($profileData, 'loginType', static::COMPANY_ID);

        // Assertions
        $this->assertTrue($result);
    }

    /**
     * Test the getLoginType method for the "sde_fcl" case.
     */
    public function testGetLoginTypeSdeFcl()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->ssoHelper->expects($this->any())
            ->method('getSSOWithFCLToggle')
            ->willReturn(false);
        $this->customerFactory->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn([
                'company_type' => 'sde',
                'company_data' => [
                    'storefront_login_method_option' => 'commercial_store_wlgn',
                ],
            ]);

        $result = $this->login->getLoginType();

        $this->assertEquals('fcl', $result);
    }

    /**
     * Test the getLoginType method for the "sso" case.
     */
    public function testGetLoginTypeSdeSso()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->ssoHelper->expects($this->any())
            ->method('getSSOWithFCLToggle')
            ->willReturn(false);
        $this->customerFactory->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn([
                'company_type' => 'sde',
                'company_data' => [
                    'storefront_login_method_option' => 'other_option',
                ],
            ]);

        $result = $this->login->getLoginType();

        // Assertions
        $this->assertEquals('fcl', $result);
    }

    /**
     * testGetLoginTypeCommercialStoreSso
     */
    public function testGetLoginTypeCommercialStoreSso()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn([
                'company_type' => 'sde',
                'company_data' => [
                    'storefront_login_method_option' => 'commercial_store_sso',
                ],
            ]);

        $result = $this->login->getLoginType();

        $this->assertEquals('sso', $result);
    }

    /**
     * Test the getRetailStoreUrl method.
     */
    public function testGetRetailStoreUrl()
    {
        // Configure the StoreFactory mock to return a retail store URL
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->any())
            ->method('load')
            ->with('default', 'code')
            ->willReturnSelf();
        $storeMock->expects($this->any())
            ->method('getUrl')
            ->willReturn('');

        $this->storeFactory->expects($this->any())
            ->method('create')
            ->willReturn($storeMock);

        // Call the method under test
        $result = $this->login->getRetailStoreUrl();

        // Assertions
        $this->assertEquals('', $result);
    }

    /**
     * Test the getOndemandStoreUrl method.
     */
    public function testGetOndemandStoreUrl()
    {
        // Configure the StoreFactory mock to return an ondemand store URL
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->any())
            ->method('load')
            ->with('ondemand', 'code')
            ->willReturnSelf();
        $storeMock->expects($this->any())
            ->method('getUrl')
            ->willReturn('');

        $this->storeFactory->expects($this->any())
            ->method('create')
            ->willReturn($storeMock);

        $result = $this->login->getOndemandStoreUrl();

        // Assertions
        $this->assertEquals('', $result);
    }

    /**
     * Test the getRedirectUrl method when the customer is logged in with a company ID.
     */
    public function testGetRedirectUrlWithCompanyId()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);
        $this->customerInterfaceFactory->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->customerFactory->expects($this->any())
            ->method('unsOndemandCompanyInfo')
            ->willReturn('');
        $this->customerFactory->expects($this->any())
            ->method('unsCustomerCompany')
            ->willReturn('');
        $this->testGetRetailStoreUrl();

        // Call the method under test
        $result = $this->login->getRedirectUrl();

        // Assertions
        $this->assertEquals('', $result);
    }

    /**
     * Test the getRedirectUrl method when the customer is logged in with a company ID.
     */
    public function testGetRedirectUrlWithCompanyIdOndemand()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);
        $this->customerInterfaceFactory->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $companyInterface = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMockForAbstractClass();
        $this->companyManagement->expects($this->any())
            ->method('getByCustomerId')
            ->with(1)
            ->willReturn($companyInterface);
        $companyInterface->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);
        $this->customerFactory->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(['company_id' => 1]);
        $this->testGetOndemandCompanyDataWithValidCompanyId();
        $this->testGetOndemandStoreUrl();
        $this->customerFactory->expects($this->any())
            ->method('setOndemandCompanyInfo')
            ->willReturn(['company_id' => 1]);
        $this->customerFactory->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturn(1);

        // Call the method under test
        $result = $this->login->getRedirectUrl();

        // Assertions
        $this->assertEquals('', $result);
    }

    /**
     * Test the getRedirectUrl method when the customer is not logged in.
     */
    public function testGetRedirectUrlWhenNotLoggedIn()
    {
        // Configure the customer session mock to indicate that the customer is not logged in

        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        // Call the method under test
        $result = $this->login->getRedirectUrl();

        // Assertions
        $this->assertEquals('', $result);
    }

    /**
     * Test the getOndemandCompanyData method with a valid company ID.
     */
    public function testGetOndemandCompanyWithValidCompanyId()
    {
        // Call the method under test with a valid company ID
        $result = $this->login->getOndemandCompanyData(false);

        $this->assertFalse($result);
    }

    /**
     * testSetProfileApprovalMessage
     */
    public function testSetProfileApprovalMessage()
    {
        $errorCode = 'retail_login_error';
        $profileApprovalMessage = 'The account sign-in was incorrect or your account
        is disabled temporarily.';

        $this->login->errorCode = $errorCode;
        $this->login->profileApprovalMessage = $profileApprovalMessage;

        $this->customerSession->expects($this->any())->method('create')->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->any())
            ->method('setLoginErrorCode')
            ->with($errorCode);
        $this->customerFactory->expects($this->any())
            ->method('setSelfRegLoginError')
            ->with($profileApprovalMessage);

        $this->assertNull($this->login->setProfileApprovalMessage($errorCode, $profileApprovalMessage));
    }

    /**
     * testGetCommercialFCLApprovalType
     */
    public function testGetCommercialFCLApprovalType()
    {
        $this->testGetCompanyId(false);

        $result = $this->login->getCommercialFCLApprovalType();

        $expectedResult = [
            'error_msg' => '',
            'domains' => null,
            'login_method' => 'registered_user',
            'fcl_user_email_verification_user_display_message' => '',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * testGetCommercialFCLApprovalTypeFalse
     */
    public function testGetCommercialFCLApprovalTypeFalse()
    {
        $customerId = 0;
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);

        $this->customerInterfaceFactory->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);

        $this->companyManagement->expects($this->any())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($this->createMock(\Magento\Company\Api\Data\CompanyInterface::class));

        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);

        $this->customerFactory->expects($this->any())
            ->method('getOndemandCompanyInfo')
            ->willReturn(['company_id' => 0]);

        $result = $this->login->getCommercialFCLApprovalType();

        $this->assertFalse($result);
    }

    /**
     * testGetOndemandCompanyDataWithValidCompanyId
     */
    public function testGetOndemandCompanyDataWithValidCompanyId()
    {
        $companyId = 1;
        $companyData = [
            'entity_id' => 1,
            'is_sensitive_data_enabled' => true,
            'storefront_login_method_option' => 'commercial_store_wlgn'
        ];

        $resultData = [
            'company_id' => 1,
            'company_data' => $companyData,
            'ondemand_url' => true,
            'url_extension' => true,
            'company_type' => 'sde'
        ];

        $this->companyFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->companyFactory->method('load')
            ->willReturn($companyData);

        $this->selfRegHelper->expects($this->any())
            ->method('checkSelfRegEnable')
            ->willReturn(1);

        $result = $this->login->getOndemandCompanyData($companyId);

        $this->assertEquals($resultData, $result);
    }

    /**
     * testGetOndemandCompanyDataWithValidCompanyIdSelf
     */
    public function testGetOndemandCompanyDataWithValidCompanyIdSelf()
    {
        $companyId = 1;
        $companyData = [
            'entity_id' => 1,
            'is_sensitive_data_enabled' => false,
            'storefront_login_method_option' => 'commercial_store_wlgn'
        ];
        $resultData = [
            'company_id' => 1,
            'company_data' => $companyData,
            'ondemand_url' => true,
            'url_extension' => true,
            'company_type' => 'selfreg'
        ];

        $this->companyFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();

        $this->companyFactory->method('load')
            ->willReturn($companyData);

        $result = $this->login->getOndemandCompanyData($companyId);

        $this->assertEquals($resultData, $result);
    }

    /**
     * testGetOndemandCompanyDataWithValidCompanyIdOndemand
     */
    public function testGetOndemandCompanyDataWithValidCompanyIdOndemand()
    {
        $companyId = 1;

        $this->companyFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();

        $this->companyFactory->method('load')
            ->willReturn(null);

        $this->selfRegHelper->expects($this->any())
            ->method('checkSelfRegEnable')
            ->willReturn(1);

        $result = $this->login->getOndemandCompanyData($companyId);

        $this->assertEquals(['ondemand_url' => true, 'url_extension' => false], $result);
    }

    /**
     * Create FCL Customer for Retail
     */
    public function testCreateFclCustomerRetail()
    {
        $profileData = [
            'address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'johndoe@example.com',
                'uuId' => '12345',
                'contactNumber' => '1234567890',
                'ext' => '123',
            ],
        ];

        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);

        $customerId = 1;
        $this->customerFactory->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerFactory->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerInterfaceFactory);
        $this->customerInterfaceFactory->expects($this->any())
            ->method('getId')
            ->willReturn($customerId);
        $this->companyManagement->expects($this->any())
            ->method('getByCustomerId')
            ->with($customerId)
            ->willReturn($this->createMock(\Magento\Company\Api\Data\CompanyInterface::class));

        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManagerMock);

        $websiteId = 1;
        $this->storeManagerMock->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);
        $this->customerFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('setFirstname')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('setLastname')->willReturnSelf();
        $this->customerFactory->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerMock->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->customerFactory->expects($this->any())->method('save')->willReturnSelf();

        $this->ssoHelper->expects($this->any())->method('saveAddress')->willReturnSelf();

        $this->assertNotNull($this->login->createFclCustomer($profileData));
    }

    /**
     * testUpdateCustomerBasicInfo
     */
    public function testUpdateCustomerBasicInfo()
    {
        $profileDetails = [
            'address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
                'contactNumber' => '1234567890',
                'ext' => '123',
                'uuId' => 'uuid123',
            ],
        ];

        $this->customerFactory->expects($this->once())
            ->method('setFirstname')
            ->with('John');

        $this->customerFactory->expects($this->once())
            ->method('setLastname')
            ->with('Doe');

        $this->customerFactory->expects($this->once())
            ->method('setSecondaryEmail')
            ->with('john@example.com');

        $this->customerFactory->expects($this->once())
            ->method('setFclProfileContactNumber')
            ->with('1234567890');

        $this->customerFactory->expects($this->once())
            ->method('setContactNumber')
            ->with('1234567890');

        $this->customerFactory->expects($this->once())
            ->method('setContactExt')
            ->with('123');

        $this->customerFactory->expects($this->once())
            ->method('setCustomerUuidValue')
            ->with('uuid123');

        $this->customerFactory->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $result = $this->login->updateCustomerBasicInfo($this->customerFactory, $profileDetails);

        $this->assertTrue($result);
    }

    /**
     * testUpdateCustomerBasicInfoWithShortContactNumber
     */
    public function testUpdateCustomerBasicInfoWithShortContactNumber()
    {
        $profileDetails = [
            'address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
                'contactNumber' => '123',
                'ext' => '123',
                'uuId' => 'uuid123',
            ],
        ];

        $result = $this->login->updateCustomerBasicInfo($this->customerFactory, $profileDetails);

        $this->assertNull($result);
        $this->assertEquals('123', $profileDetails['address']['contactNumber']);
    }

    /**
     * testUpdateCustomerBasicInfoWithException
     */
    public function testUpdateCustomerBasicInfoWithException()
    {
        $profileDetails = [];

        $this->customerFactory->expects($this->any())
            ->method('save')
            ->willThrowException(new \Exception("Save failed"));

        $this->logger->expects($this->once())
            ->method('critical');

        $result = $this->login->updateCustomerBasicInfo($this->customerFactory, $profileDetails);

        $this->assertSame($this->customerFactory, $result);
    }

    /**
     * testSetCustomerCanva
     */
    public function testSetCustomerCanva()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);

        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->session);

        $this->customerFactory->expects($this->any())
            ->method('getcustomerCanvaId')
            ->willReturn('canvaId');

        $this->session->expects($this->any())
            ->method('getcustomerCanvaId')
            ->willReturn('canvaId');

        $this->customerFactory->expects($this->any())
            ->method('getcustomerCanvaId')
            ->willReturn('canvaId');

        $this->customerFactory->expects($this->any())
            ->method('getUserProfileId')
            ->willReturn('profileId');

        $this->ssoHelper->expects($this->any())
            ->method('isCanvaIdMigrationEnabled')
            ->willReturn(true);

        $this->ssoHelper->expects($this->any())
            ->method('setCustomerCanvaIdAfterMigration')
            ->willReturnSelf();

        $this->ssoHelper->expects($this->any())
            ->method('getCustomerCanvaIdByUuid')
            ->willReturn('canvaId');

        $this->ssoHelper->expects($this->any())
            ->method('setCustomerCanvaIdAfterMigration')
            ->willReturnSelf();

        $this->login->setCustomerCanva('loginType', $this->customerFactory, 'uuid', 'customerId', 'customer');
    }

    /**
     * testSetCustomerCanvaId
     */
    public function testSetCustomerCanvaId()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);

        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->session);

        $this->customerFactory->expects($this->any())
            ->method('getcustomerCanvaId')
            ->willReturn('canvaId');

        $this->session->expects($this->any())
            ->method('getcustomerCanvaId')
            ->willReturn('canvaId');

        $this->customerFactory->expects($this->any())
            ->method('getcustomerCanvaId')
            ->willReturn('canvaId');

        $this->customerFactory->expects($this->any())
            ->method('getUserProfileId')
            ->willReturn('profileId');

        $this->ssoHelper->expects($this->any())
            ->method('isCanvaIdMigrationEnabled')
            ->willReturn(true);

        $this->ssoHelper->expects($this->any())
            ->method('getCustomerCanvaIdByUuid')
            ->willReturn('canvaIds');

        $this->ssoHelper->expects($this->any())
            ->method('setCustomerCanvaIdAfterMigration')
            ->willReturnSelf();

        $this->customerFactory->expects($this->any())
            ->method('setCustomerCanvaId')
            ->willReturnSelf();


        $this->login->setCustomerCanva('loginType', $this->customerFactory, 'uuid', 'customerId', 'customer');
    }

    /**
     * testHandleCustomerSessionWithToggleEnabled
     */
    public function testHandleCustomerSessionWithToggleEnabled()
    {
        $this->forgeRockMock->expects($this->any())
            ->method('getCookie')
            ->willReturn('sso-cookie-data');
        $this->ssoHelper->expects($this->any())
            ->method('getFCLCookieNameToggle')
            ->willReturn(true);
        $this->ssoHelper->expects($this->any())
            ->method('getFCLCookieConfigValue')
            ->willReturn('fdx_login');

        $this->cookieManager->expects($this->exactly(3))
            ->method('getCookie')
            ->withConsecutive([SSOHelper::SDE_COOKIE_NAME], ['fdx_login'])
            ->willReturnOnConsecutiveCalls('sso-cookie-data', 'sso-cookie-data');

        $this->testGetLoginTypeSdeSso();
        $this->testGetDefaultStoreCode();
        $this->customerFactory->expects($this->exactly(1))
            ->method('getLoginErrorCode')
            ->willReturn('error');

        $this->customerFactory->expects($this->exactly(2))
            ->method('getSelfRegLoginError')
            ->willReturn('error');

        $result = $this->login->handleCustomerSession();

        $this->assertNotNull($result);
    }

    /**
     * testHandleCustomerSessionSuccessLogin
     */
    public function testHandleCustomerSessionSuccessLogin()
    {
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->forgeRockMock->expects($this->any())
            ->method('getCookie')
            ->willReturn('sso-cookie-data');

        $this->cookieManager->expects($this->any())
            ->method('getCookie')
            ->withConsecutive([SSOHelper::SDE_COOKIE_NAME], ['fdx_login'])
            ->willReturnOnConsecutiveCalls('sso-cookie-data', 'sso-cookie-data');

        $this->customerFactory->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->testGetLoginTypeSdeSso();

        $this->assertNotNull($this->login->handleCustomerSession());
    }

    /**
     * Handle customer session function test without logged in
     */
    public function testHandleCustomerSessionSuccessLoginWithoutLoggedIn()
    {
        $this->forgeRockMock->expects($this->any())
            ->method('getCookie')
            ->willReturn('sso-cookie-data');

        $this->cookieManager->expects($this->any())
            ->method('getCookie')
            ->withConsecutive([SSOHelper::SDE_COOKIE_NAME], ['fdx_login'])
            ->willReturnOnConsecutiveCalls('sso-cookie-data', 'sso-cookie-data');

        $this->customerFactory->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->testGetLoginTypeSdeSso();
        $this->testGetOndemandStoreCode();
        $this->customerFactory->expects($this->any())
            ->method('getLoginErrorCode')
            ->willReturn(false);

        $this->customerFactory->expects($this->any())
            ->method('getSelfRegLoginError')
            ->willReturn(false);

        $this->customerFactory->expects($this->any())
            ->method('unsSelfRegLoginError')
            ->willReturn('');
        $this->assertNotNull($this->login->handleCustomerSession());
    }

    /**
     * testHandleCustomerSessionWithToggleEnabledNotCookie
     */
    public function testHandleCustomerSessionWithToggleEnabledNotCookie()
    {
        $this->cookieManager->expects($this->exactly(2))
            ->method('getCookie')
            ->withConsecutive([SSOHelper::SDE_COOKIE_NAME], ['fdx_login'])
            ->willReturnOnConsecutiveCalls(false, 'no');

        $this->ssoHelper->expects($this->any())
            ->method('getSSOWithFCLToggle')
            ->willReturn(false);

        $result = $this->login->handleCustomerSession();

        $this->assertNotNull($result);
    }

    /**
     * testHandleCustomerSessionThrowException
     */
    public function testHandleCustomerSessionThrowException()
    {
        $exception = new \Exception();

        $this->cookieManager->expects($this->atLeast(1))
            ->method('getCookie')
            ->withConsecutive([SSOHelper::SDE_COOKIE_NAME], ['fdx_login'])
            ->willReturnOnConsecutiveCalls(false, 'no');

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willThrowException($exception);

        $result = $this->login->handleCustomerSession();

        $this->assertNotNull($result);
    }

    /**
     * testDoLoginIfNotSso
     */
    public function testDoLoginIfNotSso()
    {
        $loginType = 'fcl';

        $profileData = [
            'address' => [
                'email' => 'test@gmail.com',
                'uuId' => '',
                'uuidEmail' => [
                    'uuId' => '123xdsd@fedex.com',
                ],
            ],
        ];
        $this->testGetProfileData($loginType);
        $this->testGetCompanyId(true);
        $this->testIsCompanyAdminUser();
        $this->testSetProfileApprovalMessage();
        $this->customerHelper->expects($this->once())
            ->method('getCustomerByUuid')
            ->willReturn($this->customerInterfaceFactory);
        $this->customerInterfaceFactory->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->ssoHelper->expects($this->any())
            ->method('getSSOWithFCLToggle')
            ->willReturn(false);

        $this->assertNull($this->login->doLogin($loginType, false));
    }

    /**
     * testDoLogin
     */
    public function testDoLogin()
    {
        $loginType = 'sso';

        $profileData = [
            'address' => [
                'email' => 'test@gmail.com',
                'uuId' => '',
                'customerId' => 'http://www.okta.com/exk9p9nmi2mDr6K0L5d7_sreejith.b.osv@fedex.com',
                'uuidEmail' => [
                    'uuId' => '123.@fedex.com',
                ],
            ],
        ];
        $customer = new \Magento\Framework\DataObject([
            // 'id' => 1,
            // 'email' => 'test@gmail.com',
            'getIsIdentifierExist' => false
        ]);
        $this->testGetProfileData($loginType);
        $this->testGetCompanyId(true);
        $this->testGetProfileData($loginType);
        $this->testIsCompanyAdminUser();
        $this->testSetProfileApprovalMessage();
        $this->customerHelper->expects($this->once())
            ->method('getCustomerByUuid')
            ->willReturn($customer);
        $this->ssoHelper->expects($this->any())
            ->method('getSSOWithFCLToggle')
            ->willReturn(false);

        $this->assertNull($this->login->doLogin($loginType, false));
    }

    /**
     * @return array
     */
    public function dataProviderScopesForSetCustomerSession(): array
    {
        return [
            ['fcl'],
            ['sso'],
            ['noComp']
        ];
    }

    /**
     * testSetCustomerSession
     *
     * @param $loginType
     * @dataProvider dataProviderScopesForSetCustomerSession
     */
    public function testSetCustomerSession($loginType)
    {
        $companyId = 1;
        $this->customerHelper->expects($this->any())
            ->method('getCustomerByUuid')
            ->willReturn($this->customerInterfaceFactory);

        $this->customerInterfaceFactory->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);

        $this->customerFactory->expects($this->any())
            ->method('unsSelfRegLoginError')
            ->willReturn('');

        $this->customerFactory->expects($this->any())
            ->method('unsLoginErrorCode')
            ->willReturn('');

        $this->customerFactory->expects($this->any())
            ->method('unsInvalidLoginCustomerId')
            ->willReturn('');

        $this->customerFactory->expects($this->any())
            ->method('setCustomerAsLoggedIn')
            ->willReturnSelf();

        $this->customerFactory->expects($this->any())
            ->method('setCustomerCompany')
            ->willReturnSelf();

        $this->testCheckIsValidLoginWithValidLogin();

        if ($loginType == 'sso') {
            $this->sdeHelper->expects($this->any())
                ->method('setCustomerActiveSessionCookie')
                ->willReturnSelf();
        }

        if ($loginType == 'noComp') {
            $this->canvaCredentialsMock->expects($this->any())
                ->method('fetch')
                ->willReturnSelf();
            $this->ssoHelper->expects($this->any())
                ->method('setFclMetaDataCookies')
                ->willReturnSelf();

            $loginType = 'fcl';
            $companyId = 0;

            $data = [
                'self_reg_login_method' => 'admin_approval',
                'error_message' => '',
                'domains' => 'https://office.fedex.com'
            ];

            $this->selfRegHelper->expects($this->any())
                ->method('getSettingByCompanyId')
                ->with(1)
                ->willReturn($data);

            $this->login->getCommercialFCLApprovalType();
        }
        $this->assertNull($this->login->setCustomerSession(
            $loginType, $this->customerInterfaceFactory, $companyId, 'uuid'));
    }

    /**
     * testCustomerSessionWithException
     */
    public function testCustomerSessionWithException()
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willThrowException(new \Exception("Error"));

        $this->assertNull($this->login->setCustomerSession(
            null, $this->customerInterfaceFactory, null, 'uuid'));
    }

    /**
     * testCheckIsValidLoginWithValidLogin
     */
    public function testCheckIsValidLoginWithValidLogin()
    {
        $this->punchoutHelper->expects($this->any())
            ->method('isActiveCustomer')
            ->with($this->customerFactory)
            ->willReturn(false);

        $this->login->checkIsValidLogin(static::COMPANY_ID, $this->customerFactory);
    }

    /**
     * @test method for testcheckIsValidLoginWithAdminApprove
     * return void
     */
    public function testcheckIsValidLoginWithAdminApprove()
    {
        $this->punchoutHelper->expects($this->any())
            ->method('isActiveCustomer')
            ->with($this->customerFactory)
            ->willReturn(true);

        $commercialApprovalTypes = [
            'self_reg_login_method' => 'admin_approval',
            'error_message' => '',
            'domains' => 'https://office.fedex.com'
        ];

        $this->selfRegHelper->expects($this->exactly(1))
            ->method('getSettingByCompanyId')
            ->willReturn($commercialApprovalTypes);
        $this->punchoutHelper->expects($this->any())
            ->method('isActiveCustomer')
            ->willReturn(true);

        $this->testIsCustomerStatusActive('Active', true, 1);

        $this->assertNotNull($this->login->checkIsValidLogin(static::COMPANY_ID, $this->customerFactory));
    }

    /**
     * @test method for testcheckIsValidLoginWithDomainRegistration
     * return void
     */
    public function testcheckIsValidLoginWithDomainRegistration()
    {
        $this->punchoutHelper->expects($this->any())
            ->method('isActiveCustomer')
            ->with($this->customerFactory)
            ->willReturn(true);

        $commercialApprovalTypes = [
            'self_reg_login_method' => 'domain_registration',
            'error_message' => '',
            'domains' => 'https://office.fedex.com'
        ];

        $this->selfRegHelper->expects($this->exactly(1))
            ->method('getSettingByCompanyId')
            ->willReturn($commercialApprovalTypes);
        $this->customerFactory->expects($this->any())
            ->method('getSecondaryEmail')
            ->willReturn('test@gmail.com');
        $this->selfRegHelper->expects($this->exactly(1))
            ->method('validateDomain')
            ->willReturn(true);

        $this->testIsCustomerStatusActive(null, null, 1);

        $this->assertNotNull($this->login->checkIsValidLogin(static::COMPANY_ID, $this->customerFactory));
    }

    /**
     * @test method for getDefaultStoreCode
     */
    public function testGetDefaultStoreCode()
    {
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerMock->expects($this->any())->method('getCode')->willReturn('default');
        $this->assertEquals('default', $this->login->getStoreCode());
    }

    /**
     * @test method for getOndemandStoreCode
     */
    public function testGetOndemandStoreCode()
    {
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerMock->expects($this->any())->method('getCode')->willReturn('ondemand');
        $this->assertEquals('ondemand', $this->login->getStoreCode());
    }

    /**
     * Test Case for
     */
    public function testSetUrlExtensionCookie()
    {
        $this->cookieMetadataFactoryMock->expects($this->any())->method('createPublicCookieMetadata')->willReturn($this->publicCookieMetadataMock);
        $this->publicCookieMetadataMock->expects($this->any())->method('setPath')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setHttpOnly')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setSecure')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setSameSite')->willReturnSelf();
        $this->publicCookieMetadataMock->expects($this->any())->method('setDuration')->willReturnSelf();
        $this->cookieManager->expects($this->any())->method('setPublicCookie')->willReturnSelf();
        $this->login->setUrlExtensionCookie("l5site51");
    }

    /**
     * Test Case for getUrlExtensionCookie
     */
    public function testGetUrlExtensionCookie()
    {
        $this->cookieManager->expects($this->any())->method('getCookie')->willReturn('l5site51');
        $this->assertEquals('l5site51', $this->login->getUrlExtensionCookie());
    }

    /**
     * Test Case for isCustomerStatusActive
     * @param string|null $customerStatus
     * @param bool|null $isActive
     * @param int $customerId
     * @dataProvider getIsCustomerStatusActiveDataProvider
     * @return void
     */
    public function testIsCustomerStatusActive($customerStatus, $isActive, $customerId)
    {
        $this->customerHelper->expects($this->any())
            ->method('getCustomerStatus')
            ->willReturn($customerStatus);

        $this->assertEquals($isActive, $this->login->isCustomerStatusActive($customerId));
    }

    /**
     * @return array
     */
    public function getIsCustomerStatusActiveDataProvider(): array
    {
        return [
            [null, null, 1],
            ['Active', true, 2],
            ['Email Verification Pending', false, 3]
        ];
    }

    /**
     * Test Case for isEmailVerificationRequired
     *
     * @param string $status
     * @param bool $isRequired
     * @param int|null $customerId
     * @dataProvider getIsEmailVerificationRequiredDataProvider
     * @return void
     */
    public function testIsEmailVerificationRequired($status, $isRequired, $customerId)
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->once())
            ->method('getInvalidLoginCustomerId')
            ->willReturn($customerId);
        $this->customerFactory->expects($this->once())
            ->method('getLoginMethod')
            ->willReturn('domain_registration');
        $this->customerHelper->expects($this->any())
            ->method('getCustomerStatus')
            ->willReturn($status);
        $this->moduleConfigMock->expects($this->any())
            ->method('isConfirmationEmailRequired')
            ->willReturn(true);

        $this->testGetLoginTypeSdeFcl();

        $this->assertFalse($this->login->isEmailVerificationRequired());
    }

    /**
     * @return array
     */
    public function getIsEmailVerificationRequiredDataProvider(): array
    {
        return [
            ['Email Verification Pending', true, 1],
            ['Active', false, null]
        ];
    }

    /**
     * Test Case for sendUserVerificationEmail
     *
     * @param string $email
     * @param string $emailType
     * @param bool $returnVal
     * @param string|null $verificationLink
     * @dataProvider getSendUserVerificationEmailDataProvider
     * @return void
     */
    public function testSendUserVerificationEmail($email, $emailType, $returnVal, $verificationLink)
    {
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturn($this->customerFactory);
        $this->customerFactory->expects($this->once())
            ->method('getInvalidLoginCustomerId')
            ->willReturn(1);
        $this->customerFactory->expects($this->once())
            ->method('getInvalidLoginCustomerId')
            ->willReturn(1);
        $this->customerFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->customerFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->customerFactory->expects($this->any())
            ->method($emailType)
            ->willReturn($email);
        $this->customerFactory->expects($this->any())
            ->method('getName')
            ->willReturn('John Doe');
        $this->moduleConfigMock->expects($this->any())
            ->method('getLinkExpirationTime')
            ->willReturn('30 Minutes');
        $this->moduleConfigMock->expects($this->any())
            ->method('getVerificationEmailSubject')
            ->willReturn('Test Email');
        $this->moduleConfigMock->expects($this->any())
            ->method('getEmailVerificationTemplate')
            ->willReturn('');
        $this->sendEmailMock->expects($this->any())
            ->method('loadEmailTemplate')
            ->willReturn('');
        $this->moduleConfigMock->expects($this->any())
            ->method('getVerificationEmailFrom')
            ->willReturn('');
        $this->sendEmailMock->expects($this->any())
            ->method('sendEmail')
            ->willReturn($returnVal);
        $this->emailVerificationMock->expects($this->any())
            ->method('generateCustomerEmailUuid')
            ->willReturn('a3d4f5');
        $this->emailVerificationMock->expects($this->any())
            ->method('getEmailVerificationLink')
            ->willReturn($verificationLink);

        $this->assertEquals($returnVal, $this->login->sendUserVerificationEmail());
    }

    /**
     * Test Case for isEmailVerificationPending
     * @param string|null $customerStatus
     * @param bool|null $isPending
     * @param int $customerId
     * @dataProvider getisEmailVerificationPendingDataProvider
     * @return void
     */
    public function testisEmailVerificationPending($customerStatus, $isPending, $customerId)
    {
        $this->customerHelper->expects($this->any())
            ->method('getCustomerStatus')
            ->willReturn($customerStatus);

        $this->assertEquals($isPending, $this->login->isEmailVerificationPending($customerId));
    }

    /**
     * @return array
     */
    public function getSendUserVerificationEmailDataProvider(): array
    {
        return [
            ['yoyo@yahoo.com', 'getSecondaryEmail', true, 'https://test.com/'],
            ['aa@aa.com', 'getEmail', false, null]
        ];
    }

    public function getisEmailVerificationPendingDataProvider(): array
    {
        return [
            [null, null, 1],
            ['Active', false, 2],
            ['Email Verification Pending', true, 3]
        ];
    }

    /**
     * @return string
     */
    Public function testdetermineProfileApprovalMessage(){
        $customerId = 56;
        $customerStatus = 'Pending For Approval';
        $this->customerHelper->expects($this->any())
            ->method('getCustomerStatus')
            ->willReturn($customerStatus);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertNotNull($this->login->determineProfileApprovalMessage($customerId, []));
    }

    /**
     * Test for isLoggingToggleEnable
     */
    public function testIsLoggingToggleEnable()
    {
        $this->toggleConfig->expects($this->once())->method('getToggleConfigValue')
            ->with('explorers_cart_items_logging')->willReturn(true);
        $this->assertTrue($this->login->isLoggingToggleEnable());
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->login->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->customerSession->method('create')->willReturn($this->session);
        $this->session->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(true);
        $result = $this->login->getOrCreateCustomerSession();
        $this->assertSame($this->session, $result);
    }

    /**
     * Test method for getFuseBidQuoteUrl
     *
     * @return void
     */
    public function testGetFuseBidQuoteUrl()
    {
        $url = 'http://example.com/retail?bidquote=123';
        $this->customerSession->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->customerSession->expects($this->any())
            ->method('getId')
            ->willReturn(1234);
        $this->customerFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->customerFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->testGetRetailStoreUrl();
        $this->customerFactory->expects($this->any())
            ->method('getSecondaryEmail')
            ->willReturn('test@gmail.com');
        $this->fuseBidViewModel->expects($this->any())
            ->method('validateCustomerQuote')
            ->willReturn('test@gmail.com');

        $this->assertNotEquals($url, $this->login->getFuseBidQuoteUrl($url));
    }
}
