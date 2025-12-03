<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Model;

use Fedex\OktaMFTF\Model\Config\General as GeneralConfig;
use Fedex\OktaMFTF\Model\Login;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Backend\Model\Auth\Session as UserSession;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    public function testAuthenticate(): void
    {
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adminSessionManagerMock = $this->getMockBuilder(AdminSessionsManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventManagerMock = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userResourceMock = $this->getMockBuilder(UserResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userSessionMock = $this->getMockBuilder(UserSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock = $this->getMockBuilder(AdminSessionInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $login = new Login(
            $adminSessionManagerMock,
            $eventManagerMock,
            $userResourceMock,
            $userSessionMock,
            $configMock
        );
        $userMock->expects($this->once())->method('getId')->willReturn(123);
        $adminSessionManagerMock->expects($this->atLeast(2))
            ->method('getCurrentSession')
            ->willReturn($sessionMock);
        $adminSessionManagerMock->expects($this->once())
            ->method('processLogin');

        $login->authenticate($userMock);
    }
}