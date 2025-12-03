<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth;

use Fedex\OKTA\Model\Backend\TokenProvider;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Fedex\OKTA\Model\Oauth\OktaToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OktaTokenTest extends TestCase
{
    /**
     * @var OktaToken
     */
    private OktaToken $oktaToken;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $loggerMock;

    /**
     * @var TokenProvider|MockObject
     */
    private TokenProvider $tokenProviderMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->tokenProviderMock = $this->createMock(TokenProvider::class);
        $this->oktaToken = new OktaToken($this->tokenProviderMock, $this->loggerMock);
    }

    public function testGetToken()
    {
        $token = 'sometoken';
        $this->tokenProviderMock->expects($this->once())->method('getOktaToken')->willReturn($token);
        $this->assertEquals($token, $this->oktaToken->getToken('some_code'));
    }

    public function testValidate()
    {
        $this->assertTrue($this->oktaToken->validate(['access_token'  => 'some_token']));
    }

    public function testValidateEmpty()
    {
        $this->expectException(LocalizedException::class);
        $this->oktaToken->validate([]);
    }

    public function testValidateWithError()
    {
        $this->expectException(LocalizedException::class);
        $this->oktaToken->validate(['error'  => true, 'error_description' => 'some message']);
    }

    public function testValidateBodyChange()
    {
        $this->expectException(LocalizedException::class);
        $this->oktaToken->validate(['token' => 'some_token']);
    }
}
