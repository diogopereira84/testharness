<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Backend;

use Fedex\OKTA\Model\Oauth\UrlBuilder\CodeStorage;
use Magento\Framework\Encryption\EncryptorInterface;
use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\OKTA\Model\Backend\LoginHandler;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Backend\TokenProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenProviderTest extends TestCase
{
    /**
     * @var TokenProvider
     */
    private TokenProvider $tokenProvider;

    /**
     * @var LoginHandler|MockObject
     */
    private LoginHandler $loginHandlerMock;

    /**
     * @var OktaHelper|MockObject
     */
    private OktaHelper $oktaHelperMock;

    /**
     * @var AbstractApiClient|MockObject
     */
    private AbstractApiClient|MockObject $abstractApiClientMock;

    /**
     * @var MockObject|CodeStorage
     */
    private MockObject|CodeStorage $codeStorageMock;

    /**
     * @var MockObject|EncryptorInterface
     */
    private MockObject|EncryptorInterface $encryptorMock;


    protected function setUp(): void
    {
        $this->oktaHelperMock = $this->createMock(OktaHelper::class);
        $this->loginHandlerMock = $this->createMock(LoginHandler::class);
        $this->abstractApiClientMock = $this->createMock(AbstractApiClient::class);
        $this->encryptorMock = $this->getMockForAbstractClass(EncryptorInterface::class);
        $this->codeStorageMock = $this->createMock(CodeStorage::class);
        $this->tokenProvider = new TokenProvider(
            $this->oktaHelperMock,
            $this->abstractApiClientMock,
            $this->encryptorMock,
            $this->codeStorageMock
        );
    }

    public function testGetOktaToken(): void
    {
        $this->abstractApiClientMock->expects($this->once())->method('execute');
        $this->oktaHelperMock->expects($this->once())->method('getClientId')->willReturn('123');
        $this->oktaHelperMock->expects($this->once())->method('getRedirectUrl')
            ->willReturn('http://domain.com/redirect');

        $this->tokenProvider->getOktaToken('some_code');
    }

    public function testGetOktaTokenWithPKCE(): void
    {
        $this->abstractApiClientMock->expects($this->once())->method('execute');
        $this->oktaHelperMock->expects($this->once())->method('getClientId')->willReturn('123');
        $this->oktaHelperMock->expects($this->once())->method('getRedirectUrl')
            ->willReturn('http://domain.com/redirect');

        $this->tokenProvider->getOktaToken('some_code');
    }
}
