<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Backend;

use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\OKTA\Model\Backend\LoginHandler;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Backend\UserInfoProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserInfoProviderTest extends TestCase
{
    /**
     * @var UserInfoProvider
     */
    private UserInfoProvider $userInfoProvider;

    /**
     * @var OktaHelper|MockObject
     */
    private OktaHelper $oktaHelperMock;

    /**
     * @var AbstractApiClient|MockObject
     */
    private AbstractApiClient $abstractApiClientMock;

    protected function setUp(): void
    {
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->abstractApiClientMock = $this->createMock(AbstractApiClient::class);
        $this->userInfoProvider = new UserInfoProvider($this->oktaHelperMock, $this->abstractApiClientMock);
    }

    public function testGetUserInfo(): void
    {
        $this->abstractApiClientMock->expects($this->once())->method('execute');
        $this->userInfoProvider->getUserInfo('access_token');
    }
}
