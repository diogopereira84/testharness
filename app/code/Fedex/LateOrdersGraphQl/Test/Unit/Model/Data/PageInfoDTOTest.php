<?php
namespace Fedex\LateOrdersGraphQl\Test\Unit\Model\Data;

use Fedex\LateOrdersGraphQl\Model\Data\PageInfoDTO;
use PHPUnit\Framework\TestCase;

class PageInfoDTOTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $pageInfo = new PageInfoDTO(2, 20, 5, true);
        $this->assertEquals(2, $pageInfo->getCurrentPage());
        $this->assertEquals(20, $pageInfo->getPageSize());
        $this->assertEquals(5, $pageInfo->getTotalPages());
        $this->assertTrue($pageInfo->getHasNextPage());
    }

    public function testHasNextPageFalse()
    {
        $pageInfo = new PageInfoDTO(1, 10, 1, false);
        $this->assertFalse($pageInfo->getHasNextPage());
    }
}
