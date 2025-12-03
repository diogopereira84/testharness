<?php

namespace Fedex\SSO\Block;

use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\View\Element\Template\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Login\Helper\Login;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\ViewModel\UnfinishedProjectNotification;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;


class RetailLoginInfoTest extends \PHPUnit\Framework\TestCase
{

	/**
  * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
  */
 protected $contextMock;
 protected $toggleConfigMock;
 protected $ssoConfigurationMock;
 /**
  * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
  */
 protected $objectManager;
 protected $retaillogininfo;
 protected $template = 'Fedex_SSO::header/singin_signup.phtml';

    /**
     * @var UnfinishedProjectNotification $myProjectManager
     */
    protected UnfinishedProjectNotification $myProjectManager;

	 protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->myProjectManager = $this->getMockBuilder(UnfinishedProjectNotification::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCartPageUnfinisedPopupEnable', 'isProjectAvailable', 'isAccessToWorkspaceToggleEnable'])
            ->getMock();

        $this->ssoConfigurationMock = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['isFclCustomer',
            'getFclCustomerName',
            'isCommercialCustomer',
            'getGeneralConfig',
            'getDefaultShippingAddress',
            'getFclCustomerInf',
            'getConfigValue',
            'getFclCustomerInfo'])
            ->disableOriginalConstructor()
            ->getMock();

	    $this->objectManager = new ObjectManager($this);
        $this->retaillogininfo = $this->objectManager->getObject(
	         RetailLoginInfo::class,
            [
                'context' => $this->contextMock,
                'ssoConfiguration' => $this->ssoConfigurationMock,
                'toggleConfig' => $this->toggleConfigMock,
                'myProjectManager' => $this->myProjectManager
            ]
        );
    }

    public function testGetTemplateWhenNotFclCustomer()
    {
        $this->ssoConfigurationMock->expects($this->any())
            ->method('isFclCustomer')
            ->willReturn(false);
        $result = $this->retaillogininfo->getTemplate();
        $this->assertEquals('Fedex_SSO::header/singin_signup.phtml', $result);
    }

    public function testGetTemplateWhenFclCustomer()
    {
        $this->ssoConfigurationMock->expects($this->any())
        ->method('isFclCustomer')
        ->willReturn(true);
        $result = $this->retaillogininfo->getTemplate();
        $this->assertEquals('Fedex_SSO::header/login_info.phtml', $result);
    }

     /**
     * Test getFclCustomerName
     */
    public function testGetFclCustomerName()
    {
        $name = 'test';
        $this->ssoConfigurationMock->expects($this->any())->method('getFclCustomerName')->willReturn($name);
        $this->assertsame($name, $this->retaillogininfo->getFclCustomerName());
    }

    /**
     * Test isFclCustomer
     */
    public function testisFclCustomer()
    {
        $this->ssoConfigurationMock->expects($this->any())
        ->method('isFclCustomer')
        ->willReturn(true);
        $this->retaillogininfo->isFclCustomer();
    }

    /**
     * Test isCommercialCustomer
     */
    public function testIsCommercialCustomer()
    {
        $this->ssoConfigurationMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);
        $this->retaillogininfo->isCommercialCustomer();
    }

    /**
     * Test getGeneralConfig
     */
    public function testGetGeneralConfig()
    {

        $returnValue = 'string';
        $this->ssoConfigurationMock->expects($this->any())
        ->method('getGeneralConfig')
        ->willReturn($returnValue);
        $this->assertsame($returnValue, $this->retaillogininfo->getGeneralConfig('code'));
    }

    /**
     * Test getDefaultShippingAddress
     */
    public function testGetDefaultShippingAddress()
    {
        $this->ssoConfigurationMock->expects($this->any())
        ->method('getDefaultShippingAddress')
        ->willReturn(true);
        $this->retaillogininfo->getDefaultShippingAddress();
    }

    /**
     * Test getFclCustomerInfo
     */
    public function testGetFclCustomerInfo()
    {
        $this->ssoConfigurationMock->expects($this->any())
        ->method('getFclCustomerInfo')
        ->willReturn(true);
        $this->retaillogininfo->getFclCustomerInfo();
    }

    /**
     * Test getWebCookieConfig
     */
    public function testgetWebCookieConfig()
    {
        $this->ssoConfigurationMock->expects($this->any())->method('getConfigValue')->willReturn(123);
        $this->assertsame(123, $this->retaillogininfo->getWebCookieConfig('abc', 1));
    }

    public function testGetCanvaDesignEnabled()
    {
        $this->ssoConfigurationMock->expects($this->any())->method('getConfigValue')->willReturn(1);
        $this->assertsame(1, $this->retaillogininfo->getCanvaDesignEnabled());
    }

    /**
     * Test isBatchUploadEnable
     */
    public function testIsMyProjectsEnable()
    {
        $this->myProjectManager->expects($this->any())
        ->method('isCartPageUnfinisedPopupEnable')->willReturn(1);
        $this->assertNotNull($this->retaillogininfo->isMyProjectsEnable());
    }

    /**
     * Test isMyProjectAvailable with toggle On
     */
    public function testIsMyProjectAvailable()
    {
        $this->myProjectManager->expects($this->any())
        ->method('isProjectAvailable')->willReturn(1);

        $this->assertTrue($this->retaillogininfo->isMyProjectAvailable());
    }

    /**
     * Test case for isPersonalAddressBookToggleEnable
     */
    public function testIsPersonalAddressBookToggleEnable()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertTrue($this->retaillogininfo->isPersonalAddressBookToggleEnable());
    }

    /**
     * Test that isAccessToWorkspaceToggleEnable returns true when toggle is enabled.
     */
    public function testIsAccessToWorkspaceToggleEnableReturnsTrue()
    {
        $this->myProjectManager->method('isAccessToWorkspaceToggleEnable')
            ->willReturn(true);

        $this->assertTrue($this->retaillogininfo->isAccessToWorkspaceToggleEnable());
    }

    /**
     * Test that isAccessToWorkspaceToggleEnable returns false when toggle is disabled.
     */
    public function testIsAccessToWorkspaceToggleEnableReturnsFalse()
    {
        $this->myProjectManager->method('isAccessToWorkspaceToggleEnable')
            ->willReturn(false);

        $this->assertFalse($this->retaillogininfo->isAccessToWorkspaceToggleEnable());
    }
}
