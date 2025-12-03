<?php
/**
 * @category  Fedex
 * @package   Fedex_SDE
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SDE\Test\Unit\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SDE\Model\ForgeRock;
use Psr\Log\LoggerInterface;

class ForgeRockTest extends TestCase
{
    protected $publicCookieMetadataMock;
    /**
     * @var ForgeRock
     */
    private ForgeRock $forgeRock;

    /**
     * @var MockObject|LoggerInterface
     */
    private MockObject|LoggerInterface $loggerMock;

    /**
     * @var MockObject|RequestInterface
     */
    private MockObject|RequestInterface $requestMock;

    /**
     * @var MockObject|CookieManagerInterface
     */
    private MockObject|CookieManagerInterface $cookieManagerMock;

    /**
     * @var MockObject|CookieMetadataFactory
     */
    private MockObject|CookieMetadataFactory $cookieMetadataFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->cookieManagerMock = $this->getMockForAbstractClass(CookieManagerInterface::class);
        $this->cookieMetadataFactoryMock = $this->getMockBuilder(CookieMetadataFactory::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->publicCookieMetadataMock = $this->createMock(PublicCookieMetadata::class);
        $this->forgeRock = new ForgeRock(
            $this->loggerMock,
            $this->requestMock,
            $this->cookieManagerMock,
            $this->cookieMetadataFactoryMock
        );
    }

    /**
     * @test
     */
    public function getCookie(): void
    {
        $this->publicCookieMetadataMock
            ->method('setDuration')
            ->with(1800)
            ->willReturnSelf();
        $this->publicCookieMetadataMock
            ->method('setPath')
            ->with('/')
            ->willReturnSelf();
        $this->publicCookieMetadataMock
            ->method('setHttpOnly')
            ->with(false)
            ->willReturnSelf();
        $this->cookieMetadataFactoryMock
            ->method('createPublicCookieMetadata')
            ->willReturn($this->publicCookieMetadataMock);
        $this->requestMock
            ->method('getParam')
            ->with('id_token')
            ->willReturn('token');
        $this->cookieManagerMock
            ->method('setPublicCookie')
            ->with(
                'id_token',
                'token',
                $this->publicCookieMetadataMock
            );

        $this->assertEquals('token', $this->forgeRock->getCookie());

        $this->cookieMetadataFactoryMock
            ->method('createPublicCookieMetadata')
            ->willThrowException(new \Exception('Error'));
        $this->loggerMock->expects($this->once())->method('error');

        $this->assertNull($this->forgeRock->getCookie());
    }
}
