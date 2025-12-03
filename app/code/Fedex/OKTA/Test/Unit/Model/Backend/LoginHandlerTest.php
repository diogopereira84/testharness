<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Backend;

use Fedex\OKTA\Model\Backend\Handler\Auth;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\EntityDataValidator;
use Fedex\OKTA\Model\Oauth\OktaUserInfoInterface;
use Psr\Log\LoggerInterface as LoggerInterface;
use Fedex\OKTA\Model\Backend\EntityProvider;
use Fedex\OKTA\Model\Backend\LoginHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginHandlerTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Event\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventManagerMock;
    /**
     * @var LoginHandler
     */
    private LoginHandler $loginHandler;

    /**
     * @var CookieManagerInterface|MockObject
     */
    private CookieManagerInterface $cookieManagerMock;

    /**
     * @var EntityProvider|MockObject
     */
    private EntityProvider $entityProviderMock;

    /**
     * @var EntityDataValidator|MockObject
     */
    private EntityDataValidator $entityDataValidator;

    /**
     * @var OktaUserInfoInterface|MockObject
     */
    private OktaUserInfoInterface $oktaUserInfoMock;

    /**
     * @var OktaHelper|MockObject
     */
    private OktaHelper $oktaHelperMock;

    /**
     * @var UserResource|MockObject
     */
    private UserResource $userResourceMock;

    /**
     * @var User|MockObject
     */
    private User $userMock;

    /**
     * @var Auth|MockObject
     */
    private Auth $authMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->cookieManagerMock = $this->createMock(CookieManagerInterface::class);
        $this->entityProviderMock = $this->createMock(EntityProvider::class);
        $this->entityDataValidator = $this->createMock(EntityDataValidator::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);
        $this->oktaUserInfoMock = $this->createMock(OktaUserInfoInterface::class);
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()->setMethods(['getIsActive', 'hasAssigned2Role', 'getId'])->getMock();
        $this->authMock = $this->createMock(Auth::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->loginHandler = new LoginHandler(
            $this->cookieManagerMock,
            $this->entityProviderMock,
            $this->entityDataValidator,
            $this->oktaUserInfoMock,
            $this->oktaHelperMock,
            $this->authMock,
            $this->loggerMock
        );
    }

    public function testLoginByToken(): void
    {
        $this->entityDataValidator->expects($this->once())->method('validate');
        $this->oktaUserInfoMock->expects($this->once())->method('getUserInfo')
            ->willReturn(
                '{"sub":"some_data","email":"email@mail.com"'
                . ',"given_name":"test","family_name":"test","groups":["test"]}'
            );
        $this->entityProviderMock->expects($this->once())->method('getOrCreateEntity')
            ->willReturn($this->userMock);
        $this->userMock->expects($this->once())->method('getIsActive')->willReturn(true);
        $this->userMock->expects($this->once())->method('hasAssigned2Role')->willReturn(true);
        $this->loginHandler->loginByToken('some_token');
    }

    public function testLoginByTokenUserInactive(): void
    {
        $this->entityDataValidator->expects($this->once())->method('validate');
        $this->oktaUserInfoMock->expects($this->once())->method('getUserInfo')
            ->willReturn(
                '{"sub":"some_data","email":"email@mail.com"'
                . ',"given_name":"test","family_name":"test","groups":["test"]}'
            );
        $this->entityProviderMock->expects($this->once())->method('getOrCreateEntity')
            ->willReturn($this->userMock);
        $this->userMock->expects($this->once())->method('getIsActive')->willReturn(false);
        $this->expectException(AuthenticationException::class);
        $this->loginHandler->loginByToken('some_token');
    }

    public function testLoginByTokenUserUnAssigned(): void
    {
        $this->entityDataValidator->expects($this->once())->method('validate');
        $this->oktaUserInfoMock->expects($this->once())->method('getUserInfo')
            ->willReturn(
                '{"sub":"some_data","email":"email@mail.com"'
                . ',"given_name":"test","family_name":"test","groups":["test"]}'
            );
        $this->entityProviderMock->expects($this->once())->method('getOrCreateEntity')
            ->willReturn($this->userMock);
        $this->userMock->expects($this->once())->method('getIsActive')->willReturn(true);
        $this->userMock->expects($this->once())->method('hasAssigned2Role')->willReturn(false);
        $this->expectException(AuthenticationException::class);
        $this->loginHandler->loginByToken('some_token');
    }
}
