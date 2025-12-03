<?php

/**
 * @category Fedex
 * @package  Fedex_SSO
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Rutvee Sojitra <rsojitra@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Model;

use Fedex\SSO\Model\SessionTimeoutMessaging;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionTimeoutMessagingTest extends TestCase
{
    private const SESSION_GENERAL_CONFIG ='sso/session_general/';
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createPartialMock(
            ScopeConfigInterface::class,
            ['getValue', 'isSetFlag']
        );
    }

    /**
     * Function test Is Get Session warning time
     */
    public function testGetSessionWarningTime()
    {
        $sessionWarningTime = '300';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(
                self::SESSION_GENERAL_CONFIG.SessionTimeoutMessaging::SESSION_WARNING_TIME,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($sessionWarningTime);
        $config = new SessionTimeoutMessaging($this->scopeConfigMock);
        $this->assertEquals($sessionWarningTime, $config->getSessionWarningTime());
    }

    /**
     * Function test Is Get Session warning primary Message
     */
    public function testGetSessionWarningPMessage()
    {
        $sessionWarningPrimaryMessage = 'sessionWarningPrimaryMessage';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(
                self::SESSION_GENERAL_CONFIG.SessionTimeoutMessaging::SESSION_WARNING_PRIMARY_MESSAGE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($sessionWarningPrimaryMessage);
        $config = new SessionTimeoutMessaging($this->scopeConfigMock);
        $this->assertEquals($sessionWarningPrimaryMessage, $config->getSessionWarningPMessage());
    }

    /**
     * Function test Is Get Session warning secondary Message
     */
    public function testGetSessionWarningSMessage()
    {
        $sessionWarningSecondaryMessage = 'sessionWarningSecondaryMessage';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(
                self::SESSION_GENERAL_CONFIG.SessionTimeoutMessaging::SESSION_WARNING_SECONDARY_MESSAGE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($sessionWarningSecondaryMessage);
        $config = new SessionTimeoutMessaging($this->scopeConfigMock);
        $this->assertEquals($sessionWarningSecondaryMessage, $config->getSessionWarningSMessage());
    }

    /**
     * Function test Is Get Session expired primary Message
     */
    public function testGetSessionExpiredPMessage()
    {
        $sessionExpiredPrimaryMessage = 'sessionWarningSecondaryMessage';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(
                self::SESSION_GENERAL_CONFIG.SessionTimeoutMessaging::SESSION_EXPIRED_PRIMARY_MESSAGE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($sessionExpiredPrimaryMessage);
        $config = new SessionTimeoutMessaging($this->scopeConfigMock);
        $this->assertEquals($sessionExpiredPrimaryMessage, $config->getSessionExpiredPMessage());
    }

    /**
     * Function test Is Get Session expired secondary Message
     */
    public function testGetSessionExpiredSMessage()
    {
        $sessionExpiredSecondaryMessage = 'sessionWarningSecondaryMessage';
        $this->scopeConfigMock->expects($this->once())->method('getValue')
            ->with(
                self::SESSION_GENERAL_CONFIG.SessionTimeoutMessaging::SESSION_EXPIRED_SECONDARY_MESSAGE,
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($sessionExpiredSecondaryMessage);
        $config = new SessionTimeoutMessaging($this->scopeConfigMock);
        $this->assertEquals($sessionExpiredSecondaryMessage, $config->getSessionExpiredSMessage());
    }
}
