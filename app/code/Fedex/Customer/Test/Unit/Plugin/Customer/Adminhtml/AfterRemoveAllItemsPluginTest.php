<?php

declare(strict_types=1);

namespace Fedex\Customer\Test\Unit\Plugin\Customer\Adminhtml;

use Fedex\Customer\Model\QuoteManager;
use Fedex\Customer\Plugin\Customer\Adminhtml\AfterRemoveAllItemsPlugin;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Fedex\Customer\Plugin\Customer\Adminhtml\AfterRemoveAllItemsPlugin
 */
class AfterRemoveAllItemsPluginTest extends TestCase
{
    /**
     * @var QuoteManager|MockObject
     */
    private $quoteManagerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var AfterRemoveAllItemsPlugin
     */
    private AfterRemoveAllItemsPlugin $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->quoteManagerMock = $this->createMock(QuoteManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->plugin = new AfterRemoveAllItemsPlugin(
            $this->quoteManagerMock,
            $this->loggerMock
        );
    }

    public function testAfterRemoveAllItems(): void
    {
        $quoteMock = $this->createMock(Quote::class);

        $this->quoteManagerMock->expects($this->once())
            ->method('resetCustomerQuote')
            ->with($quoteMock);

        $this->loggerMock->expects($this->never())
            ->method('error');

        $result = $this->plugin->afterRemoveAllItems($quoteMock, $quoteMock);

        $this->assertSame($quoteMock, $result);
    }

    public function testAfterRemoveAllItemsWithException(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $exception = new \Exception('Test exception message');
        $expectedLogMessage = '[Fedex_Customer] AfterRemoveAllItemsPlugin error: Test exception message';

        $this->quoteManagerMock->expects($this->once())
            ->method('resetCustomerQuote')
            ->with($quoteMock)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($expectedLogMessage, ['exception' => $exception]);

        $result = $this->plugin->afterRemoveAllItems($quoteMock, $quoteMock);

        $this->assertSame($quoteMock, $result);
    }
}