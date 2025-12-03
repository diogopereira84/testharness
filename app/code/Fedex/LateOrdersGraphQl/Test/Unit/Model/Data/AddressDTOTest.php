<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\AddressDTO;
use PHPUnit\Framework\TestCase;

class AddressDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $address = new AddressDTO('line1', 'line2', 'city', 'region', '12345', 'US');
        $this->assertEquals('line1', $address->getLine1());
        $this->assertEquals('line2', $address->getLine2());
        $this->assertEquals('city', $address->getCity());
        $this->assertEquals('region', $address->getRegion());
        $this->assertEquals('12345', $address->getPostalCode());
        $this->assertEquals('US', $address->getCountry());
    }

    public function testNullValues()
    {
        $address = new AddressDTO(null, null, null, null, null, null);
        $this->assertNull($address->getLine1());
        $this->assertNull($address->getLine2());
        $this->assertNull($address->getCity());
        $this->assertNull($address->getRegion());
        $this->assertNull($address->getPostalCode());
        $this->assertNull($address->getCountry());
    }
}
