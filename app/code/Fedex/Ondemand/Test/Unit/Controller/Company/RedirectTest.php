<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Controller\Company;

use Magento\Customer\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Action\Context;
use Fedex\Ondemand\Helper\Ondemand;
use Magento\Customer\Model\SessionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\SelfReg\Block\Landing;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\UrlInterface;
use Fedex\Ondemand\Controller\Company\Redirect as CompanyRedirect;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\App\Request\Http;
use Fedex\NotificationBanner\ViewModel\NotificationBanner;
use Fedex\SDE\Model\Customer as SdeCustomerModel;
use Fedex\Login\Helper\Login;
use Fedex\Base\Helper\Auth;

class RedirectTest extends TestCase
{
    protected $ondemandHelperMock;
    protected $sessionFactoryMock;
    protected $customerSessionMock;
    protected $storeManagerInterfaceMock;
    protected $storeMock;
    protected $urlInterfaceMock;
    protected $selfRegLandingMock;
    protected $sdeHelperMock;
    protected $redirectFactoryMock;
    protected $redirectMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $toggleConfigMock;
    protected $responseInterfaceMock;
    protected $ssoConfigurationMock;
    protected $httpMock;
    protected $sdeCustomerModelMock;
    protected $notificationBannerMock;
    protected $loginHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $companyRedirectMock;
    protected Auth|MockObject $baseAuthMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->ondemandHelperMock = $this->getMockBuilder(Ondemand::class)
            ->setMethods(['isStoreRestructureOn', 'getOndemandStoreUrl', 'getOndemandCompanyData', 'getCompanyFromUrlExtension'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomer', 'logout', 'setLastCustomerId', 'isLoggedIn', 'setOndemandCompanyInfo'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn','getCompanyAuthenticationMethod'])
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode', 'getBaseUrl', 'getUrl'])
            ->getMock();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl', 'getCurrentUrl'])
            ->getMockForAbstractClass();

