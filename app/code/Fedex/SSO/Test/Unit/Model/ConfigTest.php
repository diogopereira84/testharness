<?php

/**
 * @category Fedex
 * @package  Fedex_SSO
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Model;

use Fedex\NotificationBanner\ViewModel\NotificationBanner;
use Fedex\SSO\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            ['getValue', 'isSetFlag']
        );

        $this->objectManager = new ObjectManager($this);
        $this->configMock = $this->objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * @test isEnabled
     * @param bool $isEnabled
     * @dataProvider getIsEnabledDataProvider
     */
    public function testIsEnabled(bool $isEnabled): void
    {
        $this->scopeConfigMock->expects($this->once())->method('isSetFlag')
            ->with(Config::XML_PATH_FEDEX_SSO_IS_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($isEnabled);
        $this->assertEquals($isEnabled, $this->configMock->isEnabled());
    }

    /**
     * @return array
     */
    public function getIsEnabledDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * Function test Is Get Login Page URL
     */
    public function testGetLoginPageURL()
    {
        $loginUrl = 'https://someurl.com';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_LOGIN_PAGE_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($loginUrl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($loginUrl, $config->getLoginPageURL());
    }

    /**
     * Function test Is Get Query Parameter
     */
    public function testGetQueryParameter()
    {
        $queryParam = 'redirectUrl';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_QUERY_PARAMETER, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($queryParam);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($queryParam, $config->getQueryParameter());
    }
    /**
     * Function test Is Get Register URL
     */
    public function testGetRegisterUrl()
    {
        $registerUrl = 'registerUrl';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_REGISTER_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($registerUrl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($registerUrl, $config->getRegisterUrl());
    }

    /**
     * Function test Is Get Register URL Parameter
     */
    public function testGetRegisterUrlParameter()
    {
        $registerUrlwithparameter = 'registerUrlparameter';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_REGISTER_URL_PARAM, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($registerUrlwithparameter);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($registerUrlwithparameter, $config->getRegisterUrlParameter());
    }
    /**
     * Function test Is Get Profile Api URL
     */
    public function testGetProfileApiUrl()
    {
        $profileurl = 'profileurl';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_PROFILE_API_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($profileurl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($profileurl, $config->getProfileApiUrl());
    }
    /**
     * Function test Is Get FCL Profile URL
     */
    public function testGetFclMyProfileUrl()
    {
        $fclprofileUrl = 'fclprofileurl';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_FCL_MY_PROFILE_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($fclprofileUrl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($fclprofileUrl, $config->getFclMyProfileUrl());
    }

    /**
     * Function test Is Get FCL Logout URL
     */
    public function testGetFclLogoutUrl()
    {
        $fclLogoutUrl = 'fclLogoutUrl';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_FCL_LOGOUT_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($fclLogoutUrl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($fclLogoutUrl, $config->getFclLogoutUrl());
    }

    /**
     * Function test Is Get FCL Logout URL  With Parameter
     */
    public function testGetFclLogoutQueryParam()
    {
        $fclLogout = 'FclLogout';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_FCL_LOGOUT_QUERY_PARAM, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($fclLogout);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($fclLogout, $config->getFclLogoutQueryParam());
    }

    /**
     * Function test Is Get Profile Mockup
     */
    public function testGetProfileMockupJson()
    {
        $profilemockup = 'profilemockup';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_PROFILE_MOCKUP_JSON, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($profilemockup);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($profilemockup, $config->getProfileMockupJson());
    }

    /**
     * Function test IsWireMockLoginEnable
     */
    public function testIsWireMockLoginEnable()
    {
        $profilemockup = true;
        $this->scopeConfigMock->expects($this->any())->method('getValue')
            ->willReturn($profilemockup);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($profilemockup, $config->isWireMockLoginEnable());
    }

    /**
     * Function test getWireMockProfileUrl
     */
    public function testGetWireMockProfileUrl()
    {
        $profilemockupUrl = 'https://testmire.fedex.com';
        $this->scopeConfigMock->expects($this->any())->method('getValue')
            ->willReturn($profilemockupUrl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($profilemockupUrl, $config->getWireMockProfileUrl());
    }

    /**
     * Function test Get Contact Information Profile URL
     */
    public function testGetContactInformationProfileUrl()
    {
        $contactInfoProfileUrl = 'https://www.fedex.com/contact-info/';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(Config::XML_PATH_FEDEX_SSO_CONTACT_INFORMATION_PROFILE_URL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($contactInfoProfileUrl);
        $config = new Config($this->scopeConfigMock);
        $this->assertEquals($contactInfoProfileUrl, $config->getcontactinformationprofileurl());
    }

}
