<?php

/**
 * Copyright © By infogain All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SDE\Test\Unit\Plugin\Customer;

use Fedex\SDE\Helper\SdeHelper;
use Fedex\SDE\Plugin\Customer\CustomerData;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Store\Model\ScopeInterface;

/**
 * Class CustomerDataTest
 *
 * This class is to do the phpunit for CustomerDataTest plugin class
 */
class CustomerDataTest extends TestCase
{
    protected $sdeHelperMock;
    protected $scopeConfigMock;
    protected $customerData;
    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->sdeHelperMock = $this->createMock(SdeHelper::class);
        $objectManagerHelper = new ObjectManager($this);
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(
                [
                    'getValue',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerData = $objectManagerHelper->getObject(
            CustomerData::class,
            [
                'sdeHelper' => $this->sdeHelperMock,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * test testAfterGetCookieLifeTime
     */
    public function testAfterGetCookieLifeTime()
    {
        $result = 300;
        $idleTimeout = 420;
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(SsoConfiguration::XML_PATH_FEDEX_SSO_SESSION_IDLE_TIMEOUT, ScopeInterface::SCOPE_STORE)
            ->willReturn($idleTimeout);

        $this->assertEquals($idleTimeout, $this->customerData->afterGetCookieLifeTime($this->customerData, $result));
    }

    /**
     * test testAfterGetCookieLifeTimeInNonSDEStore
     */
    public function testAfterGetCookieLifeTimeInNonSDEStore()
    {
        $result = 300;
        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(false);

        $this->assertEquals($result, $this->customerData->afterGetCookieLifeTime($this->customerData, $result));
    }
}