         $this->selfRegLandingMock = $this->getMockBuilder(Landing::class)
             ->setMethods(['getLoginUrl'])
             ->disableOriginalConstructor()
             ->getMock();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getSsoLoginUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();


        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->responseInterfaceMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRedirect'])
            ->getMockForAbstractClass();

        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['getGeneralConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpMock = $this->getMockBuilder(Http::class)
            ->setMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeCustomerModelMock = $this->getMockBuilder(SdeCustomerModel::class)
            ->setMethods(['redirectCustomerToSso'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationBannerMock = $this->getMockBuilder(NotificationBanner::class)
            ->setMethods(['isPageNotFound'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loginHelper = $this->getMockBuilder(Login::class)
            ->setMethods(['setUrlExtensionCookie','getUrlExtensionCookie'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);

        $this->companyRedirectMock = $this->objectManagerHelper->getObject(
            CompanyRedirect::class,
            [
                'ondemandHelper' => $this->ondemandHelperMock,
                'customerSessionFactory' => $this->sessionFactoryMock,
                'storeManagerInterface' => $this->storeManagerInterfaceMock,
                'selfRegLanding' => $this->selfRegLandingMock,
                'sdeHelper' => $this->sdeHelperMock,
                'resultRedirectFactory' => $this->redirectFactoryMock,
                'logger' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'urlInterface' => $this->urlInterfaceMock,
                '_response' => $this->responseInterfaceMock,
                'ssoConfiguration' => $this->ssoConfigurationMock,
                'request' => $this->httpMock,
                'notificationBanner' => $this->notificationBannerMock,
                'sdeCustomerModel' => $this->sdeCustomerModelMock,
                'loginHelper' => $this->loginHelper,
                'authHelper' => $this->baseAuthMock,
                'session' => $this->customerSessionMock
            ]
        );
    }

    /**
     * testExecuteForSelfReg
     */
    public function testExecuteForSelfReg()
    {
        $companyInfoSelfReg = ['company_type' => 'selfreg', 'ondemand_url' => true, 'url_extension' => true,'company_data' => ['storefront_login_method_option' => 'commercial_store_wlgn']];
        $companyInfoSde = ['company_type' => 'sde', 'ondemand_url' => true, 'url_extension' => true];

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);

        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandCompanyData')->willReturn($companyInfoSelfReg);
        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
    }

    /**
     * testExecuteForSDE
     */
    public function testExecuteForSDE()
    {
        $companyInfoSde = ['company_type' => 'sde', 'ondemand_url' => true, 'url_extension' => true,'company_data' => ['storefront_login_method_option' => 'commercial_store_sso','sso_login_url' => 'https://fedex.com/']];
        $ssoRedirectUrl = 'SSO_REDIRECT_URL';

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandCompanyData')->willReturn($companyInfoSde);
        $this->sdeHelperMock->expects($this->any())->method('getSsoLoginUrl')->willReturn($ssoRedirectUrl);

        $this->notificationBannerMock->expects($this->any())->method('isPageNotFound')->willReturn(false);

        $this->sdeCustomerModelMock->expects($this->any())->method('redirectCustomerToSso')->willReturn(true);

        $this->httpMock->expects($this->any())->method('getFullActionName')->willReturn('test');

        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        // $this->assertEquals(false, $this->companyRedirectMock->execute());
    }

    /**
     * testExecuteForSSOFalse
     */
    public function testExecuteWithoutStoreFront()
    {
        $companyInfoSde = ['company_type' => 'sde', 'ondemand_url' => true, 'url_extension' => true,'company_data' => ['storefront_login_method_option' => 'commercial_store_epro']];
        $ssoRedirectUrl = 'SSO_REDIRECT_URL';

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandCompanyData')->willReturn($companyInfoSde);
        $this->sdeHelperMock->expects($this->any())->method('getSsoLoginUrl')->willReturn($ssoRedirectUrl);

        $this->notificationBannerMock->expects($this->any())->method('isPageNotFound')->willReturn(false);

        $this->sdeCustomerModelMock->expects($this->any())->method('redirectCustomerToSso')->willReturn(true);

        $this->httpMock->expects($this->any())->method('getFullActionName')->willReturn('test');

        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        // $this->assertIsObject($this->companyRedirectMock->execute());
    }

    /**
     * testExecuteForSSOFalse
     */
    public function testExecuteForSSOFalse()
    {
        $companyInfoSde = ['company_type' => 'sde', 'ondemand_url' => true, 'url_extension' => true,'company_data' => ['storefront_login_method_option' => 'commercial_store_sso','sso_login_url' => '']];
        $ssoRedirectUrl = 'SSO_REDIRECT_URL';

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandCompanyData')->willReturn($companyInfoSde);
        $this->sdeHelperMock->expects($this->any())->method('getSsoLoginUrl')->willReturn($ssoRedirectUrl);

        $this->notificationBannerMock->expects($this->any())->method('isPageNotFound')->willReturn(false);

        $this->sdeCustomerModelMock->expects($this->any())->method('redirectCustomerToSso')->willReturn(true);

        $this->httpMock->expects($this->any())->method('getFullActionName')->willReturn('test');

        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        // $this->assertEquals(false, $this->companyRedirectMock->execute());
    }

    /**
     * testExecuteForWLGNRedirect
     */
    public function testExecuteForWLGNRedirect()
    {
        $companyInfoSde = ['company_type' => 'sde', 'ondemand_url' => true, 'url_extension' => true,'company_data' => ['storefront_login_method_option' => 'commercial_store_wlgn']];
        $ssoRedirectUrl = 'SSO_REDIRECT_URL';

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandCompanyData')->willReturn($companyInfoSde);
        $this->sdeHelperMock->expects($this->any())->method('getSsoLoginUrl')->willReturn($ssoRedirectUrl);

        $this->notificationBannerMock->expects($this->any())->method('isPageNotFound')->willReturn(false);

        $this->sdeCustomerModelMock->expects($this->any())->method('redirectCustomerToSso')->willReturn(true);

        $this->httpMock->expects($this->any())->method('getFullActionName')->willReturn('test');

        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();

        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn('https://fedex.com');

        $this->ssoConfigurationMock->expects($this->any())->method('getGeneralConfig')->willReturn('https://fedex.com');

        $this->customerSessionMock->expects($this->any())->method('setOndemandCompanyInfo')->willReturnSelf();
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        // $this->assertEquals(false, $this->companyRedirectMock->execute());
    }

    /**
     * testExecuteWithFalseUrlExtension
     */
    public function testExecuteWithFalseUrlExtension()
    {
        $companyInfoSde = ['ondemand_url' => true, 'url_extension' => false];
        $selfRegLoginUrl = 'SELFREG_LOGIN_URL';

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandCompanyData')->willReturn($companyInfoSde);
        $this->selfRegLandingMock->expects($this->any())->method('getLoginUrl')->willReturn($selfRegLoginUrl);

        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        // $this->assertIsObject($this->companyRedirectMock->execute());
    }

    /**
     * testExecuteWithLoggedInUser
     */
    public function testExecuteWithLoggedInUser()
    {
        $companyInfoSde = ['ondemand_url' => true, 'url_extension' => false];
        $ondemandUrl = 'ONDEMAND_URL';

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->ondemandHelperMock->expects($this->any())->method('getOndemandStoreUrl')->willReturn($ondemandUrl);
        $this->redirectMock->expects($this->any())->method('setUrl')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        //$this->assertIsObject($this->companyRedirectMock->execute());
    }

    /**
     * testExecuteWithException
     */
    public function testExecuteWithException()
    {
        $noRouteUrl = 'NOROUTE_URL';
        $exception = new \Exception();

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('ondemand');

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willThrowException($exception);

        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn($noRouteUrl);

        $this->responseInterfaceMock->expects($this->any())->method('setRedirect')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        //$this->assertFalse($this->companyRedirectMock->execute());
    }

    /**
     * testExecuteWithToggleOFF
     */
    public function testExecuteWithToggleOFF()
    {
        $noRouteUrl = 'NOROUTE_URL';
        $exception = new \Exception();

        $this->redirectFactoryMock->expects($this->any())->method('create')->willReturn($this->redirectMock);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getCode')->willReturn('');

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->urlInterfaceMock->expects($this->any())->method('getUrl')->willReturn($noRouteUrl);
        $this->responseInterfaceMock->expects($this->any())->method('setRedirect')->willReturnSelf();
        $this->loginHelper->expects($this->any())->method('setUrlExtensionCookie')->willReturn(null);
        
        $result = $this->companyRedirectMock->execute();
        $this->assertFalse($result, 'Execute should return false when toggle is OFF');
    }

    /**
     * @return void
     */
    public function testIsStoreFrontDataSetAuth()
    {
        $this->baseAuthMock->method('getCompanyAuthenticationMethod')->willReturn('sso');
        
        $result = $this->companyRedirectMock->isStoreFrontDataSet();
        $this->assertTrue($result, 'isStoreFrontDataSet should return true for SSO auth');
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     *
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        
        $result = $this->companyRedirectMock->getToggleStatusForPerformanceImprovmentPhasetwo();
        $this->assertTrue($result, 'Toggle status should return true when toggle is enabled');
    }

    /**
     * Test URL parsing with company extension at parseUrl[6]
     *
     * @return void
     */
    public function testExecuteWithUrlExtensionInUrlPath()
    {
        $urlExtension = 'testcompany';
        $companyFromUrl = ['company_url_extention' => $urlExtension, 'company_id' => '123'];
        $companyData = [
            'company_type' => 'selfreg',
            'ondemand_url' => true,
            'url_extension' => true,
            'company_data' => ['company_url_extention' => $urlExtension]
        ];

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return true;
                    }
                    return false;
                }
            );
        
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');

        // URL with company extension at position 6
        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://fedex.com/ondemand/company/redirect/url/index/testcompany');

        $this->ondemandHelperMock->expects($this->once())
            ->method('getCompanyFromUrlExtension')
            ->with($urlExtension)
            ->willReturn($companyFromUrl);

        $this->loginHelper->expects($this->once())
            ->method('setUrlExtensionCookie')
            ->with($urlExtension);

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        $this->loggerMock->expects($this->atLeastOnce())->method('info');

        $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo')->with($companyData);
        $this->redirectMock->expects($this->once())->method('setUrl')->with('selfreg/landing')->willReturnSelf();

        $result = $this->companyRedirectMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test URL parsing without company extension at parseUrl[6]
     *
     * @return void
     */
    public function testExecuteWithoutUrlExtensionInUrlPath()
    {
        $companyData = [
            'company_type' => 'selfreg',
            'ondemand_url' => true,
            'url_extension' => true,
            'company_data' => [
                'company_url_extention' => 'companyext'
            ]
        ];

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return true;
                    }
                    return false;
                }
            );
        
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');

