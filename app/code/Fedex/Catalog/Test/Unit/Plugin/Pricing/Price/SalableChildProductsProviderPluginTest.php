<?php

declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Plugin\Pricing\Price;

use Fedex\Catalog\Model\ContextChecker;
use Fedex\Catalog\Model\FilterSalableChildren;
use Fedex\Catalog\Model\ToggleConfig;
use Fedex\Catalog\Plugin\Pricing\Price\SalableChildProductsProviderPlugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalableChildProductsProviderPluginTest extends TestCase
{
    /**
     * @var ToggleConfig|MockObject
     */
    private $configMock;

    /**
     * @var ContextChecker|MockObject
     */
    private $checkerMock;

    /**
     * @var FilterSalableChildren|MockObject
     */
    private $childFilterMock;

    /**
     * @var SalableChildProductsProviderPlugin
     */
    private $plugin;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isEssendantToggleEnabled'])
            ->getMock();

        $this->checkerMock = $this->getMockBuilder(ContextChecker::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isProductPage', 'isConfigurableProduct'])
            ->getMock();

        $this->childFilterMock = $this->createMock(FilterSalableChildren::class);

        $this->plugin = new SalableChildProductsProviderPlugin(
            $this->configMock,
            $this->checkerMock,
            $this->childFilterMock
        );
    }

    /**
     * @return array
     */
    public function skipProcessingDataProvider(): array
    {
        return [
            'Not on product page' => [false, true, true],
            'Not a configurable product' => [true, false, true],
            'Toggle is disabled' => [true, true, false],
        ];
    }

    /**
     * @dataProvider skipProcessingDataProvider
     * @param bool $isProductPage
     * @param bool $isConfigurable
     * @param bool $isToggleEnabled
     */
    public function testAfterGetProductsSkipsProcessing(
        bool $isProductPage,
        bool $isConfigurable,
        bool $isToggleEnabled
    ): void {
        $subjectMock = $this->createMock(LowestPriceOptionsProvider::class);
        $productMock = $this->createMock(ProductInterface::class);
        $originalResult = [$this->createMock(ProductInterface::class)];

        $this->checkerMock->method('isProductPage')->willReturn($isProductPage);
        $this->checkerMock->method('isConfigurableProduct')->with($productMock)->willReturn($isConfigurable);
        $this->configMock->method('isEssendantToggleEnabled')->willReturn($isToggleEnabled);

        $this->childFilterMock->expects($this->never())->method('filter');

        $result = $this->plugin->afterGetProducts(
            $subjectMock,
            $originalResult,
            $productMock
        );

        $this->assertSame($originalResult, $result);
    }

    /**
     * Test afterGetProducts returns filtered children when all conditions are met.
     */
    public function testAfterGetProductsReturnsFilteredChildren(): void
    {
        $subjectMock = $this->createMock(LowestPriceOptionsProvider::class);
        $originalResult = [$this->createMock(ProductInterface::class)];

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getTypeInstance'])
            ->getMockForAbstractClass();

        $typeInstanceMock = $this->createMock(Configurable::class);
        $productMock->method('getTypeInstance')->willReturn($typeInstanceMock);

        $unfilteredChildren = [
            $this->createMock(ProductInterface::class),
            $this->createMock(ProductInterface::class)
        ];
        $typeInstanceMock->method('getUsedProducts')->with($productMock)->willReturn($unfilteredChildren);

        $filteredChildren = [$this->createMock(ProductInterface::class)];
        $this->childFilterMock->expects($this->once())
            ->method('filter')
            ->with($unfilteredChildren)
            ->willReturn($filteredChildren);

        $this->checkerMock->method('isProductPage')->willReturn(true);
        $this->checkerMock->method('isConfigurableProduct')->with($productMock)->willReturn(true);
        $this->configMock->method('isEssendantToggleEnabled')->willReturn(true);

        $result = $this->plugin->afterGetProducts(
            $subjectMock,
            $originalResult,
            $productMock
        );

        $this->assertSame($filteredChildren, $result);
    }

    /**
     * Test afterGetProducts returns original result when filtered children array is empty.
     */
    public function testAfterGetProductsReturnsOriginalResultWhenFilteredChildrenIsEmpty(): void
    {
        $subjectMock = $this->createMock(LowestPriceOptionsProvider::class);
        $originalResult = [$this->createMock(ProductInterface::class)];

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->addMethods(['getTypeInstance'])
            ->getMockForAbstractClass();

        $typeInstanceMock = $this->createMock(Configurable::class);
        $productMock->method('getTypeInstance')->willReturn($typeInstanceMock);

        $unfilteredChildren = [$this->createMock(ProductInterface::class)];
        $typeInstanceMock->method('getUsedProducts')->with($productMock)->willReturn($unfilteredChildren);

        $this->childFilterMock->expects($this->once())
            ->method('filter')
            ->with($unfilteredChildren)
            ->willReturn([]);

        $this->checkerMock->method('isProductPage')->willReturn(true);
        $this->checkerMock->method('isConfigurableProduct')->with($productMock)->willReturn(true);
        $this->configMock->method('isEssendantToggleEnabled')->willReturn(true);

        $result = $this->plugin->afterGetProducts(
            $subjectMock,
            $originalResult,
            $productMock
        );

        $this->assertSame($originalResult, $result);
    }
}