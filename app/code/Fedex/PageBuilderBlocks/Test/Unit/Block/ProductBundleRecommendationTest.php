<?php

declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Test\Unit\Block;

use Fedex\PageBuilderBlocks\Block\ProductBundleRecommendation;
use Fedex\ProductEngine\Model\Catalog\Bundle\Products as ProductsBundle;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductBundleRecommendationTest extends TestCase
{
    private ProductBundleRecommendation $block;

    private MockObject $contextMock;
    private MockObject $productRepositoryMock;
    private MockObject $pricingHelperMock;
    private MockObject $imageHelperMock;
    private MockObject $searchCriteriaBuilderMock;
    private MockObject $urlBuilderMock;
    private MockObject $productsBundleMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->urlBuilderMock->method('getUrl')->willReturn('http://example.com/test-url');
        $this->contextMock->method('getUrlBuilder')->willReturn($this->urlBuilderMock);

        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->pricingHelperMock = $this->createMock(PricingHelper::class);
        $this->imageHelperMock = $this->createMock(ImageHelper::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->productsBundleMock = $this->createMock(ProductsBundle::class);

        $this->block = new ProductBundleRecommendation(
            $this->contextMock,
            $this->productRepositoryMock,
            $this->pricingHelperMock,
            $this->imageHelperMock,
            $this->productsBundleMock,
            ['data' => [
                'title' => ['Test Title'],
                'sku' => ['test-sku'],
                'price' => ['100'],
                'message' => ['Buy Now'],
                'cta-label' => ['Click Here']
            ]]
        );
    }

    public function testGetTitle(): void
    {
        $this->assertEquals('Test Title', $this->block->getTitle());
    }

    public function testGetSku(): void
    {
        $this->assertEquals('test-sku', $this->block->getSku());
    }

    public function testGetPrice(): void
    {
        $this->assertEquals('100', $this->block->getPrice());
    }

    public function testGetMessage(): void
    {
        $this->assertEquals('Buy Now', $this->block->getMessage());
    }

    public function testGetCtaLabel(): void
    {
        $this->assertEquals('Click Here', $this->block->getCtaLabel());
    }

    public function testHasContentReturnsTrue(): void
    {
        $bundleProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getId', 'getTypeInstance'])
            ->getMock();

        $bundleProduct->method('getTypeId')->willReturn('bundle');
        $bundleProduct->method('getId')->willReturn(1);

        $bundleTypeMock = $this->createMock(\Magento\Bundle\Model\Product\Type::class);
        $bundleTypeMock->method('getChildrenIds')->willReturn([]);

        $bundleProduct->method('getTypeInstance')->willReturn($bundleTypeMock);

        $this->productRepositoryMock
            ->method('get')
            ->with('test-sku')
            ->willReturn($bundleProduct);

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteriaMock);

        $searchResultsMock = $this->createMock(SearchResultsInterface::class);
        $searchResultsMock->method('getItems')->willReturn([]);
        $this->productRepositoryMock->method('getList')->willReturn($searchResultsMock);

        $this->assertTrue($this->block->hasContent());
    }

    public function testGetProductsReturnsFormattedBundleProducts(): void
    {
        $sku = 'bundle-sku';
        $this->block->setData('data', ['sku' => [$sku]]);

        // Mock bundle product
        $bundleProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getId', 'getTypeInstance'])
            ->getMock();

        $bundleProduct->method('getTypeId')->willReturn('bundle');
        $bundleProduct->method('getId')->willReturn(1);

        // Bundle type with children
        $bundleTypeMock = $this->createMock(\Magento\Bundle\Model\Product\Type::class);
        $bundleTypeMock->method('getChildrenIds')->willReturn([2 => [3, 4]]);
        $bundleProduct->method('getTypeInstance')->willReturn($bundleTypeMock);

        $this->productRepositoryMock
            ->method('get')
            ->with($sku)
            ->willReturn($bundleProduct);

        // Create Product mocks with onlyMethods() for existing methods
        $product1 = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getProductUrl', 'getImage', 'getFinalPrice'])
            ->getMock();

        $product1->method('getName')->willReturn('Product 1');
        $product1->method('getProductUrl')->willReturn('http://example.com/product1');
        $product1->method('getImage')->willReturn('product1.jpg');
        $product1->method('getFinalPrice')->willReturn(5);

        $product2 = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'getProductUrl', 'getImage', 'getFinalPrice'])
            ->getMock();
        $product2->method('getName')->willReturn('Product 2');
        $product2->method('getProductUrl')->willReturn('http://example.com/product2');
        $product2->method('getImage')->willReturn('product2.jpg');
        $product2->method('getFinalPrice')->willReturn(10);

        $this->productsBundleMock->expects($this->once())
            ->method('getBundleChildProducts')
            ->with($bundleProduct)
            ->willReturn([$product1, $product2]);

        // Image helper mock
        $imageMock = $this->createMock(\Magento\Catalog\Helper\Image::class);
        $imageMock->method('getUrl')->willReturn('http://image.url');
        $this->imageHelperMock->method('init')->willReturn($imageMock);

        // Run
        $products = $this->block->getProducts();

        // Assert
        $this->assertCount(2, $products);
        $this->assertEquals('Product 1', $products[0]['name']);
        $this->assertEquals('http://example.com/product1', $products[0]['url']);
        $this->assertEquals('http://image.url', $products[0]['image_url']);
    }
}
