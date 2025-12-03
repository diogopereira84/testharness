<?php
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model\Middleware;

use Fedex\MarketplaceWebhook\Model\Middleware\MiraklWebhookDuplicateBlocker;
use Magento\Framework\App\CacheInterface;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use PHPUnit\Framework\TestCase;

class MiraklWebhookDuplicateBlockerTest extends TestCase
{
    private CacheInterface $cacheMock;
    private HandleMktCheckout $handleMktCheckoutMock;
    private MiraklWebhookDuplicateBlocker $blocker;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->handleMktCheckoutMock = $this->createMock(HandleMktCheckout::class);

        $this->blocker = new MiraklWebhookDuplicateBlocker(
            $this->cacheMock,
            $this->handleMktCheckoutMock
        );
    }

    public function testReturnsTrueWhenPayloadIsDuplicate(): void
    {
        $payload = '{"foo":"bar"}';
        $hash = md5($payload);
        $key = 'webhook_replay_' . $hash;

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($key)
            ->willReturn('1');

        $this->handleMktCheckoutMock->expects($this->never())
            ->method('getTtlBlockWebhookInSeconds');

        $this->cacheMock->expects($this->never())
            ->method('save');

        $this->assertTrue($this->blocker->isDuplicate($payload));
    }

    public function testReturnsFalseAndSavesWhenNotDuplicate(): void
    {
        $payload = '{"foo":"bar"}';
        $hash = md5($payload);
        $key = 'webhook_replay_' . $hash;

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($key)
            ->willReturn(false);

        $this->handleMktCheckoutMock->expects($this->once())
            ->method('getTtlBlockWebhookInSeconds')
            ->willReturn(180);

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with('1', $key, [], 180);

        $this->assertFalse($this->blocker->isDuplicate($payload));
    }
}
