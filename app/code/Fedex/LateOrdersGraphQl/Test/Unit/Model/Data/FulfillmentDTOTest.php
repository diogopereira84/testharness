<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\FulfillmentDTO;
use Fedex\LateOrdersGraphQl\Api\Data\AddressDTOInterface;
use PHPUnit\Framework\TestCase;

class FulfillmentDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $address = $this->createMock(AddressDTOInterface::class);
        $fulfillment = new FulfillmentDTO('delivery', '2025-10-01T10:00:00Z', '2025-10-01T12:00:00Z', '123456', $address);
        $this->assertEquals('delivery', $fulfillment->getType());
        $this->assertEquals('2025-10-01T10:00:00Z', $fulfillment->getPickupTime());
        $this->assertEquals('2025-10-01T12:00:00Z', $fulfillment->getDeliveryTime());
        $this->assertEquals('123456', $fulfillment->getShippingAccountNumber());
        $this->assertSame($address, $fulfillment->getShippingAddress());
    }

    public function testNullValues()
    {
        $fulfillment = new FulfillmentDTO(null, null, null, null, null);
        $this->assertNull($fulfillment->getType());
        $this->assertNull($fulfillment->getPickupTime());
        $this->assertNull($fulfillment->getDeliveryTime());
        $this->assertNull($fulfillment->getShippingAccountNumber());
        $this->assertNull($fulfillment->getShippingAddress());
    }
}
