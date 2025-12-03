<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Plugin;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Acl;
use Magento\User\Model\User;
use Magento\Framework\Acl\Builder;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Plugin\SkipAdminPasswordReset;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SkipAdminPasswordResetTest extends TestCase
{
    /**
     * @var SkipAdminPasswordReset
     */
    private SkipAdminPasswordReset $skipAdminPasswordReset;

    /**
     * @var OktaHelper|MockObject
     */
    private OktaHelper $oktaHelperMock;

    /**
     * @var Builder|MockObject
     */
    private Builder $builderMock;

    /**
     * @var User|MockObject
     */
    private User $userMock;

    /**
     * @var Session|MockObject
     */
    private Session $sessionMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUser', 'getAcl', 'setAcl'])->getMock();
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getReloadAclFlag', 'setReloadAclFlag', 'save'])->getMock();
        $this->builderMock = $this->createMock(Builder::class);
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->skipAdminPasswordReset = new SkipAdminPasswordReset($this->oktaHelperMock, $this->builderMock);
    }

    public function testAroundRefreshAcl()
    {
        $this->oktaHelperMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->sessionMock->expects($this->once())->method('getUser')->willReturn($this->userMock);
        $this->userMock->method('getReloadAclFlag')->willReturn(1);
        $this->userMock->method('setReloadAclFlag')->willReturn($this->userMock);
        $this->userMock->method('save')->willReturn(true);
        $this->builderMock->expects($this->once())->method('getAcl')->willReturn(new Acl());

        $this->skipAdminPasswordReset->aroundRefreshAcl($this->sessionMock, function () {
        });
    }

    public function testAroundRefreshAclDisabled()
    {
        $this->oktaHelperMock->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->skipAdminPasswordReset->aroundRefreshAcl($this->sessionMock, function () {
        });
    }

    public function testAroundRefreshAclInvalidUser()
    {
        $this->oktaHelperMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->sessionMock->expects($this->once())->method('getUser')->willReturn(false);

        $this->skipAdminPasswordReset->aroundRefreshAcl($this->sessionMock, function () {
        });
    }
}
