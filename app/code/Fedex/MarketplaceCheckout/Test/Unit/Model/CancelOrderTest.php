<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Model\CancelOrder;
use Mirakl\Api\Helper\ClientHelper\MMP;
use Psr\Log\LoggerInterface;
use Mirakl\MMP\FrontOperator\Request\Order\Workflow\CancelOrderRequest;

class CancelOrderTest extends TestCase
{
    /** @var MMP|\PHPUnit\Framework\MockObject\MockObject */
    private MMP $clientMock;
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private LoggerInterface $loggerMock;
    /** @var CancelOrder */
    private CancelOrder $cancelOrder;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(MMP::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cancelOrder = new CancelOrder($this->clientMock, $this->loggerMock);
    }

    /**
     * Tests the successful cancellation of an order.
     * @return void
     */
    public function testCancelOrderSuccess(): void
    {
        $orderId = 'ORDER123';

        $this->clientMock
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(CancelOrderRequest::class));

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Order was cancelled in Mirakl : ' . $orderId));

        $result = $this->cancelOrder->cancelOrder($orderId);
        $this->assertTrue($result);
    }

    /**
     * Test that canceling an order logs an exception and returns false upon failure.
     * @return void
     */
    public function testCancelOrderFailureLogsExceptionAndReturnsFalse(): void
    {
        $orderId = 'ORDER456';
        $exceptionMessage = 'Network error';

        $this->clientMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception($exceptionMessage));

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Order could not be cancelled in Mirakl due to ' . $exceptionMessage));

        $result = $this->cancelOrder->cancelOrder($orderId);
        $this->assertFalse($result);
    }
}
