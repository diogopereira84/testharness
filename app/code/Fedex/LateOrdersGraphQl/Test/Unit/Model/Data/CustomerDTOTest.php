<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\CustomerDTO;
use PHPUnit\Framework\TestCase;

class CustomerDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $customer = new CustomerDTO('John Doe', 'john@example.com', '123456789');
        $this->assertEquals('John Doe', $customer->getName());
        $this->assertEquals('john@example.com', $customer->getEmail());
        $this->assertEquals('123456789', $customer->getPhone());
    }

    public function testNullPhone()
    {
        $customer = new CustomerDTO('Jane Doe', 'jane@example.com', null);
        $this->assertEquals('Jane Doe', $customer->getName());
        $this->assertEquals('jane@example.com', $customer->getEmail());
        $this->assertNull($customer->getPhone());
    }
}
