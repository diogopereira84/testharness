<?php

declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Model;

use Fedex\Catalog\Api\ContextCheckerInterface;
use Fedex\Catalog\Model\ContextChecker;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Fedex\Catalog\Model\ContextChecker
 */
class ContextCheckerTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ContextChecker
     */
    private ContextChecker $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getFullActionName'])
            ->getMockForAbstractClass();

        $this->model = new ContextChecker(
            $this->requestMock
        );
    }

    /**
     * @return void
     */
    public function testIsProductPageReturnsTrue(): void
    {
        $this->requestMock->expects($this->once())
            ->method('getFullActionName')
            ->willReturn(ContextCheckerInterface::CATALOG_PRODUCT_VIEW);

        $this->assertTrue($this->model->isProductPage());
    }

    /**
     * @return void
     */
    public function testIsProductPageReturnsFalseForOtherPages(): void
    {
        $this->requestMock->expects($this->once())
            ->method('getFullActionName')
            ->willReturn('checkout_cart_index');

        $this->assertFalse($this->model->isProductPage());
    }

    /**
     * @return void
     */
    public function testIsConfigurableProductReturnsTrue(): void
    {
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->onlyMethods(['getTypeId'])
            ->getMockForAbstractClass();

        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(ConfigurableType::TYPE_CODE);

        $this->assertTrue($this->model->isConfigurableProduct($productMock));
    }

    /**
     * @return void
     */
    public function testIsConfigurableProductReturnsFalseForSimpleProduct(): void
    {
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->onlyMethods(['getTypeId'])
            ->getMockForAbstractClass();

        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->assertFalse($this->model->isConfigurableProduct($productMock));
    }
}