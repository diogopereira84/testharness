<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Block\Product\View\AboutUs;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\Catalog\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Block\Product\View\AboutUs\Info;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\Store;

class InfoTest extends TestCase
{
    /**
     * @var Info
     */
    private $infoBlock;

    /**
     * @var CatalogHelper|MockObject
     */
    private $catalogHelperMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

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
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->infoBlock = new Info(
            $this->contextMock,
            $this->storeManagerMock,
            $this->catalogHelperMock,
            $this->configMock
        );
    }

    /**
     *
     */
    public function testGetProductInfoWithProduct(): void
    {
        $productMock = $this->createMock(Product::class);

        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $expectedResult = [
            'content-left' => 'Product Info',
            'content-right' => '',
            'content-right-mobile' => '',
        ];

        $this->configMock->expects($this->once())
            ->method('formatAttribute')
            ->with($productMock, 'product_info')
            ->willReturn('Product Info');

        $this->assertEquals($expectedResult, $this->infoBlock->getProductInfo());
    }

    /**
     *
     */
    public function testGetProductInfoWithoutProduct(): void
    {
        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->assertEquals([], $this->infoBlock->getProductInfo());
    }

    /**
     *
     */
    public function testGetInfoImageWithImage(): void
    {
        $baseUrl = 'http://example.com/';
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($baseUrl);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_info_image')
            ->willReturn('some_image.jpg');

        $expectedResult = $baseUrl . 'catalog/product' . 'some_image.jpg';

        $this->assertEquals($expectedResult, $this->infoBlock->getInfoImage($productMock));
    }

    /**
     *
     */
    public function testGetInfoImageWithNoImage(): void
    {
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->never())
            ->method('getBaseUrl');

        $this->storeManagerMock->expects($this->never())
            ->method('getStore');

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_info_image')
            ->willReturn('no_selection');

        $this->assertEquals('', $this->infoBlock->getInfoImage($productMock));
    }

    /**
     *
     */
    public function testGetInfoImageMobileWithImage(): void
    {
        $baseUrl = 'http://example.com/';
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($baseUrl);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_info_image_mobile')
            ->willReturn('some_image.jpg');

        $expectedResult = $baseUrl . 'catalog/product' . 'some_image.jpg';

        $this->assertEquals($expectedResult, $this->infoBlock->getInfoImageMobile($productMock));
    }

    /**
     *
     */
    public function testGetInfoImageMobileWithNoImage(): void
    {
        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->never())
            ->method('getBaseUrl');

        $this->storeManagerMock->expects($this->never())
            ->method('getStore');

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_info_image_mobile')
            ->willReturn('no_selection');

        $this->assertEquals('', $this->infoBlock->getInfoImageMobile($productMock));
    }
}
