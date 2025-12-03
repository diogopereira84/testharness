<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SSO\Block\LoginInfo;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginInfoTest extends TestCase
{
    protected $ssoConfiguration;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var LoginInfoData
     */
    protected $LoginInfoData;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfigMock;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {

        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(
                [
                    'getFclCustomerName',
                    'getGeneralConfig',
                    'isFclCustomer',
                    'getDefaultShippingAddress',
                    'getFclCustomerInfo',
                    'getConfigValue',
                    'isCommercialCustomer'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->LoginInfoData = $this->objectManager->getObject(
            LoginInfo::class,
            [
                'ssoConfiguration' => $this->ssoConfiguration,
                'toggleConfig' => $this->toggleConfigMock

            ]
        );
    }

    /**
     * Test getFclCustomerName
     */
    public function testGetFclCustomerName()
    {

        $name = 'test';
        $this->ssoConfiguration->expects($this->any())->method('getFclCustomerName')->willReturn($name);
        $this->assertsame($name, $this->LoginInfoData->getFclCustomerName());
    }

    /**
     * Test getGeneralConfig
     */
    public function testGetGeneralConfig()
    {

        $returnValue = 'string';
        $this->ssoConfiguration->expects($this->any())->method('getGeneralConfig')->willReturn($returnValue);
        $this->assertsame($returnValue, $this->LoginInfoData->getGeneralConfig('code'));
    }

    /**
     * Test isFclCustomer
     */
    public function testIsFclCustomer()
    {
        $this->ssoConfiguration->expects($this->any())->method('isFclCustomer')->willReturn(1);
        $this->assertsame(1, $this->LoginInfoData->isFclCustomer());
    }

    /**
     * Test getWebCookieConfig
     */
    public function testgetWebCookieConfig()
    {
        $this->ssoConfiguration->expects($this->any())->method('getConfigValue')->willReturn(123);
        $this->assertsame(123, $this->LoginInfoData->getWebCookieConfig('abc', 1));
    }

    /**
     * Test getDefaultShippingAddress
     */
    public function testGetDefaultShippingAddress()
    {

        $returnValue = 'ShippingAddress';
        $this->ssoConfiguration->expects($this->any())->method('getDefaultShippingAddress')->willReturn($returnValue);
        $this->assertsame($returnValue, $this->LoginInfoData->getDefaultShippingAddress());
    }

    /**
     * Test getFclCustomerInfo
     */
    public function testGetFclCustomerInfo()
    {

        $returnValue = 'CustomerInfo';
        $this->ssoConfiguration->expects($this->any())->method('getFclCustomerInfo')->willReturn($returnValue);
        $this->assertsame($returnValue, $this->LoginInfoData->getFclCustomerInfo());
    }

    /**
     * Test getWebCookieConfig
     */
    public function testGetCanvaDesignEnabled()
    {
        $this->ssoConfiguration->expects($this->any())->method('getConfigValue')->willReturn(1);
        $this->assertsame(1, $this->LoginInfoData->getCanvaDesignEnabled());
    }

    public function testIsCommercialCustomer()
    {
        $this->ssoConfiguration->expects($this->any())->method('isCommercialCustomer')->willReturn(1);
        $this->assertsame(1, $this->LoginInfoData->isCommercialCustomer());
    }
}
