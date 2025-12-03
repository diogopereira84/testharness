<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Fedex\CatalogMvp\Ui\DataProvider\Product\Form\Modifier\PublishedToggle;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class PublishedToggleTest extends TestCase
{
    private LocatorInterface $locatorMock;
    private AttributeSetRepositoryInterface $attributeSetRepositoryMock;
    private PublishedToggle $publishedToggle;
    private ToggleConfig $toggleConfig;

    private Product $productMock;
    private AttributeSetInterface $attributeSetMock;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->locatorMock = $this->createMock(LocatorInterface::class);
        $this->attributeSetRepositoryMock = $this->createMock(AttributeSetRepositoryInterface::class);
        $this->productMock = $this->createMock(Product::class);
        $this->attributeSetMock = $this->createMock(AttributeSetInterface::class);

        $this->publishedToggle = new PublishedToggle(
            $this->locatorMock,
            $this->attributeSetRepositoryMock,
            $this->toggleConfig
        );
    }

    public function testModifyDataSetsPublishedWhenPrintOnDemand(): void
    {
        $productId = 123;
        $attributeSetName = 'PrintOnDemand';
        $this->toggleConfig->method('getToggleConfigValue')
        ->with('tech_titans_e_484727')
        ->willReturn(true);

        $this->productMock->method('getId')->willReturn($productId);
        $this->productMock->method('getAttributeSetId')->willReturn(10);
        $this->locatorMock->method('getProduct')->willReturn($this->productMock);
        $this->attributeSetRepositoryMock->method('get')->with(10)->willReturn($this->attributeSetMock);
        $this->attributeSetMock->method('getAttributeSetName')->willReturn($attributeSetName);

        $data = [];

        $result = $this->publishedToggle->modifyData($data);

        $this->assertArrayHasKey($productId, $result);
        $this->assertArrayHasKey('product', $result[$productId]);
        $this->assertEquals(1, $result[$productId]['product']['published']);
    }

    public function testModifyDataDoesNotSetPublishedWhenNotPrintOnDemand(): void
    {
        $productId = 123;
        $attributeSetName = 'OtherSet';

        $this->productMock->method('getId')->willReturn($productId);
        $this->productMock->method('getAttributeSetId')->willReturn(10);
        $this->locatorMock->method('getProduct')->willReturn($this->productMock);
        $this->attributeSetRepositoryMock->method('get')->with(10)->willReturn($this->attributeSetMock);
        $this->attributeSetMock->method('getAttributeSetName')->willReturn($attributeSetName);

        $data = [];

        $result = $this->publishedToggle->modifyData($data);

        $this->assertArrayNotHasKey('published', isset($result[$productId]['product']) ? $result[$productId]['product'] : []);
    }

    public function testModifyMetaIncludesDefaultWhenPrintOnDemand(): void
    {
        $attributeSetName = 'PrintOnDemand';

        $this->setupAttributeSetName($attributeSetName);

        // Mock toggleConfig to return true to enable feature
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('tech_titans_e_484727')
            ->willReturn(true);

        $meta = [
            'product-details' => [
                'children' => [
                    'published' => [
                        'arguments' => [
                            'data' => [
                                'config' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->publishedToggle->modifyMeta($meta);

        $publishedConfig = $result['product-details']['children']['published']['arguments']['data']['config'];

        $this->assertArrayHasKey('label', $publishedConfig);
        $this->assertEquals(__('Published'), $publishedConfig['label']);
        $this->assertEquals(Field::NAME, $publishedConfig['componentType']);
        $this->assertEquals(Checkbox::NAME, $publishedConfig['formElement']);
        $this->assertEquals(Boolean::NAME, $publishedConfig['dataType']);
        $this->assertEquals(61, $publishedConfig['sortOrder']);
        $this->assertEquals(1, $publishedConfig['default']);
    }



    public function testModifyMetaDoesNotIncludeDefaultWhenNotPrintOnDemand(): void
    {
        $attributeSetName = 'OtherSet';

        $this->setupAttributeSetName($attributeSetName);

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('tech_titans_e_484727')
            ->willReturn(true);  // or false depending on your logic

        $meta = [
            'product-details' => [
                'children' => [
                    'published' => [
                        'arguments' => [
                            'data' => [
                                'config' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->publishedToggle->modifyMeta($meta);

        $publishedConfig = $result['product-details']['children']['published']['arguments']['data']['config'];

        $this->assertArrayNotHasKey('default', $publishedConfig);
    }


    public function testGetAttributeSetNameReturnsCachedValue(): void
    {
        $attributeSetName = 'PrintOnDemand';

        $this->setupAttributeSetName($attributeSetName);

        // First call to populate cache
        $this->publishedToggle->modifyData([]);

        // Second call should return cached value without calling repository again
        $name = $this->invokeGetAttributeSetName();

        $this->assertEquals($attributeSetName, $name);
    }

    public function testGetAttributeSetNameReturnsNullOnException(): void
    {
        $this->productMock->method('getAttributeSetId')->willReturn(10);
        $this->locatorMock->method('getProduct')->willReturn($this->productMock);
        $this->attributeSetRepositoryMock->method('get')->willThrowException(new \Exception('error'));

        $name = $this->invokeGetAttributeSetName();

        $this->assertNull($name);
    }

    private function setupAttributeSetName(string $name): void
    {
        $this->productMock->method('getAttributeSetId')->willReturn(10);
        $this->locatorMock->method('getProduct')->willReturn($this->productMock);
        $this->attributeSetRepositoryMock->method('get')->with(10)->willReturn($this->attributeSetMock);
        $this->attributeSetMock->method('getAttributeSetName')->willReturn($name);
    }

    private function invokeGetAttributeSetName(): ?string
    {
        $reflection = new \ReflectionClass($this->publishedToggle);
        $method = $reflection->getMethod('getAttributeSetName');
        $method->setAccessible(true);

        return $method->invoke($this->publishedToggle);
    }
}
