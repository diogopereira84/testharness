<?php
/**
 * @category  Fedex
 * @package   Fedex_CategoryLayout
 */
declare(strict_types=1);

namespace Fedex\CategoryLayout\Block;

use Fedex\CategoryLayout\Block\CategoryUpdate;
use Fedex\CatalogMvp\Model\Source\SharedCatalogs;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;

class CategoryUpdateTest extends TestCase
{
    protected $categoryUpdate;
    /**
     * @var SharedCatalogs|MockObject
     */
    private $sharedCatalogsMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    private $data = [];

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogsMock = $this->createMock(SharedCatalogs::class);

        $this->categoryUpdate = new CategoryUpdate(
            $this->contextMock,
            $this->sharedCatalogsMock,
            $this->data
        );
    }

    /**
     * Test getCategories method
     */
    public function testGetCategories(): void
    {
        $this->sharedCatalogsMock->expects($this->once())
            ->method('toOptionArray')
            ->willReturn([]);

        $this->assertEquals([], $this->categoryUpdate->getCategories());
    }
}
