<?php
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Plugin\Block\Product\ProductList;

use PHPUnit\Framework\TestCase;
use Magento\Theme\Block\Html\Pager;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Fedex\Catalog\Plugin\Block\Product\ProductList\CatalogToolbarPagination;
use Magento\Framework\Data\Collection;

class CatalogToolbarPaginationTest extends TestCase
{
    private MvpHelper $mvpHelper;

    protected function setUp(): void
    {
        $this->mvpHelper = $this->createMock(MvpHelper::class);
    }

    public function testAfterGetFrameEndWithCustomPagination(): void
    {
        $pager = $this->createMock(Pager::class);
        $collection = $this->createMock(Collection::class);

        $this->mvpHelper->method('shouldApplyCustomPagination')->willReturn(true);
        $this->mvpHelper->method('getSessionPageSize')->willReturn(10);

        $pager->method('getCollection')->willReturn($collection);
        $collection->expects($this->once())->method('setPageSize')->with(10);
        $collection->method('getPageSize')->willReturn(10);
        $collection->method('getSize')->willReturn(95);

        $plugin = new CatalogToolbarPagination($this->mvpHelper);
        $result = $plugin->afterGetFrameEnd($pager, 5);

        $this->assertEquals(10, $result); // 95 / 10 = 9.5 => 10
    }

    public function testAfterGetLastPageNumWithCustomPagination(): void
    {
        $pager = $this->createMock(Pager::class);
        $collection = $this->createMock(Collection::class);

        $this->mvpHelper->method('shouldApplyCustomPagination')->willReturn(true);
        $this->mvpHelper->method('getSessionPageSize')->willReturn(20);

        $pager->method('getCollection')->willReturn($collection);
        $collection->expects($this->once())->method('setPageSize')->with(20);
        $collection->method('getPageSize')->willReturn(20);
        $collection->method('getSize')->willReturn(90);

        $plugin = new CatalogToolbarPagination($this->mvpHelper);
        $result = $plugin->afterGetLastPageNum($pager, 5);

        $this->assertEquals(5, $result); // 90 / 20 = 4.5 => 5
    }

    public function testCalculateTotalPagesWithZeroPageSizeReturnsOriginalResult(): void
    {
        $pager = $this->createMock(Pager::class);
        $collection = $this->createMock(Collection::class);

        $this->mvpHelper->method('shouldApplyCustomPagination')->willReturn(true);
        $this->mvpHelper->method('getSessionPageSize')->willReturn(0);

        $pager->method('getCollection')->willReturn($collection);
        $collection->expects($this->never())->method('setPageSize');
        $collection->method('getPageSize')->willReturn(0);
        $collection->method('getSize')->willReturn(100);

        $plugin = new CatalogToolbarPagination($this->mvpHelper);
        $result = $plugin->afterGetLastPageNum($pager, 7);

        $this->assertEquals(7, $result); // original result returned due to 0 page size
    }

    public function testReturnsOriginalResultIfCustomPaginationIsNotApplied(): void
    {
        $pager = $this->createMock(Pager::class);

        $this->mvpHelper->method('shouldApplyCustomPagination')->willReturn(false);

        $plugin = new CatalogToolbarPagination($this->mvpHelper);

        $originalResult = 5;

        $this->assertEquals($originalResult, $plugin->afterGetFrameEnd($pager, $originalResult));
        $this->assertEquals($originalResult, $plugin->afterGetLastPageNum($pager, $originalResult));
    }

    public function testCalculateTotalPagesUsesHelperLimit(): void
    {
        $pager = $this->createMock(Pager::class);
        $collection = $this->createMock(Collection::class);

        $this->mvpHelper->method('shouldApplyCustomPagination')->willReturn(true);
        $this->mvpHelper->method('getSessionPageSize')->willReturn(15);

        $pager->method('getCollection')->willReturn($collection);
        $collection->expects($this->once())->method('setPageSize')->with(15);
        $collection->method('getPageSize')->willReturn(15);
        $collection->method('getSize')->willReturn(150);

        $plugin = new CatalogToolbarPagination($this->mvpHelper);
        $result = $plugin->afterGetFrameEnd($pager, 3);

        $this->assertEquals(10, $result); // 150 / 15 = 10
    }
}
