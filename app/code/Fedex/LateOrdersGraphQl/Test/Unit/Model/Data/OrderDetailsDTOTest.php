<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\OrderDetailsDTO;
use Fedex\LateOrdersGraphQl\Api\Data\CustomerDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\FulfillmentDTOInterface;
use Fedex\LateOrdersGraphQl\Api\Data\StoreRefDTOInterface;
use PHPUnit\Framework\TestCase;

class OrderDetailsDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $customer = $this->createMock(CustomerDTOInterface::class);
        $fulfillment = $this->createMock(FulfillmentDTOInterface::class);
        $store = $this->createMock(StoreRefDTOInterface::class);
        $items = ['item1', 'item2'];
        $details = new OrderDetailsDTO(
            'OID123',
            'processing',
            '2025-10-01T12:00:00Z',
            $customer,
            $fulfillment,
            $store,
            $items,
            'note',
            true
        );
        $this->assertEquals('OID123', $details->getOrderId());
        $this->assertEquals('processing', $details->getStatus());
        $this->assertEquals('2025-10-01T12:00:00Z', $details->getCreatedAt());
        $this->assertSame($customer, $details->getCustomer());
        $this->assertSame($fulfillment, $details->getFulfillment());
        $this->assertSame($store, $details->getStore());
        $this->assertEquals($items, $details->getItems());
        $this->assertEquals('note', $details->getOrderNotes());
        $this->assertTrue($details->getIs1p());
    }

    public function testNullFulfillmentAndOrderNotes()
    {
        $customer = $this->createMock(CustomerDTOInterface::class);
        $store = $this->createMock(StoreRefDTOInterface::class);
        $details = new OrderDetailsDTO(
            'OID124',
            'complete',
            '2025-10-01T13:00:00Z',
            $customer,
            null,
            $store,
            [],
            null,
            false
        );
        $this->assertNull($details->getFulfillment());
        $this->assertNull($details->getOrderNotes());
        $this->assertFalse($details->getIs1p());
    }
}
