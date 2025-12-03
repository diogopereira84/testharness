<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Block\Product\Attributes\Test;

use Fedex\ProductEngine\Block\Product\Attributes\Bundle;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Customer\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Fedex\ProductEngine\Model\Config\Backend as PeBackendConfig;
use Fedex\ProductCustomAtrribute\Model\Config\Backend as CanvaBackendConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\ProductEngine\Model\Catalog\Bundle\Products as ProductsBundle;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use PHPUnit\Framework\MockObject\MockObject;

class BundleTest extends TestCase
{
    private Bundle $bundle;
    private MockObject $productsBundle;
    private MockObject $product;
    private array $constructorArgs;

    protected function setUp(): void
    {
        $this->productsBundle = $this->createMock(ProductsBundle::class);
        $this->product = $this->createMock(ProductInterface::class);

        $this->constructorArgs = [
            $this->createMock(Context::class),
            $this->createMock(EncoderInterface::class),
            $this->createMock(JsonEncoderInterface::class),
            $this->createMock(StringUtils::class),
            $this->createMock(Product::class),
            $this->createMock(ConfigInterface::class),
            $this->createMock(FormatInterface::class),
            $this->createMock(Session::class),
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(PriceCurrencyInterface::class),
            $this->createMock(Attribute::class),
            $this->createMock(ProductAttributeRepositoryInterface::class),
            $this->createMock(PeBackendConfig::class),
            $this->createMock(CanvaBackendConfig::class),
            $this->createMock(SearchCriteriaBuilder::class),
            $this->createMock(SortOrderBuilder::class),
            $this->createMock(ToggleConfig::class),
            $this->productsBundle,
            []
        ];

        $this->bundle = $this->getMockBuilder(Bundle::class)
            ->setConstructorArgs($this->constructorArgs)
            ->onlyMethods(['getProduct', 'getAttributes'])
            ->getMock();
    }

    public function testGetVisibleAttributesReturnsEmptyIfNotBundleType(): void
    {
        $this->bundle->method('getProduct')->willReturn($this->product);
        $this->product->method('getTypeId')->willReturn('simple');
        $result = $this->bundle->getVisibleAttributes();
        $this->assertSame([], $result);
    }

    public function testGetVisibleAttributesSkipsDisabledChildProducts(): void
    {
        $this->bundle->method('getProduct')->willReturn($this->product);
        $this->product->method('getTypeId')->willReturn('bundle');
        $childProduct = $this->createMock(ProductInterface::class);
        $childProduct->method('getStatus')->willReturn(Status::STATUS_DISABLED);
        $this->productsBundle->method('getBundleChildProducts')->willReturn([$childProduct]);
        $result = $this->bundle->getVisibleAttributes();
        $this->assertSame([], $result);
    }

    public function testGetVisibleAttributesHandlesNoVisibleAttributes(): void
    {
        $this->bundle->method('getProduct')->willReturn($this->product);
        $this->product->method('getTypeId')->willReturn('bundle');
        $childProduct = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatus'])
            ->addMethods(['getPeProductId', 'getData'])
            ->getMockForAbstractClass();
        $childProduct->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $childProduct->method('getPeProductId')->willReturn('pid1');
        $childProduct->method('getData')->willReturn('');
        $this->productsBundle->method('getBundleChildProducts')->willReturn([$childProduct]);
        $attributesCollection = $this->getMockBuilder('Magento\Framework\Data\Collection')->disableOriginalConstructor()->getMock();
        $attributesCollection->method('getItems')->willReturn([]);
        $this->bundle->method('getAttributes')->willReturn($attributesCollection);
        $result = $this->bundle->getVisibleAttributes();
        $this->assertSame(['pid1' => []], $result);
    }

