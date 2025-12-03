<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\Commercial\Test\Unit\ViewModel;

use Fedex\Commercial\ViewModel\CommercialSsoConfiguration;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Commercial\Helper\CommercialHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Search\Helper\Data;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\ViewModel\MvpHelper as MvpHelperViewModel;

/**
 * Test class for SdeSsoConfiguration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CommercialSsoConfigurationTest extends TestCase
{
    protected $toggleConfigMock;
    protected $mvpHelperViewModelMock;
    protected $ssoConfig;
    protected $selfRegMock;
    protected $customerSessionMock;
    protected $customerSessionFactoryMock;
    protected $customerRepositoryMock;
    protected $customerInterfaceMock;
    protected $urlInterfaceMock;
    protected $commercialHelperMock;
    protected $sdeHelperMock;
    protected $searchHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $commercialSsoConfigurationMock;
    /**
     * @var Session $customerSession
     */
    protected $customerSession;

    /**
     * @var Http $requestMock
     */
    protected $requestMock;
    /**
     * @var ScopeConfigInterface $scopeConfigMock
     */
    protected $scopeConfigMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(
                [
                    'getValue',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

       $this->mvpHelperViewModelMock = $this->getMockBuilder(MvpHelperViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSharedCatalogPermissionEnabled','isEnableStopRedirectMvpAddToCart'])
            ->getMock();     

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->setMethods(['getFullActionName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoConfig = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['getGeneralConfig','getConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['isSelfRegCustomerAdmin','isSelfRegCompany'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomer',
                    'getId',
                    'getCustomerCompany',
                    'getFirstname',
                    'getCustomerId',
                    'isLoggedIn'
                ]
            )
            ->getMock();

        $this->customerSessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->commercialHelperMock = $this->getMockBuilder(CommercialHelper::class)
        ->setMethods(['isGlobalCommercialCustomer', 'getCompanyInfo'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore', 'getLogoutUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchHelperMock = $this->getMockBuilder(Data::class)
        ->setMethods(['getResultUrl',
                      'getSuggestUrl',
                      'getMinQueryLength',
                      'getQueryParamName',
                      'getEscapedQueryText',
                      'getMaxQueryLength'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->commercialSsoConfigurationMock = $this->objectManager->getObject(
            CommercialSsoConfiguration::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'customerSession' => $this->customerSessionFactoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'request' => $this->requestMock,
                'urlInterface' => $this->urlInterfaceMock,
                'commercialHelper'=> $this->commercialHelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'Data'=> $this->searchHelperMock,
                'selfReg' => $this->selfRegMock,
                'ssoConfig' => $this->ssoConfig,
                'toggleConfig' => $this->toggleConfigMock,
                'session' => $this->customerSessionMock,
                'MvpHelperViewModel' => $this->mvpHelperViewModelMock
            ]
        );
    }

    /**
     * @test testGetCommercialCustomerName
     */
    public function testGetCommercialCustomerName()
    {
        $customerName = 'Shivani Kanswal';

        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn($customerName);

        $this->assertEquals($customerName, $this->commercialSsoConfigurationMock->getCommercialCustomerName());
    }

    /**
     * @test testGetCommercialCustomerNameWithoutName
     */
    public function testGetCommercialCustomerNameWithoutName()
    {
        $customerName = '';

        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals($customerName, $this->commercialSsoConfigurationMock->getCommercialCustomerName());
    }

    /**
     * @test testIsCommercialCustomerWithTrue
     */
    public function testIsSdeCustomerWithTrue()
    {
        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->customerSessionMock->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(null);

        $this->assertEquals(true, $this->commercialSsoConfigurationMock->isSdeCustomer());
    }

    /**
     * @test testIsCommercialCustomerWithFalse
     */
    public function testIsSdeCustomerWithFalse()
    {
        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->assertEquals(false, $this->commercialSsoConfigurationMock->isSdeCustomer());
    }

    /**
     * @test testcommercialCustomerSession
     */
    public function testCommercialCustomerSession()
    {
        $this->customerSessionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerSessionMock);

        $this->assertEquals(
            $this->customerSessionMock,
            $this->commercialSsoConfigurationMock->commercialCustomerSession()
        );
    }
    /**
     * @test testgetCommercialCurrentUrl
     */
    public function testgetCommercialCurrentUrl()
    {
        $url = 'https://staging3.office.fedex.com/l6site51/customer/account/';
        $this->urlInterfaceMock->expects($this->once())
            ->method('getCurrentUrl')
            ->willReturn($url);
        $this->assertEquals(
            $url,
            $this->commercialSsoConfigurationMock->getCommercialCurrentUrl()
        );
    }
    /**
     * @test testgetCommercialCurrentUrl
     */
    public function testisGlobalCommercialCustomerRequest()
    {
        $this->commercialHelperMock->expects($this->once())
        ->method('isGlobalCommercialCustomer')
        ->willReturn(true);

        $this->assertEquals(
            true,
            $this->commercialSsoConfigurationMock->isGlobalCommercialCustomerRequest()
        );
    }
    /**
     * @test testgetIsRequestFromSdeStore
     */
    public function testgetIsRequestFromSdeStore()
    {
        $this->sdeHelperMock->expects($this->any())
        ->method('getIsSdeStore')
        ->willReturn(true);

        $this->assertEquals(
            true,
            $this->commercialSsoConfigurationMock->getIsRequestFromSdeStore()
        );
    }
    /**
     * @test testgetVmResultUrl
     */
    public function testgetVmResultUrl()
    {
        $result = 'Search';
        $this->searchHelperMock->expects($this->any())
        ->method('getResultUrl')
        ->willReturn($result);

        $this->assertEquals(
            null,
            $this->commercialSsoConfigurationMock->getVmResultUrl()
        );
    }
    /**
     * @test testgetVmSuggestUrl
     */
    public function testgetVmSuggestUrl()
    {
        $result = 'Search';
        $this->searchHelperMock->expects($this->any())
        ->method('getSuggestUrl')
        ->willReturn($result);

        $this->assertEquals(
            null,
            $this->commercialSsoConfigurationMock->getVmSuggestUrl()
        );
    }
     /**
      * @test testgetVmMinQueryLength
      */
    public function testgetVmMinQueryLength()
    {
        $result = 'Search';
        $this->searchHelperMock->expects($this->any())
        ->method('getMinQueryLength')
        ->willReturn($result);

        $this->assertEquals(
            null,
            $this->commercialSsoConfigurationMock->getVmMinQueryLength()
        );
    }
     /**
      * @test testgetVmQueryParamName
      */
    public function testgetVmQueryParamName()
    {
        $result = 'Search';
        $this->searchHelperMock->expects($this->any())
        ->method('getQueryParamName')
        ->willReturn($result);

        $this->assertEquals(
            null,
            $this->commercialSsoConfigurationMock->getVmQueryParamName()
        );
    }
     /**
      * @test testgetVmMaxQueryLength
      */
    public function testgetVmMaxQueryLength()
    {
        $result = 'Search';
        $this->searchHelperMock->expects($this->any())
        ->method('getMaxQueryLength')
        ->willReturn($result);

        $this->assertEquals(
            null,
            $this->commercialSsoConfigurationMock->getVmMaxQueryLength()
        );
    }
     /**
      * @test testgetVmEscapedQueryText
      */
    public function testgetVmEscapedQueryText()
    {
        $result = 'Search';
        $this->searchHelperMock->expects($this->any())
        ->method('getMaxQueryLength')
        ->willReturn($result);

        $this->assertEquals(
            null,
            $this->commercialSsoConfigurationMock->getVmEscapedQueryText()
        );
    }
     /**
      * @test testidentifyUserRequest
      */
    public function testidentifyUserRequest()
    {
        $_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.4 (KHTML, like Gecko)
         Edge/22.0.1229.94 Safari/537.4';
         $this->assertIsString($this->commercialSsoConfigurationMock->identifyUserRequest());
    }

    /**
     * @test testGetSdeLogoutUrl
     */
    public function testGetSdeLogoutUrl()
    {
        $sdeLogoutUrl = 'test-url';
        $this->sdeHelperMock->expects($this->any())
            ->method('getLogoutUrl')
            ->willReturn($sdeLogoutUrl);

        $this->assertEquals($sdeLogoutUrl, $this->commercialSsoConfigurationMock->getSdeLogoutUrl());
    }

    /**
     * @test testIsSelfRegAdmin
     */
    public function testIsSelfRegAdmin()
    {

        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->assertEquals(true, $this->commercialSsoConfigurationMock->isSelfRegAdmin());
    }

    /**
     * @test testIsSelfRegCompany
     */
    public function testIsSelfRegCompany()
    {

        $this->selfRegMock->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(true);
        $this->assertEquals(true, $this->commercialSsoConfigurationMock->isSelfRegCompany());
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
        $this->assertEquals($value, $this->commercialSsoConfigurationMock->getCanvaDesignEnabled());
    }

    public function testGetSelfRegLogoutUrl()
    {
        $redirectUrl = "https://staging3.office.fedex.com/selfreg/logout";
        $this->urlInterfaceMock->expects($this->any())
        ->method('getUrl')->willReturn($redirectUrl);
        $wlgnLogoutPageUrl = 'https://api.test.office.fedex.com/customer/fedexoffice/v1/logout';
        $queryParameter = 'redirect';
        $this->ssoConfig
            ->method('getGeneralConfig')->withConsecutive(
                ['fcl_logout_url'],
                ['fcl_logout_query_param']
            )
            ->willReturnOnConsecutiveCalls($wlgnLogoutPageUrl, $queryParameter);
        $wlgnLogoutUrl = $wlgnLogoutPageUrl.'?'.$queryParameter.'='.$redirectUrl;
        $this->assertEquals($wlgnLogoutUrl, $this->commercialSsoConfigurationMock->getSelfRegLogoutUrl());
    }

    /**
     * @test testGetCommercialLogoutInfo
     */
    public function testGetCommercialLogoutInfo()
    {
        $companyInfo = [
            'company_id' => 1,
            'login_method' => 'commercial_store_wlgn',
            'is_sensitive_data_enabled' => 0,
            'logoutUrl' => 'URL'
        ];
        $this->commercialHelperMock->expects($this->once())
        ->method('getCompanyInfo')
        ->willReturn($companyInfo);
        $this->assertNotNull($this->commercialSsoConfigurationMock->getCommercialLogoutInfo());
    }

    /**
     * @test testGetCommercialLogoutInfoWithEmpty
     */
    public function testGetCommercialLogoutInfoWithEmpty()
    {
        $companyInfo = [];
        $this->commercialHelperMock->expects($this->once())
        ->method('getCompanyInfo')
        ->willReturn($companyInfo);
        $this->assertFalse($this->commercialSsoConfigurationMock->getCommercialLogoutInfo());
    }

    /**
     * testGetOrCreateCustomerSession
     * @return void
     */
    public function testGetOrCreateCustomerSession()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $result = $this->commercialSsoConfigurationMock->getOrCreateCustomerSession();
        $this->assertSame($this->customerSessionMock, $result);
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
        $this->assertEquals(true, $this->commercialSsoConfigurationMock->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

    /**
     * testtestIsSharedCatalogPermissionEnabled
     * @return void
     */
    public function testIsSharedCatalogPermissionEnabled()
    {
        $this->mvpHelperViewModelMock->expects($this->any())
            ->method('isSharedCatalogPermissionEnabled')
            ->willReturn(true);
        $this->assertEquals(null, $this->commercialSsoConfigurationMock->isSharedCatalogPermissionEnabled());
    }

    /**
     * testIsEnableStopRedirectMvpAddToCart
     * @return void
     */
    public function testIsEnableStopRedirectMvpAddToCart()
    {
        $this->mvpHelperViewModelMock->expects($this->any())
            ->method('isEnableStopRedirectMvpAddToCart')
            ->willReturn(true);
        $this->assertEquals(null, $this->commercialSsoConfigurationMock->isEnableStopRedirectMvpAddToCart());
    }

    /**
     * @test testIsImprovingPasswordToggle
     */
    public function testIsImprovingPasswordToggle()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('sgc_improving_visibility_to_change_password')
            ->willReturn(true);

        $this->assertTrue($this->commercialSsoConfigurationMock->isimprovingpasswordtoggle());
    }

}