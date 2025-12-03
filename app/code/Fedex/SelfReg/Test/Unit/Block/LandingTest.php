<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Block;

use Fedex\SelfReg\Block\Landing;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Framework\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Delivery\Helper\Data;

class LandingTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Fedex\SelfReg\Helper\SelfReg & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $selfRegHelperMock;
    protected $ssoConfigurationMock;
    protected $urlInterface;
    protected $helperData;
    protected $scopeConfig;
    protected $logoMock;
    protected $storeManagerInterfaceMock;
    protected $storeInterfaceMock;
    /**
     * @var (\Magento\Framework\App\Request\Http & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $httpRequestMock;
    protected $sessionFactoryMock;
    protected $customerSessionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $laningObj;
    /**
     * @inheritDoc
     * B-1145896
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegHelperMock = $this->getMockBuilder(SelfReg::class)
            ->setMethods(['selfRegWlgnLogin', 'isSelfRegCompany', 'isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['getLoginPopupConfig', 'getGeneralConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl', 'getBaseUrl'])
            ->getMockForAbstractClass();

        $this->helperData = $this->getMockBuilder(Data::class)
            ->setMethods(['getCompanyLogo'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->logoMock = $this->getMockBuilder(Logo::class)
            ->setMethods(['getLogoSrc', 'getLogoAlt', 'getLogoWidth', 'getLogoHeight'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->setMethods(['getCode','getStoreId','getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        // B-1326232 - RT-ECVS-Self-reg login error page
        $this->httpRequestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionFactoryMock = $this->getMockBuilder(\Magento\Customer\Model\SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelfRegLoginError'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->laningObj = $this->objectManager->getObject(
            Landing::class,
            [
                'selfRegHelper' => $this->selfRegHelperMock,
                'ssoConfiguration' => $this->ssoConfigurationMock,
                'url' => $this->urlInterface,
                'logo' => $this->logoMock,
                'storeManagerInterface' => $this->storeManagerInterfaceMock,
                'httpRequest' => $this->httpRequestMock,
                'sessionFactory' => $this->sessionFactoryMock,
                'helperData' => $this->helperData,
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    /**
     *
     * @test  testGetLogoSrc
     */
    public function testGetLogoSrc()
    {
        $this->helperData->expects($this->any())->method('getCompanyLogo')->willReturn(null);
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn(false);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getStoreId')->willReturn('108');
        $logoUrl = "https://staging3.office,fedex.com/media/logo.png";
        $this->logoMock->expects($this->any())->method('getLogoSrc')->willReturn($logoUrl);
        $this->assertEquals($logoUrl, $this->laningObj->getLogoSrc());
    }

    public function testGetLogoSrcWithCompanyLogo()
    {
        $logoUrl = "https://staging3.office,fedex.com/media/logo.png";
        $this->helperData->expects($this->any())->method('getCompanyLogo')->willReturn($logoUrl);
        $this->assertEquals($logoUrl, $this->laningObj->getLogoSrc());
    }

    public function testGetLogoSrcWithStoreLogo()
    {
        $mediaurl = "https://staging3.office,fedex.com/media/";
        $logoUrl = "https://staging3.office,fedex.com/media/logo/test.png";
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn("test.png");
        $this->storeManagerInterfaceMock->expects($this->any())
            ->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getStoreId')->willReturn('108');
        $this->storeInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn($mediaurl);
        $this->assertEquals($logoUrl, $this->laningObj->getLogoSrc());
    }

    /**
     *
     * @test  testGetLogoAlt
     */
    public function testGetLogoAlt()
    {
        $logoAlt = "Store Logo";
        $this->logoMock->expects($this->any())->method('getLogoAlt')->willReturn($logoAlt);
        $this->assertEquals($logoAlt, $this->laningObj->getLogoAlt());
    }

    /**
     *
     * @test  testGetLogoWidth
     */
    public function testGetLogoWidth()
    {
        $logoWidth = 23;
        $this->logoMock->expects($this->any())->method('getLogoWidth')->willReturn($logoWidth);
        $this->assertEquals($logoWidth, $this->laningObj->getLogoWidth());
    }

    /**
     *
     * @test  testGetLogoHeight
     */
    public function testGetLogoHeight()
    {
        $logoHeight = 23;
        $this->logoMock->expects($this->any())->method('getLogoHeight')->willReturn($logoHeight);
        $this->assertEquals($logoHeight, $this->laningObj->getLogoHeight());
    }

    /**
     *
     * @test  testGetTitle
     */
    public function testGetTitle()
    {
        $title = "Register / Login Your";
        $this->ssoConfigurationMock->expects($this->any())->method('getLoginPopupConfig')
        ->with('login_popup_message_heading')
        ->willReturn($title);
        $this->assertEquals($title, $this->laningObj->getTitle());
    }

    /**
     *
     * @test  testGetDescription
     */
    public function testGetDescription()
    {
        $desc = "Descerp";
        $this->ssoConfigurationMock->expects($this->any())->method('getLoginPopupConfig')
        ->with('login_popup_message')
        ->willReturn($desc);
        $this->assertEquals($desc, $this->laningObj->getDescription());
    }

    /**
     *
     * @test  testGetRegistrationBtnLabel
     */
    public function testGetRegistrationBtnLabel()
    {
        $reg = "Register";
        $this->ssoConfigurationMock->expects($this->any())->method('getLoginPopupConfig')
        ->with('create_user_button_text')
        ->willReturn($reg);
        $this->assertEquals($reg, $this->laningObj->getRegistrationBtnLabel());
    }

    /**
     *
     * @test  testGetRegistrationBtnLabel
     */
    public function testgetLoginBtnLabel()
    {
        $login = "Login";
        $this->ssoConfigurationMock->expects($this->any())->method('getLoginPopupConfig')
        ->with('login_button_text')
        ->willReturn($login);
        $this->assertEquals($login, $this->laningObj->getLoginBtnLabel());
    }

    /**
     *
     * @test  testGetLoginUrl
     */
    public function testGetLoginUrl()
    {
        $url = "https://staging3.office.fedex.com/me/selfreg/login";
        $loginPageUrl = "https://wwwwtest.fedex.com/login";
        $query = "redirect";
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn(false);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getStoreId')->willReturn('108');
        $this->urlInterface->expects($this->any())->method('getUrl')->willReturn($url);
        $logoUrl = "https://staging3.office,fedex.com/media/logo.png";
        $this->ssoConfigurationMock
            ->expects($this->exactly(2))
            ->method('getGeneralConfig')
            ->withConsecutive(['wlgn_login_page_url'], ['query_parameter'])
            ->willReturnOnConsecutiveCalls($loginPageUrl, $query);
        $expected = $loginPageUrl . '?' . $query . '=' . $url;
        $this->assertEquals($expected, $this->laningObj->getLoginUrl());
    }

    public function testGetLoginUrlToggleCondition()
    {
        $url = "https://staging3.office.fedex.com/me/selfreg/login";
        $loginPageUrl = "https://wwwwtest.fedex.com/login";
        $query = "redirect";
        $this->urlInterface->expects($this->any())->method('getUrl')->willReturn($url);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getStoreId')->willReturn('108');
        $this->urlInterface->expects($this->any())->method('getUrl')->willReturn($url);
        $this->ssoConfigurationMock
            ->expects($this->exactly(2))
            ->method('getGeneralConfig')
            ->withConsecutive(['wlgn_login_page_url'], ['query_parameter'])
            ->willReturnOnConsecutiveCalls($loginPageUrl, $query);
        $expected = $loginPageUrl . '?' . $query . '=' . $url;
        $this->assertNotNUll($expected, $this->laningObj->getLoginUrl());
    }

    /**
     *
     * @test  testGetLoginUrl
     */
    public function testGetBaseUrl()
    {
        $url = "https://staging3.office.fedex.com/me/";

        $this->urlInterface->expects($this->any())->method('getBaseUrl')->willReturn($url);

        $this->assertEquals($url, $this->laningObj->getBaseUrl());
    }
    
    /**
     *
     * @test  testGetRegistrationUrl
     */
    public function testGetRegistrationUrlToggleCondition()
    {
        $registrationUrl = "https://wwwwtest.fedex.com/register/";
        $param = "?store=stag3_marketplace";
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')
        ->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')->willReturn('me');
        $this->ssoConfigurationMock
            ->expects($this->exactly(2))
            ->method('getGeneralConfig')
            ->withConsecutive(['register_url'], ['register_url_param'])
            ->willReturnOnConsecutiveCalls($registrationUrl, $param);

        $expected = $registrationUrl . $param . '/' . 'me/oauth';
        $this->assertEquals($expected, $this->laningObj->getRegistrationUrl());
    }

    /**
     * B-1326232 - RT-ECVS-Self-reg login error page
     * @test  testGetLoginErrorMsg
     */
    public function testGetLoginErrorMsg()
    {
        $errorMsg = 'Access Denied.';
        $this->sessionFactoryMock->expects($this->any())->method('create')->willReturn($this->customerSessionMock);
        $this->customerSessionMock->expects($this->any())->method('getSelfRegLoginError')->willReturn($errorMsg);
        $this->assertEquals($errorMsg, $this->laningObj->getLoginErrorMsg());
    }

    /**
     * GetStoreConfigData
     * B-1805640
     */
    public function testGetStoreConfigData()
    {
        $path = 'sso/login_error_popup/login_error_popup_message';
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')
        ->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getCode')->willReturn('me');
        $this->scopeConfig->expects($this->any())->method('getValue')->willReturn("test.png");
        $this->assertEquals('test.png', $this->laningObj->getStoreConfigData($path));
    }
}
