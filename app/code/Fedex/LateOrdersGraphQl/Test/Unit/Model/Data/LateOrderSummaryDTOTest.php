<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSummaryDTO;
use PHPUnit\Framework\TestCase;

class LateOrderSummaryDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $summary = new LateOrderSummaryDTO('OID123', '2025-10-01T12:00:00Z', 'processing', true);
        $this->assertEquals('OID123', $summary->getOrderId());
        $this->assertEquals('2025-10-01T12:00:00Z', $summary->getCreatedAt());
        $this->assertEquals('processing', $summary->getStatus());
        $this->assertTrue($summary->getIs1p());
    }

    public function testFalseIs1p()
    {
        $summary = new LateOrderSummaryDTO('OID124', '2025-10-01T13:00:00Z', 'complete', false);
        $this->assertFalse($summary->getIs1p());
    }
}
