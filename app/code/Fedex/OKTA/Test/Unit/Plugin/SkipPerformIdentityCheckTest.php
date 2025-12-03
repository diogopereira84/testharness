<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Plugin;

use Magento\User\Model\User;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Plugin\SkipPerformIdentityCheck;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use tests\verification\Tests\HookActionsTest;
use function PHPUnit\Framework\once;

class SkipPerformIdentityCheckTest extends TestCase
{
    /**
     * @var SkipPerformIdentityCheck
     */
    private SkipPerformIdentityCheck $skipPerformIdentityCheck;

    /**
     * @var OktaHelper|MockObject
     */
    private OktaHelper $oktaHelperMock;

    /**
     * @var User|MockObject
     */
    private User $userMock;

    public function testAroundPerformIdentityCheck()
    {
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getReloadAclFlag', 'setReloadAclFlag', 'save'])->getMock();
        $this->skipPerformIdentityCheck = new SkipPerformIdentityCheck($this->oktaHelperMock);

        $this->oktaHelperMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->skipPerformIdentityCheck->aroundPerformIdentityCheck($this->userMock, function () {
        }, 'password');
    }

    public function testAroundPerformIdentityCheckDisabled()
    {
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getReloadAclFlag', 'setReloadAclFlag', 'save'])->getMock();
        $this->skipPerformIdentityCheck = new SkipPerformIdentityCheck($this->oktaHelperMock);

        $this->oktaHelperMock->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->skipPerformIdentityCheck->aroundPerformIdentityCheck($this->userMock, function () {
        }, 'password');
    }
}
