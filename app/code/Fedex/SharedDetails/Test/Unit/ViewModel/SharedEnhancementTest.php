<?php

namespace Fedex\SharedDetails\Test\Unit\ViewModel;

use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SharedDetails\ViewModel\SharedEnhancement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SharedEnhancementTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $urlInterfaceMock;
    protected $scopeConfigInterfaceMock;
    /**
     * @var (\Magento\Store\Model\ScopeInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeInterfaceMock;
    protected $toggleConfigMock;
    protected $sharedEnhancementMock;
    /**
     * @var SharedEnhancement $sharedEnhancement
     */
    protected $sharedEnhancement;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->setMethods(['getCurrentUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeInterfaceMock = $this->getMockBuilder(ScopeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->sharedEnhancementMock = $this->objectManager->getObject(
            SharedEnhancement::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'urlInterface' => $this->urlInterfaceMock,
                'scopeInterface' => $this->scopeInterfaceMock,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock
            ]
        );
    }

    /**
     * Check is shared Order page
     *
     * @return bool true|false
     */
    public function testIsSharedOrderPage()
    {
        $this->urlInterfaceMock->expects($this->any())->method('getCurrentUrl')->willReturn('/shared/order/history');

        $this->assertTrue($this->sharedEnhancementMock->isSharedOrderPage());
    }

    /**
     * Check is shared Order page
     *
     * @return bool true|false
     */
    public function testIsSharedOrderPageWithFalse()
    {
        $this->urlInterfaceMock->expects($this->any())->method('getCurrentUrl')->willReturn('/my/order/history');

        $this->assertFalse($this->sharedEnhancementMock->isSharedOrderPage());
    }

    /**
     * Check is customer reporting enhancement toggle enable
     *
     * @return bool true|false
     */
    public function testIsCustomerReportingEnhancementToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->sharedEnhancementMock->isCustomerReportingEnhancementToggleEnabled());
    }

    /**
     * Check is customer reporting enhancement toggle disable
     *
     * @return bool true|false
     */
    public function testIsCustomerReportingEnhancementToggleDisable()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $this->assertFalse($this->sharedEnhancementMock->isCustomerReportingEnhancementToggleEnabled());
    }

    /**
     * Test Get timeframe configuration
     *
     * @return array
     */
    public function testGetTimeframeOptionsWithMultiSelect()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn('1,3');
        $this->assertNotNull($this->sharedEnhancementMock->getTimeframeOptions());
    }

    /**
     * Test Get timeframe configuration
     *
     * @return array
     */
    public function testGetTimeframeOptions()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn('1');
        $this->assertNotNull($this->sharedEnhancementMock->getTimeframeOptions());
    }

    /**
     * Test get timeframe select options configuration
     *
     * @return array
     */
    public function testGetTimeframeSelectOptions()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn('1');
        $this->assertNotNull($this->sharedEnhancementMock->getTimeframeSelectOptions());
    }

    /**
     * Test Get User Emails Allow Limit Configuration
     */
    public function testGetUserEmailsAllowLimit()
    {
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')->willReturn('20');
        $this->assertNotNull($this->sharedEnhancementMock->getUserEmailsAllowLimit());
    }

    /**
    * testIsCompanySettingsToggleEnabled
    *
    * @return boolean
    */
    public function testIsCompanySettingsToggleEnabled()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->sharedEnhancementMock->isCompanySettingsToggleEnabled());
    }
}
