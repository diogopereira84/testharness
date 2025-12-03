<?php

declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Model;

use Fedex\Catalog\Model\FilterSalableChildren;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fedex\Catalog\Model\FilterSalableChildren
 */
class FilterSalableChildrenTest extends TestCase
{
    /**
     * @var FilterSalableChildren
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->model = new FilterSalableChildren();
    }

    /**
     * @param array $children
     * @param array $expectedResult
     * @dataProvider filterDataProvider
     */
    public function testFilter(array $children, array $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->model->filter($children));
    }

    /**
     * Data provider for testFilter.
     *
     * @return array
     */
    public function filterDataProvider(): array
    {
        $salableChild = $this->createChildMock(true);
        $nonSalableChild = $this->createChildMock(false);

        return [
            'empty children array' => [
                'children' => [],
                'expectedResult' => []
            ],
            'all children salable' => [
                'children' => [$salableChild, $salableChild],
                'expectedResult' => [0 => $salableChild, 1 => $salableChild]
            ],
            'no children salable' => [
                'children' => [$nonSalableChild, $nonSalableChild],
                'expectedResult' => []
            ],
            'mixed salable and non-salable children' => [
                'children' => [$salableChild, $nonSalableChild, $salableChild],
                'expectedResult' => [0 => $salableChild, 2 => $salableChild]
            ]
        ];
    }

    /**
     * Creates a mock for a child product.
     *
     * @param bool $isSalable
     * @return MockObject|Product
     */
    private function createChildMock(bool $isSalable): MockObject
    {
        $childMock = $this->createMock(Product::class);
        $childMock->method('isSalable')
            ->willReturn($isSalable);

        return $childMock;
    }
}