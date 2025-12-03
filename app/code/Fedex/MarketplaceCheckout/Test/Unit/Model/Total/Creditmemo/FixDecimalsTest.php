<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Total\Creditmemo;

use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceCheckout\Model\Total\Creditmemo\FixDecimals;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class FixDecimalsTest extends TestCase
{
    private FixDecimals $fixDecimals;
    private HandleMktCheckout $handleMktCheckout;
    private Creditmemo $creditmemo;
    private Order $order;

    protected function setUp(): void
    {
        $this->handleMktCheckout = $this->createMock(HandleMktCheckout::class);
        $this->creditmemo = $this->createMock(Creditmemo::class);
        $this->order = $this->getMockBuilder(Order::class)
            ->onlyMethods(['getTotalRefunded', 'getSubtotal', 'getBaseSubtotal', 'getSubtotalInclTax', 'getBaseSubtotalInclTax', 'getGrandTotal', 'getBaseGrandTotal', 'getSubtotalRefunded', 'getBaseSubtotalRefunded'])
            ->addMethods(['getSubtotalInclTaxRefunded', 'getBaseSubtotalInclTaxRefunded', 'getGrandTotalRefunded', 'getBaseGrandTotalRefunded'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fixDecimals = new FixDecimals($this->handleMktCheckout);
    }

    public function testCollectWhenOrderCancellationRefundsEnabled(): void
    {
        $this->creditmemo->method('getOrder')->willReturn($this->order);

        $this->order->method('getGrandTotal')->willReturn(100.00);
        $this->order->method('getTotalRefunded')->willReturn(50.00);

        $this->creditmemo->method('getSubtotal')->willReturn(50.00);
        $this->creditmemo->method('getBaseSubtotal')->willReturn(50.00);
        $this->creditmemo->method('getSubtotalInclTax')->willReturn(50.00);
        $this->creditmemo->method('getBaseSubtotalInclTax')->willReturn(50.00);
        $this->creditmemo->method('getGrandTotal')->willReturn(50.00);
        $this->creditmemo->method('getBaseGrandTotal')->willReturn(50.00);

        $this->order->method('getSubtotalRefunded')->willReturn(0.00);
        $this->order->method('getBaseSubtotalRefunded')->willReturn(0.00);
        $this->order->method('getSubtotalInclTaxRefunded')->willReturn(0.00);
        $this->order->method('getBaseSubtotalInclTaxRefunded')->willReturn(0.00);
        $this->order->method('getGrandTotalRefunded')->willReturn(0.00);
        $this->order->method('getBaseGrandTotalRefunded')->willReturn(0.00);

        $this->assertSame($this->fixDecimals, $this->fixDecimals->collect($this->creditmemo));
    }
}
