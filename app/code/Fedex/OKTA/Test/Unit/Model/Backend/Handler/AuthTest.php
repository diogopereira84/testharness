<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Backend\Handler;

use Fedex\OKTA\Model\Backend\Handler\Auth;
use Magento\Backend\Model\Auth\Session as UserSession;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Security\Model\AdminSessionInfo;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    /**
     * @var (\Magento\User\Model\ResourceModel\User & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $userResourceMock;
    /**
     * @var Auth
     */
    private Auth $auth;

    /**
     * @var AdminSessionInfo|MockObject
     */
    private AdminSessionInfo $adminSessionInfoMock;

    /**
     * @var AdminSessionsManager|MockObject
     */
    private AdminSessionsManager $adminSessionsManagerMock;

    /**
     * @var EventManager|MockObject
     */
    private EventManager $eventManagerMock;

    /**
     * @var User|MockObject
     */
    private User $userMock;

    /**
     * @var UserSession|MockObject
     */
    private UserSession $userSessionMock;

    protected function setUp(): void
    {
        $this->adminSessionInfoMock = $this->createMock(AdminSessionInfo::class);
        $this->adminSessionsManagerMock = $this->createMock(AdminSessionsManager::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);
        $this->userResourceMock = $this->createMock(UserResource::class);
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()->setMethods(['getIsActive', 'hasAssigned2Role', 'getId'])->getMock();
        $this->userSessionMock = $this->createMock(UserSession::class);
        $this->auth = new Auth(
            $this->adminSessionsManagerMock,
            $this->eventManagerMock,
            $this->userResourceMock,
            $this->userSessionMock,
        );
    }

    public function testLogin(): void
    {
        $this->adminSessionsManagerMock->expects($this->any())->method('getCurrentSession')
            ->willReturn($this->adminSessionInfoMock);
        $this->adminSessionInfoMock->expects($this->any())->method('setData')->withConsecutive(
            ['updated_at', time()],
            ['status', AdminSessionInfo::LOGGED_IN]
        )->willReturnSelf();
        $this->adminSessionsManagerMock->expects($this->once())->method('processLogin');
        $this->auth->login($this->userMock);
    }
}
