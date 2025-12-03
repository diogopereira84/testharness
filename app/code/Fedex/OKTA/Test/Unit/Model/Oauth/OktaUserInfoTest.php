<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth;

use Fedex\OKTA\Model\Backend\UserInfoProvider;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\Oauth\OktaUserInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OktaUserInfoTest extends TestCase
{
    /**
     * @var OktaUserInfo
     */
    private OktaUserInfo $oktaUserInfo;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var UserInfoProvider|MockObject
     */
    private UserInfoProvider $userInfoProviderMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->userInfoProviderMock = $this->createMock(UserInfoProvider::class);
        $this->oktaUserInfo = new OktaUserInfo($this->userInfoProviderMock, $this->loggerMock);
    }

    public function testGetUserInfo()
    {
        $userData = ['userdata'];
        $this->userInfoProviderMock->expects($this->once())->method('getUserInfo')->willReturn($userData);
        $this->assertEquals($userData, $this->oktaUserInfo->getUserInfo('token'));
    }

    public function testValidate()
    {
        $this->assertTrue($this->oktaUserInfo->validate(['userdata'  => 'some_data']));
    }

    public function testValidateEmpty()
    {
        $this->expectException(LocalizedException::class);
        $this->oktaUserInfo->validate([]);
    }

    public function testValidateWithError()
    {
        $this->expectException(LocalizedException::class);
        $this->oktaUserInfo->validate(['error'  => true, 'error_description' => 'some message']);
    }
}
