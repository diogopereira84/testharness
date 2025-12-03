<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SSO\Test\Unit\ViewModel;

use Fedex\SSO\ViewModel\SessionTimeout;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SSO\Block\LoginInfo;
use Fedex\SSO\Model\SessionTimeoutMessaging;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
/**
 * Test class for SsoConfiguration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SessionTimeoutTest extends TestCase
{
    protected $getLoggedAsCustomerAdminIdMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $sessionTimeOut;
    /**
     * @var ToggleConfig
     */
    protected $toggleConfigMock;
    /**
     * @var LoginInfo
     */
    private $loginInfoMock;
    /**
     * @var SessionTimeout
     */
    private $sessiontimeoutMock;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessiontimeoutMock = $this->getMockBuilder(SessionTimeoutMessaging::class)
            ->setMethods(['getSessionWarningTime',
                'getSessionWarningPMessage',
                'getSessionWarningSMessage',
                'getSessionExpiredPMessage',
                'getSessionExpiredSMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loginInfoMock = $this->getMockBuilder(LoginInfo::class)
            ->setMethods(['getWebCookieConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->getLoggedAsCustomerAdminIdMock = $this->getMockBuilder(GetLoggedAsCustomerAdminIdInterface::class)
            ->setMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->sessionTimeOut    = $this->objectManager->getObject(
            SessionTimeout::class,
            [
                'toggleConfig'               => $this->toggleConfigMock,
                'sessionTimeoutMessaging'    => $this->sessiontimeoutMock,
                'loginInfo'                  => $this->loginInfoMock,
                'getLoggedAsCustomerAdminId' => $this->getLoggedAsCustomerAdminIdMock
            ]
        );
    }
    /**
     * Test get session warning time
     *
     * @return void
     */
    public function testGetSessionWarningTime()
    {
        $this->sessiontimeoutMock->expects($this->any())
            ->method('getSessionWarningTime')->willReturn('');
        $expectedResult = $this->sessionTimeOut->getSessionWarningTime();
        $this->assertEquals('', $expectedResult);
    }
    /**
     * Test Get session warning primary message
     *
     * @return void
     */
    public function testGetSessionWarningPMessage()
    {
        $this->sessiontimeoutMock->expects($this->any())
            ->method('getSessionWarningPMessage')->willReturn('');
        $expectedResult = $this->sessionTimeOut->getSessionWarningPMessage();
        $this->assertEquals('', $expectedResult);
    }

    /**
     * Test Get session warning secondary message
     *
     * @return void
     */
    public function testGetSessionWarningSMessage()
    {
        $this->sessiontimeoutMock->expects($this->any())
            ->method('getSessionWarningSMessage')->willReturn('');
        $expectedResult = $this->sessionTimeOut->GetSessionWarningSMessage();
        $this->assertEquals('', $expectedResult);
    }

    /**
     * Test Get session expired primary message
     *
     * @return void
     */
    public function testGetSessionExpiredPMessage()
    {
        $this->sessiontimeoutMock->expects($this->any())
            ->method('getSessionExpiredPMessage')->willReturn('');
        $expectedResult = $this->sessionTimeOut->getSessionExpiredPMessage();
        $this->assertEquals('', $expectedResult);
    }

    /**
     * Test Get session expired secondary message
     *
     * @return void
     */
    public function testGetSessionExpiredSMessage()
    {
        $this->sessiontimeoutMock->expects($this->any())
            ->method('getSessionExpiredSMessage')->willReturn('');
        $expectedResult = $this->sessionTimeOut->getSessionExpiredSMessage();
        $this->assertEquals('', $expectedResult);
    }

    /**
     * Test Get webconfig for cookie lifetime
     *
     * @return void
     */
    public function testGetWebConfig()
    {
        $this->loginInfoMock->expects($this->any())
            ->method('getWebCookieConfig')
            ->with(SessionTimeout::COOKIE_LIFETIME)
            ->willReturn(true);
        $expectedResult = $this->sessionTimeOut->getWebConfig();
        $this->assertEquals(true, $expectedResult);
    }

    /**
     * Test get impersonator timeout toggle value
     *
     * @return void
     */
    public function testGetImpersonatorToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->sessionTimeOut->getImpersonatorToggle());
    }

    /**
     * Test get Impersonator Admin Id
     *
     */
    public function testGetAdminId()
    {
        $this->getLoggedAsCustomerAdminIdMock->expects($this->any())
            ->method('execute')
            ->willReturn(435);
        $this->assertEquals(435, $this->sessionTimeOut->getAdminId());
    }
}