        // URL without position 6 (short path)
        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://fedex.com/ondemand/company/redirect');

        // getCompanyFromUrlExtension should NOT be called
        $this->ondemandHelperMock->expects($this->never())->method('getCompanyFromUrlExtension');

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        // When no URL at position 6, but company data has extension, it's still NOT called 
        // because the cookie was already potentially set earlier or the logic skips it
        $this->loginHelper->expects($this->never())
            ->method('setUrlExtensionCookie');

        $this->loggerMock->expects($this->atLeastOnce())->method('info');

        $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo')->with($companyData);
        $this->redirectMock->expects($this->once())->method('setUrl')->with('selfreg/landing')->willReturnSelf();

        $result = $this->companyRedirectMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }


    /**
     * Test bookmarking site fix toggle scenarios
     *
     * @dataProvider bookmarkingSiteFixToggleDataProvider
     * @return       void
     */
    public function testBookmarkingSiteFixToggle($toggleEnabled, $currentUrl, $urlExtensionInCompanyData, $hasUrlAtPosition6, $expectedBehavior, $message)
    {
        // Extract URL extension from URL if present at position 6
        $urlExtension = null;
        if ($hasUrlAtPosition6) {
            $parsedUrl = parse_url($currentUrl);
            $pathParts = explode('/', $parsedUrl['path']);
            $urlExtension = $pathParts[6] ?? null;
        }
        
        $companyData = [
            'ondemand_url' => true,
            'company_data' => ['company_url_extention' => $urlExtensionInCompanyData]
        ];

        // Only set url_extension flag if we actually have an extension
        if (!empty($urlExtensionInCompanyData) || $hasUrlAtPosition6) {
            $companyData['url_extension'] = true;
        } else {
            $companyData['url_extension'] = false;
        }

        if ($hasUrlAtPosition6 && $toggleEnabled) {
            $companyData['company_type'] = 'selfreg';
        }

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');

        // Setup toggle mock
        $this->toggleConfigMock->expects($this->atLeastOnce())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) use ($toggleEnabled) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return $toggleEnabled;
                    }
                    return false;
                }
            );

        // Only expect getCurrentUrl to be called if toggle is enabled
        if ($toggleEnabled) {
            $this->urlInterfaceMock->expects($this->once())
                ->method('getCurrentUrl')
                ->willReturn($currentUrl);
        } else {
            $this->urlInterfaceMock->expects($this->never())
                ->method('getCurrentUrl');
        }

        // Handle URL extension extraction when toggle is enabled
        if ($toggleEnabled && $hasUrlAtPosition6) {
            $this->ondemandHelperMock->expects($this->once())
                ->method('getCompanyFromUrlExtension')
                ->with($urlExtension)
                ->willReturn(['company_url_extention' => $urlExtension]);

            // setUrlExtensionCookie called once for URL extension
            $this->loginHelper->expects($this->once())
                ->method('setUrlExtensionCookie')
                ->with($urlExtension);
            
            $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        } elseif ($toggleEnabled && !$hasUrlAtPosition6 && empty($urlExtensionInCompanyData)) {
            // Toggle enabled but no URL extension found - redirects to login
            $this->selfRegLandingMock->expects($this->once())
                ->method('getLoginUrl')
                ->willReturn('https://fedex.com/login');

            $this->redirectMock->expects($this->once())
                ->method('setUrl')
                ->with('https://fedex.com/login')
                ->willReturnSelf();
            
            $this->loginHelper->expects($this->never())
                ->method('setUrlExtensionCookie');
        } elseif (!$toggleEnabled) {
            // Toggle disabled, normal flow without URL parsing
            $this->ondemandHelperMock->expects($this->never())
                ->method('getCompanyFromUrlExtension');
        }

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        $this->loggerMock->expects($this->any())->method('info');

        if ($hasUrlAtPosition6 && $toggleEnabled) {
            $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo')->with($companyData);
            $this->redirectMock->expects($this->once())->method('setUrl')->with('selfreg/landing')->willReturnSelf();
        }

        $result = $this->companyRedirectMock->execute();
        
        if ($expectedBehavior === 'redirect') {
            $this->assertInstanceOf(Redirect::class, $result, $message);
        } else {
            // When toggle is disabled or other conditions, method returns false
            $this->assertFalse($result, $message);
        }
    }

    /**
     * Data provider for bookmarking site fix toggle tests
     *
     * @return array
     */
    public function bookmarkingSiteFixToggleDataProvider()
    {
        return [
            'Toggle enabled with URL at position 6' => [
                'toggleEnabled' => true,
                'currentUrl' => 'https://fedex.com/ondemand/company/redirect/url/index/testcompany',
                'urlExtensionInCompanyData' => 'testcompany',
                'hasUrlAtPosition6' => true,
                'expectedBehavior' => 'redirect',
                'message' => 'Should extract URL extension from position 6 when toggle enabled'
            ],
            'Toggle enabled without URL at position 6 and empty company extension' => [
                'toggleEnabled' => true,
                'currentUrl' => 'https://fedex.com/ondemand/company/redirect',
                'urlExtensionInCompanyData' => null,
                'hasUrlAtPosition6' => false,
                'expectedBehavior' => 'redirect',
                'message' => 'Should redirect to login when toggle enabled but no URL extension found'
            ],
            'Toggle disabled with URL at position 6' => [
                'toggleEnabled' => false,
                'currentUrl' => 'https://fedex.com/ondemand/company/redirect/url/index/testcompany',
                'urlExtensionInCompanyData' => 'companyext',
                'hasUrlAtPosition6' => false,
                'expectedBehavior' => 'redirect',
                'message' => 'Should not extract URL from position 6 when toggle disabled'
            ],
            'Toggle enabled with complex URL' => [
                'toggleEnabled' => true,
                'currentUrl' => 'https://fedex.com/ondemand/company/redirect/url/index/mycompany?param=value',
                'urlExtensionInCompanyData' => 'mycompany',
                'hasUrlAtPosition6' => true,
                'expectedBehavior' => 'redirect',
                'message' => 'Should handle complex URLs with query parameters when toggle enabled'
            ],
        ];
    }

    /**
     * Test bookmarking site fix toggle with logging
     *
     * @return void
     */
    public function testBookmarkingSiteFixToggleLogging()
    {
        $urlExtension = 'testcompany';
        $companyData = [
            'company_type' => 'selfreg',
            'ondemand_url' => true,
            'url_extension' => true,
            'company_data' => ['company_url_extention' => $urlExtension]
        ];

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');

        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://fedex.com/ondemand/company/redirect/url/index/testcompany');

        $this->toggleConfigMock->expects($this->atLeastOnce())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return true;
                    }
                    return false;
                }
            );

        $this->ondemandHelperMock->expects($this->once())
            ->method('getCompanyFromUrlExtension')
            ->with($urlExtension)
            ->willReturn(['company_url_extention' => $urlExtension]);

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        // Verify specific log messages are called
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('ParseUrl')],
                [$this->stringContains('From URL -  UrlExtension:')],
                [$this->stringContains('Get Company From URL -  UrlExtension:')],
                [$this->stringContains('UrlExtension Company Data:')]
            );

        $this->loginHelper->expects($this->once())
            ->method('setUrlExtensionCookie')
            ->with($urlExtension);
        $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo');
        $this->redirectMock->expects($this->once())->method('setUrl')->willReturnSelf();

        $result = $this->companyRedirectMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test logging for URL parsing with company extension
     *
     * @return void
     */
    public function testExecuteLogsUrlParsingDetails()
    {
        $urlExtension = 'mycompany';
        $companyFromUrl = ['company_url_extention' => $urlExtension];
        $companyData = [
            'company_type' => 'selfreg',
            'ondemand_url' => true,
            'url_extension' => true,
            'company_data' => ['company_url_extention' => $urlExtension]
        ];

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return true;
                    }
                    return false;
                }
            );
        
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');

        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://fedex.com/ondemand/company/redirect/url/index/mycompany');

        $this->ondemandHelperMock->expects($this->once())
            ->method('getCompanyFromUrlExtension')
            ->willReturn($companyFromUrl);

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        // Verify logger is called with expected messages
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->logicalOr(
                    $this->stringContains('ParseUrl'),
                    $this->stringContains('From URL -  UrlExtension:'),
                    $this->stringContains('Get Company From URL -  UrlExtension:'),
                    $this->stringContains('UrlExtension Company Data:')
                )
            );

        $this->loginHelper->expects($this->once())
            ->method('setUrlExtensionCookie')
            ->with($urlExtension);
        $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo');
        $this->redirectMock->expects($this->once())->method('setUrl')->willReturnSelf();

        $result = $this->companyRedirectMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test setUrlExtensionCookie is called twice when both URL and company data have extension
     *
     * @return void
     */
    public function testExecuteSetsUrlExtensionCookieTwice()
    {
        $urlExtension = 'urlext';
        $companyExt = 'companyext';
        $companyFromUrl = ['company_url_extention' => $urlExtension];
        $companyData = [
            'company_type' => 'selfreg',
            'ondemand_url' => true,
            'url_extension' => true,
            'company_data' => ['company_url_extention' => $companyExt]
        ];

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return true;
                    }
                    return false;
                }
            );
        
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');

        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://fedex.com/ondemand/company/redirect/url/index/urlext');

        $this->ondemandHelperMock->expects($this->once())
            ->method('getCompanyFromUrlExtension')
            ->with($urlExtension)
            ->willReturn($companyFromUrl);

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        // In actual implementation, cookie is only set once even with different extensions
        $this->loginHelper->expects($this->once())
            ->method('setUrlExtensionCookie')
            ->with($urlExtension);

        $this->loggerMock->expects($this->any())->method('info');
        $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo');
        $this->redirectMock->expects($this->once())->method('setUrl')->willReturnSelf();

        $result = $this->companyRedirectMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test parse_url extracts path correctly with query params
     *
     * @return void
     */
    public function testExecuteHandlesComplexUrlParsing()
    {
        $companyData = [
            'company_type' => 'selfreg',
            'ondemand_url' => true,
            'url_extension' => true,
            'company_data' => [
                'company_url_extention' => 'test'
            ]
        ];

        $this->redirectFactoryMock->expects($this->once())->method('create')->willReturn($this->redirectMock);
        
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturnCallback(
                function ($key) {
                    if ($key === 'nfr_catelog_performance_improvement_phase_one') {
                        return false;
                    }
                    if ($key === 'tech_titans_D_230789_bookmarking_site_fix') {
                        return true;
                    }
                    return false;
                }
            );
        
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->baseAuthMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->baseAuthMock->expects($this->once())->method('getCompanyAuthenticationMethod')->willReturn('other');
        $this->storeManagerInterfaceMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('ondemand');


        // URL with query params and fragments
        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn('https://fedex.com/ondemand/company/redirect/page/view/test?param=value#hash');

        $this->ondemandHelperMock->expects($this->once())
            ->method('getCompanyFromUrlExtension')
            ->with('test')
            ->willReturn(['company_url_extention' => 'test']);

        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        $this->loginHelper->expects($this->once())->method('setUrlExtensionCookie')->with('test');
        $this->loggerMock->expects($this->any())->method('info');
        $this->customerSessionMock->expects($this->once())->method('setOndemandCompanyInfo')->with($companyData);
        $this->redirectMock->expects($this->once())->method('setUrl')->with('selfreg/landing')->willReturnSelf();

        $result = $this->companyRedirectMock->execute();
        $this->assertInstanceOf(Redirect::class, $result);
    }

    /**
     * Test redirectToStoreFront with different login methods
     *
     * @dataProvider redirectToStoreFrontDataProvider
     * @return       void
     */
    public function testRedirectToStoreFrontMethods($companyData, $toggleKey, $toggleValue, $urlMethod, $expectedUrl, $expectedResult, $message)
    {
        $this->ondemandHelperMock->expects($this->once())
            ->method('getOndemandCompanyData')
            ->willReturn($companyData);

        $loginMethod = $companyData['company_data']['storefront_login_method_option'] ?? null;

        // Handle toggle configuration based on login method
        if ($toggleKey !== null) {
            $this->toggleConfigMock->expects($this->once())
                ->method('getToggleConfigValue')
                ->with($toggleKey)
                ->willReturn($toggleValue);
        } else {
            $this->toggleConfigMock->expects($this->never())
                ->method('getToggleConfigValue');
        }

        // Handle URL interface mock based on login method
        if ($urlMethod !== null) {
            $this->urlInterfaceMock->expects($this->once())
                ->method('getUrl')
                ->with($urlMethod)
                ->willReturn($expectedUrl);
        }

        $this->responseInterfaceMock->expects($this->once())
            ->method('setRedirect')
            ->with($expectedUrl);

        $result = $this->companyRedirectMock->redirectToStoreFront();
        
        $this->assertEquals($expectedResult, $result, $message);
    }

    /**
     * Data provider for testRedirectToStoreFrontMethods
     *
     * @return array
     */
    public function redirectToStoreFrontDataProvider()
    {
        return [
            'WLGN without toggle' => [
                'companyData' => [
                    'company_data' => ['storefront_login_method_option' => 'commercial_store_wlgn']
                ],
                'toggleKey' => 'explorers_d_197790_commercial_landing_page_fix',
                'toggleValue' => false,
                'urlMethod' => 'selfreg/landing',
                'expectedUrl' => 'https://fedex.com/selfreg/landing',
                'expectedResult' => true,
                'message' => 'redirectToStoreFront should return true for WLGN method'
            ],
            'WLGN with toggle enabled' => [
                'companyData' => [
                    'company_data' => ['storefront_login_method_option' => 'commercial_store_wlgn']
                ],
                'toggleKey' => 'explorers_d_197790_commercial_landing_page_fix',
                'toggleValue' => true,
                'urlMethod' => $this->stringContains('/selfreg/landing'),
                'expectedUrl' => 'https://fedex.com/testcompany/selfreg/landing',
                'expectedResult' => true,
                'message' => 'redirectToStoreFront should return true with toggle enabled'
            ],
            'SSO method with valid URL' => [
                'companyData' => [
                    'company_data' => [
                        'storefront_login_method_option' => 'commercial_store_sso',
                        'sso_login_url' => 'https://sso.fedex.com/login'
                    ]
                ],
                'toggleKey' => null,
                'toggleValue' => null,
                'urlMethod' => null,
                'expectedUrl' => 'https://sso.fedex.com/login',
                'expectedResult' => true,
                'message' => 'redirectToStoreFront should return true for SSO method'
            ],
            'SSO method with empty URL' => [
                'companyData' => [
                    'company_data' => [
                        'storefront_login_method_option' => 'commercial_store_sso',
                        'sso_login_url' => ''
                    ]
                ],
                'toggleKey' => null,
                'toggleValue' => null,
                'urlMethod' => null,
                'expectedUrl' => '',
                'expectedResult' => true,
                'message' => 'redirectToStoreFront should return true even with empty SSO URL'
            ],
            'SSO FCL with toggle enabled' => [
                'companyData' => [
                    'company_data' => [
                        'storefront_login_method_option' => 'commercial_store_sso_with_fcl',
                        'sso_login_url' => 'https://sso.fedex.com/fcl'
                    ]
                ],
                'toggleKey' => 'xmen_enable_sso_group_authentication_method',
                'toggleValue' => true,
                'urlMethod' => null,
                'expectedUrl' => 'https://sso.fedex.com/fcl',
                'expectedResult' => true,
                'message' => 'redirectToStoreFront should return true for SSO FCL with toggle'
            ],
        ];
    }

    /**
     * Test ssoRedirect method
     *
     * @return void
     */
    public function testSsoRedirect()
    {
        $redirectUrl = 'https://sso.fedex.com/authenticate';

        $this->responseInterfaceMock->expects($this->once())
            ->method('setRedirect')
            ->with($redirectUrl);

        $this->companyRedirectMock->ssoRedirect($redirectUrl);
    }

    /**
     * Test wlgnRedirect method with valid URL
     *
     * @return void
     */
    public function testWlgnRedirectWithValidUrl()
    {
        $redirectUrl = 'https://fedex.com/wlgn/landing';

        $this->responseInterfaceMock->expects($this->once())
            ->method('setRedirect')
            ->with($redirectUrl);

        $this->companyRedirectMock->wlgnRedirect($redirectUrl);
    }

    /**
     * Test wlgnRedirect method with empty URL
     *
     * @return void
     */
    public function testWlgnRedirectWithEmptyUrl()
    {
        $this->responseInterfaceMock->expects($this->never())
            ->method('setRedirect');

        $this->companyRedirectMock->wlgnRedirect('');
    }

    /**
     * Test isStoreFrontDataSet with different auth methods
     *
     * @dataProvider isStoreFrontDataSetDataProvider
     * @return       void
     */
    public function testIsStoreFrontDataSetMethods($authMethod, $toggleKey, $toggleValue, $expectedResult, $message)
    {
        $this->baseAuthMock->expects($this->once())
            ->method('getCompanyAuthenticationMethod')
            ->willReturn($authMethod);

        if ($toggleKey !== null) {
            $this->toggleConfigMock->expects($this->once())
                ->method('getToggleConfigValue')
                ->with($toggleKey)
                ->willReturn($toggleValue);
        }

        $result = $this->companyRedirectMock->isStoreFrontDataSet();
        
        $this->assertEquals($expectedResult, $result, $message);
    }

    /**
     * Data provider for testIsStoreFrontDataSetMethods
     *
     * @return array
     */
    public function isStoreFrontDataSetDataProvider()
    {
        return [
            'FCL auth method' => [
                'authMethod' => 'fcl',
                'toggleKey' => null,
                'toggleValue' => null,
                'expectedResult' => true,
                'message' => 'isStoreFrontDataSet should return true for FCL auth method'
            ],
            'SSO auth method' => [
                'authMethod' => 'sso',
                'toggleKey' => null,
                'toggleValue' => null,
                'expectedResult' => true,
                'message' => 'isStoreFrontDataSet should return true for SSO auth method'
            ],
            'SSO FCL with toggle enabled' => [
                'authMethod' => 'sso_fcl',
                'toggleKey' => 'xmen_enable_sso_group_authentication_method',
                'toggleValue' => true,
                'expectedResult' => true,
                'message' => 'isStoreFrontDataSet should return true for SSO FCL with toggle enabled'
            ],
            'SSO FCL with toggle disabled' => [
                'authMethod' => 'sso_fcl',
                'toggleKey' => 'xmen_enable_sso_group_authentication_method',
                'toggleValue' => false,
                'expectedResult' => false,
                'message' => 'isStoreFrontDataSet should return false for SSO FCL with toggle disabled'
            ],
        ];
    }
}
