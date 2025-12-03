<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\ProductBundle\Model\CartItemProcessor;
use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Bundle\Api\Data\BundleOptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductOptionExtensionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ProductOptionExtensionFactory;
use Magento\Quote\Api\Data\ProductOptionInterfaceFactory;
use Magento\Bundle\Api\Data\BundleOptionInterface;
use Magento\Quote\Api\Data\ProductOptionInterface;
use PHPUnit\Framework\TestCase;

class InvokerCartItemProcessor extends CartItemProcessor {
    public function __construct($objectFactory, $productOptionExtensionFactory, $bundleOptionFactory, $productOptionFactory, $config) {
        parent::__construct($objectFactory, $productOptionExtensionFactory, $bundleOptionFactory, $productOptionFactory, $config);
    }
    public function callParentConvertToBuyRequest($cartItem) {
        return parent::convertToBuyRequest($cartItem);
    }
}

class CartItemProcessorTest extends TestCase
{
    private $objectFactory;
    private $productOptionExtensionFactory;
    private $bundleOptionFactory;
    private $productOptionFactory;
    private $config;
    private $processor;
    private $invokerProcessor;

    protected function setUp(): void
    {
        $this->objectFactory = $this->createMock(DataObjectFactory::class);
        $this->productOptionExtensionFactory = $this->createMock(ProductOptionExtensionFactory::class);
        $this->bundleOptionFactory = $this->createMock(BundleOptionInterfaceFactory::class);
        $this->productOptionFactory = $this->createMock(ProductOptionInterfaceFactory::class);
        $this->config = $this->createMock(ConfigInterface::class);

        $this->processor = new CartItemProcessor(
            $this->objectFactory,
            $this->productOptionExtensionFactory,
            $this->bundleOptionFactory,
            $this->productOptionFactory,
            $this->config
        );
        $this->invokerProcessor = new InvokerCartItemProcessor(
            $this->objectFactory,
            $this->productOptionExtensionFactory,
            $this->bundleOptionFactory,
            $this->productOptionFactory,
            $this->config
        );
    }

    public function testConvertToBuyRequestFeatureToggleDisabledCallsParent()
    {
        $cartItem = $this->createMock(CartItemInterface::class);
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $result = $this->invokerProcessor->convertToBuyRequest($cartItem);
        $expected = $this->invokerProcessor->callParentConvertToBuyRequest($cartItem);
        $this->assertSame($expected, $result);
    }

    public function testConvertToBuyRequestCartItemHasBuyRequestReturnsNull()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $infoBuyRequest = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getValue'])
            ->getMock();
        $infoBuyRequest->method('getValue')->willReturn('value');
        $cartItem->method('getOptionByCode')->willReturn($infoBuyRequest);
        $result = $this->processor->convertToBuyRequest($cartItem);
        $this->assertNull($result);
    }

    public function testConvertToBuyRequestNoProductOptionReturnsNull()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductOption'])
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $cartItem->method('getOptionByCode')->willReturn(null);
        $cartItem->method('getProductOption')->willReturn(null);
        $result = $this->processor->convertToBuyRequest($cartItem);
        $this->assertNull($result);
    }

    public function testConvertToBuyRequestNoExtensionAttributesReturnsNull()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductOption'])
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $cartItem->method('getOptionByCode')->willReturn(null);
        $productOption = $this->createMock(ProductOptionInterface::class);
        $productOption->method('getExtensionAttributes')->willReturn(null);
        $cartItem->method('getProductOption')->willReturn($productOption);
        $result = $this->processor->convertToBuyRequest($cartItem);
        $this->assertNull($result);
    }

    public function testConvertToBuyRequestBundleOptionsNotArrayReturnsNull()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductOption'])
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $cartItem->method('getOptionByCode')->willReturn(null);
        $extensionAttributes = $this->createMock(ProductOptionExtensionInterface::class);
        $extensionAttributes->method('getBundleOptions')->willReturn(null);
        $productOption = $this->createMock(ProductOptionInterface::class);
        $productOption->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $cartItem->method('getProductOption')->willReturn($productOption);
        $result = $this->processor->convertToBuyRequest($cartItem);
        $this->assertNull($result);
    }

    public function testConvertToBuyRequestBuildsRequestDataAndCallsObjectFactory()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductOption'])
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $cartItem->method('getOptionByCode')->willReturn(null);

        $selection = 'selection1';
        $optionId = 42;
        $optionQty = 3;
        $bundleOption = $this->createMock(BundleOptionInterface::class);
        $bundleOption->method('getOptionSelections')->willReturn([$selection]);
        $bundleOption->method('getOptionId')->willReturn($optionId);
        $bundleOption->method('getOptionQty')->willReturn($optionQty);
        $extensionAttributes = $this->createMock(ProductOptionExtensionInterface::class);
        $extensionAttributes->method('getBundleOptions')->willReturn([$bundleOption]);
        $productOption = $this->createMock(ProductOptionInterface::class);
        $productOption->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $cartItem->method('getProductOption')->willReturn($productOption);

        $expectedRequestData = [
            'bundle_option' => [
                $optionId => [$selection]
            ],
            'bundle_option_qty' => [
                $optionId => $optionQty
            ]
        ];
        $createdObject = $this->createMock(DataObject::class);
        $this->objectFactory->expects($this->once())
            ->method('create')
            ->with($expectedRequestData)
            ->willReturn($createdObject);

        $result = $this->processor->convertToBuyRequest($cartItem);
        $this->assertSame($createdObject, $result);
    }

    public function testCartItemHasBuyRequestReturnsTrue()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $option = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $option->method('getValue')->willReturn('value');
        $cartItem->method('getOptionByCode')->willReturn($option);
        $reflection = new \ReflectionClass(CartItemProcessor::class);
        $method = $reflection->getMethod('cartItemHasBuyRequest');
        $method->setAccessible(true);
        $result = $method->invoke($this->processor, $cartItem);
        $this->assertTrue($result);
    }

    public function testCartItemHasBuyRequestReturnsFalseIfNoOption()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $cartItem->method('getOptionByCode')->willReturn(null);
        $reflection = new \ReflectionClass(CartItemProcessor::class);
        $method = $reflection->getMethod('cartItemHasBuyRequest');
        $method->setAccessible(true);
        $result = $method->invoke($this->processor, $cartItem);
        $this->assertFalse($result);
    }

    public function testCartItemHasBuyRequestReturnsFalseIfValueIsNull()
    {
        $cartItem = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOptionByCode'])
            ->getMockForAbstractClass();
        $option = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $option->method('getValue')->willReturn(null);
        $cartItem->method('getOptionByCode')->willReturn($option);
        $reflection = new \ReflectionClass(CartItemProcessor::class);
        $method = $reflection->getMethod('cartItemHasBuyRequest');
        $method->setAccessible(true);
        $result = $method->invoke($this->processor, $cartItem);
        $this->assertFalse($result);
    }
}
