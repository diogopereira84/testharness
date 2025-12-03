<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Service\CustomerCanvasTokenService;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasUserManager;
use Fedex\CustomerCanvas\Model\Service\StoreFrontUserIdService;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerCanvasUserManagerTest extends TestCase
{
    /** @var CustomerCanvasTokenService|MockObject */
    private $tokenServiceMock;

    /** @var StoreFrontUserIdService|MockObject */
    private $storeFrontUserIdServiceMock;

    /** @var CustomerCanvasUserManager */
    private $userManager;

    protected function setUp(): void
    {
        $this->tokenServiceMock = $this->createMock(CustomerCanvasTokenService::class);
        $this->storeFrontUserIdServiceMock = $this->createMock(StoreFrontUserIdService::class);

        $this->userManager = new CustomerCanvasUserManager(
            $this->tokenServiceMock,
            $this->storeFrontUserIdServiceMock
        );
    }

    public function testGetOrCreateToken_Success(): void
    {
        $this->storeFrontUserIdServiceMock->method('getStoreFrontUserId')->willReturn('uuid-123');
        $this->tokenServiceMock->method('fetchToken')->with('uuid-123')->willReturn('access-token-xyz');

        $token = $this->userManager->getOrCreateToken();
        $this->assertEquals('access-token-xyz', $token);
    }

    public function testGetOrCreateToken_ReturnsNull(): void
    {
        $this->storeFrontUserIdServiceMock->method('getStoreFrontUserId')->willReturn('uuid-123');
        $this->tokenServiceMock->method('fetchToken')->with('uuid-123')->willReturn(null);

        $token = $this->userManager->getOrCreateToken();
        $this->assertNull($token);
    }

    public function testGetOrCreateToken_ThrowsLocalizedException(): void
    {
        $this->storeFrontUserIdServiceMock
            ->method('getStoreFrontUserId')
            ->willThrowException(new LocalizedException(__('Failed')));

        $this->expectException(LocalizedException::class);
        $this->userManager->getOrCreateToken();
    }
}
