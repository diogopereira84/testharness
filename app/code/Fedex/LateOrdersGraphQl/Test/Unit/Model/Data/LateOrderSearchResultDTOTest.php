<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSearchResult;
use Fedex\LateOrdersGraphQl\Model\Data\LateOrderSearchResultDTO;
use Fedex\LateOrdersGraphQl\Api\Data\PageInfoDTOInterface;
use PHPUnit\Framework\TestCase;

class LateOrderSearchResultDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $items = ['item1', 'item2'];
        $pageInfo = $this->createMock(PageInfoDTOInterface::class);
        $result = new LateOrderSearchResultDTO($items, 2, $pageInfo);
        $this->assertEquals($items, $result->getItems());
        $this->assertEquals(2, $result->getTotalCount());
        $this->assertSame($pageInfo, $result->getPageInfo());
    }
}
