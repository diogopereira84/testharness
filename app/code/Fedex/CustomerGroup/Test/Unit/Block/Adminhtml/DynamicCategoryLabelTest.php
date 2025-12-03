<?php

namespace Fedex\CustomerGroup\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Fedex\CustomerGroup\Block\Adminhtml\DynamicCategoryLabel;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Backend\Block\Template\Context;

class DynamicCategoryLabelTest extends TestCase
{

    public const B2B_ROOT_CATEGORY = 'B2B Root Category';

    protected $contextMock;
    protected $catalogMvpHelperMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootCategoryDetailFromStore'])
            ->getMock();
    }

    public function testCategoryB2BReturnsCorrectLabel()
    {
        $this->catalogMvpHelperMock->expects($this->any())->method('getRootCategoryDetailFromStore')->willReturn('B2B Root Category');
        
        $block = new DynamicCategoryLabel($this->contextMock , $this->catalogMvpHelperMock);
        $this->assertEquals(self::B2B_ROOT_CATEGORY, $block->categoryB2B());
    }
}
