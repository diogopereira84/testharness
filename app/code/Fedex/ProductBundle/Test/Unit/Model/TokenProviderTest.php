<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\Delivery\Helper\Data;
use Fedex\ProductBundle\Model\TokenProvider;
use Fedex\Punchout\ViewModel\TazToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenProviderTest extends TestCase
{
    private TazToken|MockObject $tazToken;
    private Data|MockObject $deliveryHelper;
    private TokenProvider $provider;

    protected function setUp(): void
    {
        $this->tazToken = $this->createMock(TazToken::class);
        $this->deliveryHelper = $this->createMock(Data::class);

        $this->provider = new TokenProvider(
            $this->tazToken,
            $this->deliveryHelper
        );
    }

    public function testGetTazTokenReturnsExpectedValue(): void
    {
        $this->tazToken
            ->expects($this->once())
            ->method('getTazToken')
            ->with(true)
            ->willReturn('token123');

        $result = $this->provider->getTazToken(true);
        $this->assertSame('token123', $result);
    }

    public function testGetTazTokenDefaultsToFalse(): void
    {
        $this->tazToken
            ->expects($this->once())
            ->method('getTazToken')
            ->with(false)
            ->willReturn('defaultToken');

        $result = $this->provider->getTazToken();
        $this->assertSame('defaultToken', $result);
    }

    public function testGetCompanySiteReturnsExpectedValue(): void
    {
        $this->deliveryHelper
            ->expects($this->once())
            ->method('getCompanySite')
            ->willReturn('companySite123');

        $result = $this->provider->getCompanySite();
        $this->assertSame('companySite123', $result);
    }
}
