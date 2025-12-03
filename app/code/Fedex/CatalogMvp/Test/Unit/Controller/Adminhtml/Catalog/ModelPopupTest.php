<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Controller\Adminhtml\Catalog;

use Fedex\CatalogMvp\Controller\Adminhtml\Catalog\ModelPopup;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModelPopupTest extends TestCase
{
    private ModelPopup $controller;
    private MockObject $contextMock;
    private MockObject $productCollectionFactoryMock;
    private MockObject $categoryFactoryMock;
    private MockObject $categoryRepositoryMock;
    private MockObject $imageHelperMock;
    private MockObject $resultFactoryMock;
    private MockObject $rawResultMock;
    private MockObject $toggleConfigMock;
    private MockObject $catalogMvpHelperMock;
    private MockObject $dyesubConfigProviderMock;
    private MockObject $requestMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->productCollectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->categoryFactoryMock = $this->createMock(CategoryFactory::class);
        $this->categoryRepositoryMock = $this->createMock(CategoryRepository::class);
        $this->imageHelperMock = $this->createMock(Image::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->rawResultMock = $this->createMock(Raw::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->catalogMvpHelperMock = $this->createMock(CatalogMvp::class);
        $this->dyesubConfigProviderMock = $this->createMock(ConfigProvider::class);

        // Mock request
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMock();

        $this->contextMock->method('getRequest')->willReturn($this->requestMock);

        $this->controller = new ModelPopup(
            $this->contextMock,
            $this->productCollectionFactoryMock,
            $this->categoryFactoryMock,
            $this->categoryRepositoryMock,
            $this->imageHelperMock,
            $this->resultFactoryMock,
            $this->rawResultMock,
            $this->toggleConfigMock,
            $this->catalogMvpHelperMock,
            $this->dyesubConfigProviderMock
        );

        // Use reflection to set protected property
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('resultFactory');
        $property->setAccessible(true);
        $property->setValue($this->controller, $this->resultFactoryMock);
    }

    public function testExecuteReturnsRawHtmlWithProductName()
    {
        $categoryId = 10;
        $subcategoryIds = '10,11,12';
        $attributeSetId = 99;
        $productName = 'Test Product';
        $productId = 123;
        $sku = 'SKU123';
        $imageUrl = 'http://example.com/image.jpg';

        // Mock request param
        $this->requestMock->method('getParam')->with('id')->willReturn($categoryId);

        // Mock category and children
        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getAllChildren')->with(false)->willReturn($subcategoryIds);
        $this->categoryRepositoryMock->method('get')->with($categoryId)->willReturn($categoryMock);

        // Mock product collection
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->method('getName')->willReturn($productName);
        $productMock->method('getId')->willReturn($productId);
        $productMock->method('getSku')->willReturn($sku);
        $productMock->method('getData')->with('small_image')->willReturn('small_image.jpg');
        $productCollectionMock = $this->createMock(ProductCollection::class);
        $productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $productCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $productCollectionMock->method('addCategoriesFilter')->willReturnSelf();
        $productCollectionMock->method('addFieldToFilter')->willReturnSelf();
        $productCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$productMock]));

        $this->productCollectionFactoryMock->method('create')->willReturn($productCollectionMock);

        // Toggle config
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);

        // Attribute set
        $this->catalogMvpHelperMock->method('getAttrSetIdByName')->with('FXOPrintProducts')->willReturn($attributeSetId);

        // DyeSub config
        $this->dyesubConfigProviderMock->method('isDyeSubEnabled')->willReturn(false);

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
