<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Oauth\UrlBuilder;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\OKTA\Model\Oauth\UrlBuilder\CodeStorage;

class CodeStorageTest extends TestCase
{
    /**
     * Base64 encoded COOKIE value
     */
    private const COOKIE_VALUE = 'IHNvbWUgcmFuZG9tIHN0cmluZyArKysrKy8vLyA';

    /**
     * Cookie lifetime value
     */
    private const COOKIE_LIFETIME_VALUE = 30;

    /**
     * Cookie path value
     */
    private const COOKIE_PATH_VALUE = '/';

    /**
     * @var MockObject|CookieManagerInterface
     */
    private MockObject|CookieManagerInterface $cookieManagerMock;

    /**
     * @var MockObject|EncryptorInterface
     */
    private MockObject|EncryptorInterface $encryptorMock;

    /**
     * @var MockObject|CookieMetadataFactory
     */
    private MockObject|CookieMetadataFactory $cookieMetadataFactoryMock;

    /**
     * @var CodeStorage
     */
    private CodeStorage $codeStorage;

    /**
     * @var MockObject|SessionManagerInterface
     */
    private MockObject|SessionManagerInterface $sessionManagerMock;

    /**
     * @var PublicCookieMetadata
     */
    private PublicCookieMetadata $publicCookieMetadata;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->cookieManagerMock = $this->getMockForAbstractClass(CookieManagerInterface::class);
        $this->cookieMetadataFactoryMock = $this->createMock(CookieMetadataFactory::class);
        $this->sessionManagerMock = $this->getMockForAbstractClass(SessionManagerInterface::class);
        $this->encryptorMock = $this->getMockForAbstractClass(EncryptorInterface::class);
        $this->publicCookieMetadata = new PublicCookieMetadata();
        $this->codeStorage = new CodeStorage(
            $this->cookieManagerMock,
            $this->cookieMetadataFactoryMock,
            $this->sessionManagerMock,
            $this->encryptorMock
        );
    }

    /**
     * Test retrieve() method
     *
     * @return void
     */
    public function testRetrieve(): void
    {
        $this->cookieManagerMock->expects($this->once())->method('getCookie')
            ->willReturn(self::COOKIE_VALUE);
        $this->encryptorMock->expects($this->once())->method('decrypt')
            ->willReturn(self::COOKIE_VALUE);
        $this->assertEquals(
            self::COOKIE_VALUE,
            $this->codeStorage->retrieve()
        );
    }

    /**
     * Test store() method
     *
     * @return void
     */
    public function testStore(): void
    {
        $this->sessionManagerMock->expects($this->once())->method('getCookieLifetime')
            ->willReturn(self::COOKIE_LIFETIME_VALUE);
        $this->sessionManagerMock->expects($this->once())->method('getCookiePath')
            ->willReturn(self::COOKIE_PATH_VALUE);
        $this->sessionManagerMock->expects($this->once())->method('getCookieDomain')
            ->willReturn('fedex.com');
        $this->cookieMetadataFactoryMock->expects($this->once())->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadata);
        $this->encryptorMock->expects($this->once())->method('encrypt')->willReturn(self::COOKIE_VALUE);
        $this->cookieManagerMock->expects($this->once())->method('setPublicCookie');

        $this->codeStorage->store(self::COOKIE_VALUE);
    }
}
