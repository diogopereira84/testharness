<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <manuel.rosario.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceWebhook\Api\Data\CreateInvoiceMessageInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\MarketplaceWebhook\Model\CreateInvoiceMarketplaceOnlyOrders;

class CreateInvoiceMarketplaceOnlyOrdersTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubmitOrderHelper
     */
    private $submitOrderHelper;

    /**
     * @var CreateInvoiceMessageInterface
     */
    private $message;

    /**
     * @var CreateInvoiceMarketplaceOnlyOrders 
     */
    private $createInvoiceMarketplaceOnlyOrders;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->submitOrderHelper = $this->createMock(SubmitOrderHelper::class);
        $this->message = $this->createMock(CreateInvoiceMessageInterface::class);

        $this->createInvoiceMarketplaceOnlyOrders = new CreateInvoiceMarketplaceOnlyOrders(
            $this->logger,
            $this->submitOrderHelper
        );
    }

    /**
     * Test execute exception.
     */
    public function testExecute(): void
    {
        $orderId = 12345;

        $this->message->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId);

        $this->submitOrderHelper->expects($this->once())
            ->method('generateInvoice')
            ->with($orderId);

        $this->logger->expects($this->never())
            ->method('critical');

        $this->createInvoiceMarketplaceOnlyOrders->execute($this->message);
    }

    /**
     * Test execute with exception.
     */
    public function testExecuteWithException(): void
    {
        $orderId = 12345;
        $exceptionMessage = 'An error occurred';

        $this->message->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId);

        $this->submitOrderHelper->expects($this->once())
            ->method('generateInvoice')
            ->with($orderId)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($exceptionMessage));

        $this->createInvoiceMarketplaceOnlyOrders->execute($this->message);
    }
}
