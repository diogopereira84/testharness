<?php
/**
 * @category  Fedex
 * @package   Fedex_Rate
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Rate\Test\Unit\Block\Product\View;

use Magento\Catalog\Helper\Data;
use PHPUnit\Framework\TestCase;
use Fedex\Rate\Block\Product\View\PriceBox;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Catalog\Model\Config as CatalogConfig;

/**
 * Class PriceBoxTest
 *
 * @covers \Fedex\Rate\Block\Product\View\PriceBox
 */
class PriceBoxTest extends TestCase
{
    /**
     * @var PriceBox
     */
    private $priceBox;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var CatalogConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogConfig;

    /**
     * @var \Magento\Catalog\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $catalogData;

    /**
     * Setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->catalogConfig = $this->createMock(CatalogConfig::class);
        $this->catalogData = $this->createMock(Data::class);

        $this->priceBox = new PriceBox(
            $this->contextMock,
            $this->catalogConfig,
            $this->catalogData
        );
    }

    /**
     * @return void
     */
    public function testGetCatalogConfig()
    {
        $this->assertSame(
            $this->catalogConfig,
            $this->priceBox->getCatalogConfig()
        );
    }

    /**
     * @return void
     */
    public function testGetProductTypeReturnsTypeId()
    {
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId'])
            ->getMock();
        $productMock->method('getTypeId')->willReturn('simple');
        $this->catalogData->method('getProduct')->willReturn($productMock);
        $this->assertSame('simple', $this->priceBox->getProductType());
    }

    /**
     * @return void
     */
    public function testGetProductTypeReturnsNullIfNoProduct()
    {
        $this->catalogData->method('getProduct')->willReturn(null);
        $this->assertNull($this->priceBox->getProductType());
    }
}
