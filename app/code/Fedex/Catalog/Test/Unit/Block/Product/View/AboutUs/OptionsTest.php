<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Block\Product\View\AboutUs;

use Fedex\Catalog\Block\Product\View\AboutUs\Options;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Fedex\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    /**
     * @var Options
     */
    private $optionsBlock;

    /**
     * @var CatalogHelper|MockObject
     */
    private $catalogHelperMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogHelperMock = $this->createMock(CatalogHelper::class);
        $this->configMock = $this->createMock(Config::class);

        $this->optionsBlock = new Options(
            $this->contextMock,
            $this->catalogHelperMock,
            $this->configMock
        );
    }

    /**
     *
     */
    public function testGetProductOptions(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->any())
            ->method('getData')
            ->with('product_options')
            ->willReturn('options');

        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->configMock->expects($this->once())
            ->method('formatAttribute')
            ->with($productMock, 'product_options')
            ->willReturn('formatted options');

        $expectedResult = 'formatted options';
        $actualResult = $this->optionsBlock->getProductOptions();

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     *
     */
    public function testGetProductOptionsWithNoProduct(): void
    {
        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->configMock->expects($this->never())
            ->method('formatAttribute');

        $this->assertEquals('', $this->optionsBlock->getProductOptions());
    }
}
