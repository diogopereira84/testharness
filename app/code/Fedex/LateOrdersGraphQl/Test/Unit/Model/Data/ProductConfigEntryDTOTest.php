<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\ProductConfigEntryDTO;
use PHPUnit\Framework\TestCase;

class ProductConfigEntryDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $entry = new ProductConfigEntryDTO('color', 'blue');
        $this->assertEquals('color', $entry->getKey());
        $this->assertEquals('blue', $entry->getValue());
    }

    public function testNullValue()
    {
        $entry = new ProductConfigEntryDTO('size', null);
        $this->assertEquals('size', $entry->getKey());
        $this->assertNull($entry->getValue());
    }
}
