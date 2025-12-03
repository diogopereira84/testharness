<?php
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Block\Adminhtml;

use Fedex\Catalog\Block\Adminhtml\Product;
use Magento\Backend\Block\Widget\Context;
use Magento\Catalog\Model\Product\TypeFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection as AttributeSetCollection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
class ProductTest extends TestCase
{
    private Product|MockObject $block;
    private Context|MockObject $contextMock;
    private TypeFactory|MockObject $typeFactoryMock;
    private ProductFactory|MockObject $productFactoryMock;
    private AttributeSet|MockObject $attributeSetMock;
    private ToggleConfig|MockObject $toggleConfigMock;
    private RequestInterface|MockObject $requestMock;
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->typeFactoryMock = $this->createMock(TypeFactory::class);
        $this->productFactoryMock = $this->createMock(ProductFactory::class);
        $this->attributeSetMock = $this->createMock(AttributeSet::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->block = $this->getMockBuilder(Product::class)
            ->setConstructorArgs([
                $this->contextMock,
                $this->typeFactoryMock,
                $this->productFactoryMock,
                $this->attributeSetMock,
                $this->toggleConfigMock,
                $this->requestMock,
                []
            ])
            ->onlyMethods(['getUrl'])
            ->getMock();
    }


    private function callProtectedMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    public function testGetProductCreateUrlWithCommercialType(): void
    {
        $productMock = $this->createMock(CatalogProduct::class);
        $productMock->method('getDefaultAttributeSetId')->willReturn(10);

        $resourceMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock->method('getTypeId')->willReturn(4);

        $productMock->method('getResource')->willReturn($resourceMock);
        $this->productFactoryMock->method('create')->willReturn($productMock);

        $attributeSetCollectionMock = $this->createMock(AttributeSetCollection::class);
        $attributeSetCollectionMock->method('setEntityTypeFilter')->willReturnSelf();
        $attributeSetCollectionMock->method('addFieldToFilter')->willReturnSelf();

        $attributeSetItemMock = $this->createMock(AttributeSet::class);
        $attributeSetItemMock->method('getId')->willReturn(null);

        $attributeSetCollectionMock->method('getFirstItem')->willReturn($attributeSetItemMock);
        $this->attributeSetMock->method('getCollection')->willReturn($attributeSetCollectionMock);

        $this->block->expects($this->once())
            ->method('getUrl')
            ->with('catalog/*/new', ['set' => 10, 'type' => 'commercial'])
            ->willReturn('http://example.com/catalog/new');

        $url = $this->callProtectedMethod($this->block, '_getProductCreateUrl', ['commercial']);
        $this->assertEquals('http://example.com/catalog/new', $url);
    }


    public function testGetProductCreateUrlWithDefaultType(): void
    {
        $productMock = $this->createMock(CatalogProduct::class);
        $productMock->method('getDefaultAttributeSetId')->willReturn(20);
        $this->productFactoryMock->method('create')->willReturn($productMock);

        $this->block->method('getUrl')->willReturn('http://example.com/catalog/default');

        $url = $this->callProtectedMethod($this->block, '_getProductCreateUrl', ['simple']);
        $this->assertIsString($url);
    }

    public function testGetProductType(): void
    {
        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('type')
            ->willReturn('commercial');

        $result = $this->block->getProductType();

        $this->assertSame('commercial', $result);
    }
    public function testGetAttributeSetIdByNameReturnsId(): void
    {
        $expectedAttributeSetId = 42;

        $productMock = $this->createMock(CatalogProduct::class);
        $resourceMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product::class);
        $resourceMock->method('getTypeId')->willReturn(4);
        $productMock->method('getResource')->willReturn($resourceMock);
        $this->productFactoryMock->method('create')->willReturn($productMock);
        $attributeSetCollectionMock = $this->createMock(AttributeSetCollection::class);
        $attributeSetCollectionMock->method('setEntityTypeFilter')->willReturnSelf();
        $attributeSetCollectionMock->method('addFieldToFilter')->willReturnSelf();

        $attributeSetItemMock = $this->createMock(AttributeSet::class);
        $attributeSetItemMock->method('getId')->willReturn($expectedAttributeSetId);

        $attributeSetCollectionMock->method('getFirstItem')->willReturn($attributeSetItemMock);
        $this->attributeSetMock->method('getCollection')->willReturn($attributeSetCollectionMock);
        $result = $this->callProtectedMethod($this->block, 'getAttributeSetIdByName', ['printondemand']);

        $this->assertEquals($expectedAttributeSetId, $result);
    }

    public function testGetProductCreateUrlWithCommercialTypeAndToggleEnabled(): void
    {
        $this->toggleConfigMock
            ->method('getToggleConfigValue')
            ->with('tech_titans_e_484727')
            ->willReturn(true);

        $productMock = $this->createMock(CatalogProduct::class);
        $productMock->method('getDefaultAttributeSetId')->willReturn(99);

        $resourceMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product::class);
        $resourceMock->method('getTypeId')->willReturn(4);
        $productMock->method('getResource')->willReturn($resourceMock);

        $this->productFactoryMock->method('create')->willReturn($productMock);

        $attributeSetCollectionMock = $this->createMock(AttributeSetCollection::class);
        $attributeSetCollectionMock->method('setEntityTypeFilter')->willReturnSelf();
        $attributeSetCollectionMock->method('addFieldToFilter')->willReturnSelf();

        $attributeSetItemMock = $this->createMock(AttributeSet::class);
        $attributeSetItemMock->method('getId')->willReturn(null); // Simulate not found

        $attributeSetCollectionMock->method('getFirstItem')->willReturn($attributeSetItemMock);
        $this->attributeSetMock->method('getCollection')->willReturn($attributeSetCollectionMock);

        $this->block->expects($this->once())
            ->method('getUrl')
            ->with('catalog/*/new', ['set' => 99, 'type' => 'commercial'])
            ->willReturn('http://example.com/catalog/new');

        $url = $this->callProtectedMethod($this->block, '_getProductCreateUrl', ['commercial']);
        $this->assertEquals('http://example.com/catalog/new', $url);
    }

}
