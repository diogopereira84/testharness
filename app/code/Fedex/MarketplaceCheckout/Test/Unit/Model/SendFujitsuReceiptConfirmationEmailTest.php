<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Model\SendFujitsuReceiptConfirmationEmail;
use Psr\Log\LoggerInterface;
use Fedex\FujitsuReceipt\Model\FujitsuReceipt;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SendFujitsuReceiptConfirmationEmailTest extends TestCase
{
    /** @var LoggerInterface|PHPUnit\Framework\MockObject\MockObject */
    private LoggerInterface $logger;

    /** @var FujitsuReceipt|PHPUnit\Framework\MockObject\MockObject */
    private FujitsuReceipt $fujitsuReceipt;

    /** @var ToggleConfig|PHPUnit\Framework\MockObject\MockObject */
    private ToggleConfig $toggleConfig;

    /** @var SendFujitsuReceiptConfirmationEmail */
    private SendFujitsuReceiptConfirmationEmail $model;

    /** @var string */
    private string $payload;

    /**
     * Sets up the test environment.
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fujitsuReceipt = $this->createMock(FujitsuReceipt::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->model = new SendFujitsuReceiptConfirmationEmail(
            $this->logger,
            $this->fujitsuReceipt,
            $this->toggleConfig
        );

        $this->payload = json_encode(['order_id' => 'ORD123', 'amount' => 99.95]);
    }

    /**
     * Tests the execute method behavior when the feature toggle is disabled.
     * @return void
     */
    public function testExecuteWhenToggleDisabled(): void
    {
        $this->toggleConfig
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('new_fujitsu_receipt_approach')
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Send fujitsu receipt confirmation email data: ' . $this->payload);

        $decoded = json_decode($this->payload, true);
        $this->fujitsuReceipt
            ->expects($this->once())
            ->method('sendFujitsuReceiptConfirmationEmail')
            ->with($this->equalTo($decoded));

        $result = $this->model->execute($this->payload);
        $this->assertNull($result, 'execute() should return null when toggle is disabled');
    }

    /**
     * Tests the execution flow when the feature toggle is enabled.
     * @return void
     */
    public function testExecuteWhenToggleEnabled(): void
    {
        $this->toggleConfig
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('new_fujitsu_receipt_approach')
            ->willReturn(true);

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->fujitsuReceipt
            ->expects($this->never())
            ->method('sendFujitsuReceiptConfirmationEmail');

        $result = $this->model->execute($this->payload);
        $this->assertNull($result, 'execute() should return null when toggle is enabled');
    }
}
