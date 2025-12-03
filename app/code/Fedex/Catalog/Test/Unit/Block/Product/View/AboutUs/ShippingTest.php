<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Block\Product\View\AboutUs;

use Fedex\Catalog\Block\Product\View\AboutUs\Shipping;
use Fedex\Catalog\Model\Config;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var CatalogHelper|MockObject
     */
    private $catalogHelperMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Shipping
     */
    private $shippingBlock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogHelperMock = $this->getMockBuilder(CatalogHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingBlock = new Shipping(
            $this->contextMock,
            $this->catalogHelperMock,
            $this->configMock
        );
    }

    /**
     *
     */
    public function testGetProductShippingInfo(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->any())
            ->method('getData')
            ->with('shipping_estimator_content_new')
            ->willReturn('Some shipping info.');

        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->configMock->expects($this->once())
            ->method('formatAttribute')
            ->with($productMock, 'shipping_estimator_content_new')
            ->willReturn('Some formatted shipping info.');

        $this->assertEquals('Some formatted shipping info.', $this->shippingBlock->getProductShippingInfo());
    }

    /**
     *
     */
    public function testGetProductShippingInfoWithNoProduct(): void
    {
        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->assertEquals('', $this->shippingBlock->getProductShippingInfo());
    }
}
