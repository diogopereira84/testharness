<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\StoreRefDTO;
use PHPUnit\Framework\TestCase;

class StoreRefDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $storeRef = new StoreRefDTO('1', '100', 'store@example.com');
        $this->assertEquals('1', $storeRef->getStoreId());
        $this->assertEquals('100', $storeRef->getStoreNumber());
        $this->assertEquals('store@example.com', $storeRef->getStoreEmail());
    }
}