    public function testGetVisibleAttributesHandlesAttributeOptions(): void
    {
        $this->bundle->method('getProduct')->willReturn($this->product);
        $this->product->method('getTypeId')->willReturn('bundle');
        $childProduct = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatus'])
            ->addMethods(['getPeProductId', 'getData'])
            ->getMockForAbstractClass();
        $childProduct->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $childProduct->method('getPeProductId')->willReturn('pid2');
        $childProduct->method('getData')->willReturnMap([
            ['visible_attributes', 'attr1,attr2'],
            ['attr1', 'opt1'],
            ['attr2', null]
        ]);
        $this->productsBundle->method('getBundleChildProducts')->willReturn([$childProduct]);
        $attribute1 = $this->createMock(Attribute::class);
        $attribute1->method('getAttributeCode')->willReturn('attr1');
        $attribute1->method('getSource')->willReturn($this->createMock(Table::class));
        $attribute2 = $this->createMock(Attribute::class);
        $attribute2->method('getAttributeCode')->willReturn('attr2');
        $attribute2->method('getSource')->willReturn($this->createMock(Table::class));
        $attributesCollection = $this->getMockBuilder('Magento\Framework\Data\Collection')->disableOriginalConstructor()->getMock();
        $attributesCollection->method('getItems')->willReturn([$attribute1, $attribute2]);
        $this->bundle->method('getAttributes')->willReturn($attributesCollection);
        $attributeSource = $attribute1->getSource();
        $attributeSource->method('getSpecificOptions')->willReturn([
            ['choice_id' => 'cid1'],
            ['choice_id' => 'cid2']
        ]);
        $result = $this->bundle->getVisibleAttributes();
        $this->assertSame(['pid2' => ['cid1', 'cid2']], $result);
    }

    public function testGetVisibleAttributesSkipsNonTableSource(): void
    {
        $this->bundle->method('getProduct')->willReturn($this->product);
        $this->product->method('getTypeId')->willReturn('bundle');
        $childProduct = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatus'])
            ->addMethods(['getPeProductId', 'getData'])
            ->getMockForAbstractClass();
        $childProduct->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $childProduct->method('getPeProductId')->willReturn('pid3');
        $childProduct->method('getData')->willReturnMap([
            ['visible_attributes', 'attr1'],
            ['attr1', 'opt1']
        ]);
        $this->productsBundle->method('getBundleChildProducts')->willReturn([$childProduct]);
        $attribute1 = $this->createMock(Attribute::class);
        $attribute1->method('getAttributeCode')->willReturn('attr1');
        $attribute1->method('getSource')->willReturn($this->createMock('stdClass'));
        $attributesCollection = $this->getMockBuilder('Magento\Framework\Data\Collection')->disableOriginalConstructor()->getMock();
        $attributesCollection->method('getItems')->willReturn([$attribute1]);
        $this->bundle->method('getAttributes')->willReturn($attributesCollection);
        $result = $this->bundle->getVisibleAttributes();
        $this->assertSame(['pid3' => []], $result);
    }

    public function testGetIdsSkusReturnsCorrectSkus(): void
    {
        $this->bundle->method('getProduct')->willReturn($this->product);
        $childProduct1 = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatus'])
            ->addMethods(['getPeProductId', 'getData'])
            ->getMockForAbstractClass();
        $childProduct1->method('getStatus')->willReturn(Status::STATUS_ENABLED);
        $childProduct1->method('getPeProductId')->willReturn('pid1');
        $childProduct1->method('getSku')->willReturn('sku1');
        $childProduct2 = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatus'])
            ->addMethods(['getPeProductId', 'getData'])
            ->getMockForAbstractClass();
        $childProduct2->method('getStatus')->willReturn(Status::STATUS_DISABLED);
        $childProduct2->method('getPeProductId')->willReturn('pid2');
        $childProduct2->method('getSku')->willReturn('sku2');
        $this->productsBundle->method('getBundleChildProducts')->willReturn([$childProduct1, $childProduct2]);
        $result = $this->bundle->getIdsSkus();
        $this->assertSame(['pid1' => 'sku1'], $result);
    }
}

