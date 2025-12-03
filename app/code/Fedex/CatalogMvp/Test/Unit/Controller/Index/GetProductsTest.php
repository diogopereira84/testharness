<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Controller\Index;

use Fedex\CatalogMvp\Controller\Index\GetProducts;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\CategoryRepository;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GetProductsTest extends TestCase
{
    private GetProducts $controller;
    private MockObject $contextMock;
    private MockObject $productCollectionFactoryMock;
    private MockObject $categoryFactoryMock;
    private MockObject $imageHelperMock;
    private MockObject $resultFactoryMock;
    private MockObject $rawResultMock;
    private MockObject $scopeConfigMock;
    private MockObject $categoryRepositoryMock;
    private MockObject $toggleConfigMock;
    private MockObject $requestMock;
    private MockObject $catalogMvpHelperMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->categoryFactoryMock = $this->createMock(CategoryFactory::class);
        $this->imageHelperMock = $this->createMock(Image::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->rawResultMock = $this->createMock(Raw::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->categoryRepositoryMock = $this->createMock(CategoryRepository::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->catalogMvpHelperMock = $this->createMock(CatalogMvp::class);

        $this->controller = new GetProducts(
            $this->contextMock,
            $this->productCollectionFactoryMock,
            $this->categoryFactoryMock,
            $this->imageHelperMock,
            $this->resultFactoryMock,
            $this->scopeConfigMock,
            $this->categoryRepositoryMock,
            $this->toggleConfigMock,
            $this->requestMock,
            $this->catalogMvpHelperMock
        );
    }

    public function testExecuteReturnsHtmlWithProductName()
    {
        $categoryId = 10;
        $subcategoryIds = '10,11,12';
        $attributeSetId = 99;
        $productName = 'Test Product';
        $productId = 123;
        $sku = 'SKU123';
        $imageUrl = 'http://example.com/image.jpg';

        // Request param
        $this->requestMock->method('getParam')->with('id')->willReturn($categoryId);

        // Category repository and factory
        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getAllChildren')->with(false)->willReturn($subcategoryIds);
        $this->categoryRepositoryMock->method('get')->with($categoryId)->willReturn($categoryMock);
        $this->categoryFactoryMock->method('create')->willReturn($categoryMock);
        $categoryMock->method('load')->with([$categoryId])->willReturn($categoryMock);

        // Scope config
        $this->scopeConfigMock->method('getValue')->willReturn('');

        // Product collection
        $productMock = $this->createMock(Product::class);
        $productMock->method('getName')->willReturn($productName);
        $productMock->method('getId')->willReturn($productId);        $productMock->method('getSku')->willReturn($sku);
        $productMock->method('getData')->with('small_image')->willReturn('small_image.jpg');
        $productCollectionMock = $this->createMock(ProductCollection::class);
        $productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $productCollectionMock->method('addCategoriesFilter')->willReturnSelf();
        $productCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $productCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$productMock]));

        $this->productCollectionFactoryMock->method('create')->willReturn($productCollectionMock);

        // Attribute set
        $this->catalogMvpHelperMock->method('getAttrSetIdByName')->with('FXOPrintProducts')->willReturn($attributeSetId);

        // Toggle config
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);

        // Image helper
        $this->imageHelperMock->method('init')->willReturnSelf();
        $this->imageHelperMock->method('setImageFile')->willReturnSelf();
        $this->imageHelperMock->method('keepFrame')->willReturnSelf();
        $this->imageHelperMock->method('resize')->willReturnSelf();
        $this->imageHelperMock->method('getUrl')->willReturn($imageUrl);

        // Result factory
        $this->resultFactoryMock->method('create')->with(ResultFactory::TYPE_RAW)->willReturn($this->rawResultMock);

        // Raw result
        $this->rawResultMock->expects($this->once())->method('setContents')
            ->with($this->callback(function ($html) use ($productName) {
                return str_contains($html, $productName);
            }))
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertInstanceOf(Raw::class, $result);
    }
}
