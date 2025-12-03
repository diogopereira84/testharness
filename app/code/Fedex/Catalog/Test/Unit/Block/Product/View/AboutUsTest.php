<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Block\Product\View;

use Fedex\Catalog\Block\Product\View\AboutUs;
use Fedex\Catalog\Model\Config;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class AboutUsTest extends TestCase
{
    /**
     * @var AboutUs
     */
    private $aboutUsBlock;

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
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var string
     */
    private string $baseUrl;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->baseUrl = 'http://example.com/';
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->catalogHelperMock = $this->createMock(CatalogHelper::class);
        $this->configMock = $this->createMock(Config::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->productMock = $this->createMock(Product::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $this->aboutUsBlock = new AboutUs(
            $this->contextMock,
            $this->storeManagerMock,
            $this->catalogHelperMock,
            $this->configMock,
            $this->toggleConfigMock
        );
    }

    /**
     * Test getProductIdeas method
     */
    public function testGetProductIdeas(): void
    {
        $this->catalogHelperMock->method('getProduct')->willReturn($this->productMock);

        $this->storeManagerMock->expects($this->atMost(2))
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->atMost(2))
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($this->baseUrl);

        $this->productMock->expects($this->atMost(2))
            ->method('getData')
            ->withConsecutive(['product_ideas_image'], ['product_ideas_image_mobile'])
            ->willReturnOnConsecutiveCalls('/some_ideas', '/some_ideas_mobile');

        $this->configMock->expects($this->once())
            ->method('formatAttribute')
            ->with($this->productMock, 'product_ideas')
            ->willReturn('formatted_ideas');

        $result = $this->aboutUsBlock->getProductIdeas();
        $this->assertEquals([
            'content-left' => 'formatted_ideas',
            'content-right' => 'http://example.com/catalog/product/some_ideas',
            'content-right-mobile' => 'http://example.com/catalog/product/some_ideas_mobile'
        ], $result);
    }

    /**
     * Test getProductIdeas method
     */
    public function testGetProductIdeasWhenProductNotPresent(): void
    {
        $this->catalogHelperMock->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->assertEquals([], $this->aboutUsBlock->getProductIdeas());
    }

    /**
     * Test getProductIdeas method
     */
    public function testGetIdeasImageWithImage(): void
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($this->baseUrl);

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_ideas_image')
            ->willReturn('/some_image.jpg');
        $result = $this->aboutUsBlock->getIdeasImage($productMock);
        $expected = $this->baseUrl . 'catalog/product/some_image.jpg';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getProductIdeas method
     */
    public function testGetIdeasImageWithNoImage(): void
    {
        $this->storeManagerMock->expects($this->never())
            ->method('getStore');

        $this->storeMock->expects($this->never())
            ->method('getBaseUrl');

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_ideas_image')
            ->willReturn(null);

        $this->assertEquals('', $this->aboutUsBlock->getIdeasImage($productMock));
    }

    /**
     * Test getProductIdeasMobile method
     */
    public function testGetIdeasImageMobileWithImage(): void
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn($this->baseUrl);

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_ideas_image_mobile')
            ->willReturn('/some_image_mobile.jpg');
        $result = $this->aboutUsBlock->getIdeasImageMobile($productMock);
        $expected = $this->baseUrl . 'catalog/product/some_image_mobile.jpg';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getProductIdeasMobile method
     */
    public function testGetIdeasImageMobileWithNoImage(): void
    {
        $this->storeManagerMock->expects($this->never())
            ->method('getStore');

        $this->storeMock->expects($this->never())
            ->method('getBaseUrl');

        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())
            ->method('getData')
            ->with('product_ideas_image_mobile')
            ->willReturn(null);

        $this->assertEquals('', $this->aboutUsBlock->getIdeasImageMobile($productMock));
    }

}
