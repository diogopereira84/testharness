<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Plugin\Bundle;

use Fedex\ProductBundle\Plugin\Bundle\TypePlugin;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\ProductBundle\Service\BundleProductProcessor;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

class TypePluginTest extends TestCase
{
    private $config;
    private $bundleProductProcessor;
    private $request;
    private $plugin;
    private $type;
    private $product;
    private $buyRequest;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->bundleProductProcessor = $this->createMock(BundleProductProcessor::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->plugin = new TypePlugin($this->config, $this->bundleProductProcessor, $this->request);
        $this->type = $this->createMock(Type::class);
        $this->product = $this->createMock(Product::class);
        $this->buyRequest = $this->createMock(DataObject::class);
    }

    public function testReturnsResultIfToggleDisabled()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $this->product->method('getTypeId')->willReturn('simple');
        $result = ['foo'];
        $output = $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
        $this->assertSame($result, $output);
    }

    public function testReturnsResultIfProductTypeNotCode()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn('simple');
        $result = ['foo'];
        $output = $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
        $this->assertSame($result, $output);
    }

    public function testReturnsResultIfBundleIdHashOptionIsNull()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn(null);
        $result = ['foo'];
        $output = $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
        $this->assertSame($result, $output);
    }

    public function testReturnsResultIfResultIsNotArray()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn($customOption);
        $result = new Phrase('not_array');
        $output = $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
        $this->assertSame($result, $output);
    }

    public function testParentItemAndProductDataBySkuSetForTypeCode()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $customOption->method('getValue')->willReturn('productsData');
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn($customOption);
        $item = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $item->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $item->method('getCustomOption')
            ->withConsecutive(['info_buyRequest'], ['productsData'])
            ->willReturn($customOption);
        $this->bundleProductProcessor->method('mapProductsBySkuForQuoteApproval')->willReturn(['sku1' => 'data1']);
        $result = [$item];
        $output = $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
        $this->assertIsArray($output);
    }

    public function testAddsBundleIdHashOptionIfMissing()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $customOption->method('getValue')->willReturn('hash');
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn($customOption);
        $item = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $item->method('getTypeId')->willReturn('simple');
        $item->method('getCustomOption')
            ->withConsecutive(['bundle_instance_id_hash'], ['info_buyRequest'])->willReturn(null);
        $item->expects($this->once())
            ->method('addCustomOption')
            ->with('bundle_instance_id_hash', 'hash');
        $result = [$item];
        $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
    }

    public function testUpdatesQtyAndCustomOptionsForChildItems()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();
        $customOption->method('getValue')->willReturn('productsData');
        $this->product->method('getCustomOption')
            ->with('bundle_instance_id_hash')
            ->willReturn($customOption);
        $parentItem = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $parentItem->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $parentItem->method('getCustomOption')
            ->willReturnMap([
                ['info_buyRequest', $customOption],
                ['productsData', $customOption],
                ['bundle_instance_id_hash', $customOption],
                ['selection_qty_2', $customOption],
                ['product_qty_4', $customOption],
            ]);
        $parentItem->method('getSku')->willReturn('skuParent');
        $parentItem->method('getSelectionId')->willReturn(null);
        $parentItem->method('getOptionId')->willReturn(null);
        $parentItem->method('getId')->willReturn(1);
        $parentItem->method('addCustomOption')->willReturnSelf();
        $item = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption', 'setQty'])
            ->addMethods(['getOptionId', 'getSelectionId', 'setCartQty'])
            ->getMock();
        $item->method('getTypeId')->willReturn('simple');
        $item->method('getCustomOption')
            ->willReturnMap([
                ['bundle_instance_id_hash', $customOption],
                ['info_buyRequest', $customOption],
            ]);
        $item->method('getSelectionId')->willReturn(2);
        $item->method('getOptionId')->willReturn(3);
        $item->method('getId')->willReturn(4);
        $item->method('getSku')->willReturn('sku1');
        $item->expects($this->once())->method('setQty')->with(1.0);
        $item->expects($this->once())->method('setCartQty')->with(1.0);
        $parentItem->expects($this->any())->method('getCustomOption')->willReturn($customOption);
        $customOption->expects($this->exactly(2))->method('setValue')->with(1.0);
        $this->bundleProductProcessor->method('mapProductsBySkuForQuoteApproval')->willReturn(['sku1' => json_encode(['external_prod' => ['qty' => 1.0]])]);
        $result = [$parentItem, $item];
        $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
    }

    public function testAddsInfoBuyRequestCustomOptionForMatchingChild()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);
        $customOption->method('getValue')->willReturn('buyRequestValue');
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn($customOption);
        $parentItem = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $parentItem->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $parentItem->method('getCustomOption')
            ->willReturnMap([
                ['info_buyRequest', $customOption],
                ['productsData', $customOption],
                ['bundle_instance_id_hash', $customOption],
            ]);
        $parentItem->method('getSku')->willReturn('skuParent');
        $parentItem->method('getSelectionId')->willReturn(null);
        $parentItem->method('getOptionId')->willReturn(null);
        $parentItem->method('getId')->willReturn(1);
        $parentItem->method('addCustomOption')->willReturnSelf();
        $item = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $item->method('getTypeId')->willReturn('simple');
        $item->method('getCustomOption')
            ->willReturnMap([
                ['bundle_instance_id_hash', $customOption],
                ['info_buyRequest', $customOption],
            ]);
        $item->method('getSelectionId')->willReturn(2);
        $item->method('getOptionId')->willReturn(3);
        $item->method('getId')->willReturn(4);
        $item->method('getSku')->willReturn('sku1');
        $item->expects($this->once())->method('addCustomOption')->with('info_buyRequest', json_encode(['external_prod' => [['qty' => 1.0]]]));
        $this->bundleProductProcessor->method('mapProductsBySkuForQuoteApproval')->willReturn(['sku1' => json_encode(['external_prod' => [['qty' => 1.0]]])]);
        $result = [$parentItem, $item];
        $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
    }

    // --- getQtyFromProductsData() tests ---
    public function testGetQtyFromProductsDataReturnsOneIfEmpty()
    {
        $reflection = new \ReflectionMethod($this->plugin, 'getQtyFromProductsData');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->plugin, '');
        $this->assertSame(1.0, $result);
    }

    public function testGetQtyFromProductsDataReturnsOneIfDecodedEmpty()
    {
        $reflection = new \ReflectionMethod($this->plugin, 'getQtyFromProductsData');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->plugin, json_encode([]));
        $this->assertSame(1.0, $result);
    }

    public function testGetQtyFromProductsDataReturnsOneIfMissingExternalProd()
    {
        $reflection = new \ReflectionMethod($this->plugin, 'getQtyFromProductsData');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->plugin, json_encode(['foo' => 'bar']));
        $this->assertSame(1.0, $result);
    }

    public function testGetQtyFromProductsDataReturnsQtyIfPresent()
    {
        $reflection = new \ReflectionMethod($this->plugin, 'getQtyFromProductsData');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->plugin, json_encode(['external_prod' => [['qty' => 2.5]]]));
        $this->assertSame(2.5, $result);
    }

    public function testUpdateChildItemQtyWithProductsQtyDataUpdatesCustomOptionsAndQty()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();
        $customOption->method('getValue')->willReturn('productsData');
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn($customOption);
        $parentItem = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $parentItem->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $parentItem->method('getCustomOption')
            ->willReturnMap([
                ['selection_qty_2', $customOption],
                ['product_qty_4', $customOption],
            ]);
        $parentItem->method('getSku')->willReturn('skuParent');
        $parentItem->method('getSelectionId')->willReturn(null);
        $parentItem->method('getOptionId')->willReturn(null);
        $parentItem->method('getId')->willReturn(1);
        $parentItem->method('addCustomOption')->willReturnSelf();
        $item = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption', 'setQty'])
            ->addMethods(['getOptionId', 'getSelectionId', 'setCartQty'])
            ->getMock();
        $item->method('getTypeId')->willReturn('simple');
        $item->method('getCustomOption')
            ->willReturnMap([
                ['bundle_instance_id_hash', $customOption],
                ['info_buyRequest', $customOption],
            ]);
        $item->method('getSelectionId')->willReturn(2);
        $item->method('getOptionId')->willReturn(3);
        $item->method('getId')->willReturn(4);
        $item->method('getSku')->willReturn('sku1');
        $item->expects($this->once())->method('setQty')->with(5.0);
        $item->expects($this->once())->method('setCartQty')->with(5.0);
        $customOption->expects($this->exactly(2))->method('setValue')->with(5.0);
        $this->request->method('getParam')->with('productsQtyData')->willReturn(json_encode(['sku1' => 5.0]));
        $this->bundleProductProcessor->method('mapProductsBySkuForQuoteApproval')->willReturn([]);
        $result = [$parentItem, $item];
        $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
    }

    public function testUpdateChildItemQtyWithProductsQtyDataNoCustomOptionsStillSetsQty()
    {
        $this->config->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $this->product->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $customOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->addMethods(['setValue'])
            ->getMock();
        $customOption->method('getValue')->willReturn('productsData');
        $this->product->method('getCustomOption')->with('bundle_instance_id_hash')->willReturn($customOption);
        $parentItem = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption'])
            ->addMethods(['getOptionId', 'getSelectionId'])
            ->getMock();
        $parentItem->method('getTypeId')->willReturn(Type::TYPE_CODE);
        $parentItem->method('getCustomOption')
            ->willReturnMap([
                ['selection_qty_2', null],
                ['product_qty_4', null],
            ]);
        $parentItem->method('getSku')->willReturn('skuParent');
        $parentItem->method('getSelectionId')->willReturn(null);
        $parentItem->method('getOptionId')->willReturn(null);
        $parentItem->method('getId')->willReturn(1);
        $parentItem->method('addCustomOption')->willReturnSelf();
        $item = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypeId', 'getCustomOption', 'getSku', 'getId', 'addCustomOption', 'setQty'])
            ->addMethods(['getOptionId', 'getSelectionId', 'setCartQty'])
            ->getMock();
        $item->method('getTypeId')->willReturn('simple');
        $item->method('getCustomOption')
            ->willReturnMap([
                ['bundle_instance_id_hash', $customOption],
                ['info_buyRequest', $customOption],
            ]);
        $item->method('getSelectionId')->willReturn(2);
        $item->method('getOptionId')->willReturn(3);
        $item->method('getId')->willReturn(4);
        $item->method('getSku')->willReturn('sku1');
        $item->expects($this->once())->method('setQty')->with(7.0);
        $item->expects($this->once())->method('setCartQty')->with(7.0);
        $customOption->expects($this->never())->method('setValue');
        $this->request->method('getParam')->with('productsQtyData')->willReturn(json_encode(['sku1' => 7.0]));
        $this->bundleProductProcessor->method('mapProductsBySkuForQuoteApproval')->willReturn([]);
        $result = [$parentItem, $item];
        $this->plugin->afterPrepareForCartAdvanced($this->type, $result, $this->buyRequest, $this->product, 'mode');
    }
}
