<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\ThirdPartyProduct;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Model\Quote\ThirdPartyProduct\ExternalProd;
use Fedex\MarketplaceProduct\Model\ShopManagement;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;
use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ExternalProdTest extends TestCase
{
    /**
     * @var ExternalProd
     */
    private ExternalProd $externalProd;

    /**
     * @var ShopManagement
     */
    private ShopManagement $shopManagement;

    /**
     * @var ToggleConfig
     */
    private ToggleConfig $toggleConfig;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->shopManagement = $this->getMockBuilder(ShopManagement::class)
            ->setMethods(['getId', 'getSellerAltName', 'getShopByProduct'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->externalProd = new ExternalProd($this->shopManagement, $this->toggleConfig);
    }

    /**
     * Test createAdditionalData function.
     *
     * @return void
     */
    public function testCreateAdditionalData(): void
    {
        $product = $this
            ->getMockBuilder(Product::class)
            ->setMethods(['getProductId', 'getData'])
            ->disableOriginalConstructor()->getMock();
        $product->method('getProductId')->willReturn(1);
        $product->method('getData')->willReturnMap([
            ['map_sku', null, '123456'],
        ]);

        $item = $this
            ->getMockBuilder(Item::class)
            ->setMethods(['getItemId', 'getQty', 'getProduct', 'getAdditionalData', 'getName'])
            ->disableOriginalConstructor()->getMock();
        $item->method('getProduct')->willReturn($product);
        $item->method('getItemId')->willReturn(123);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getQty')->willReturn(1);
        $item->method('getAdditionalData')->willReturn('{"unit_price": 10, "total": 10}');

        $shop = $this
            ->getMockBuilder(ShopInterface::class)
            ->setMethods(['getId', 'getSellerAltName', 'getShopByProduct'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->shopManagement->method('getShopByProduct')->willReturn($shop);
        $this->shopManagement->method('getId')->willReturn(1);
        $this->shopManagement->method('getSellerAltName')->willReturn('Alt Name');

        $decodedData = ['key1' => 'value1', 'key2' => 'value2'];

        $result = $this->externalProd->createAdditionalData($item, $decodedData);

        $this->assertArrayHasKey('external_prod', $result);
        $this->assertCount(1, $result['external_prod']);
        $this->assertEquals(123, $result['external_prod'][0]['product']['instanceId']);
        $this->assertEquals('Test Product', $result['external_prod'][0]['product']['name']);
        $this->assertEquals(1, $result['external_prod'][0]['product']['qty']);
        $this->assertEquals(1, $result['external_prod'][0]['externalSkus'][0]['qty']);
        $this->assertEquals(10, $result['external_prod'][0]['externalSkus'][0]['unitPrice']);
        $this->assertEquals(10, $result['external_prod'][0]['externalSkus'][0]['price']);
        $this->assertFalse($result['external_prod'][0]['sensitiveData']);
    }

    /**
     * Test createAdditionalData returns existing data when external_prod already exists
     */
    public function testCreateAdditionalDataWithExistingExternalProdData(): void
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $item->expects($this->never())
            ->method('getProduct');

        $this->shopManagement->expects($this->never())
            ->method('getShopByProduct');

        $existingExternalProd = [
            'product' => [
                'id' => 999,
                'qty' => 3,
                'name' => 'Existing Product',
                'version' => '1',
                'instanceId' => 12345,
                'vendorReference' => [
                    'vendorId' => '42',
                    'vendorProductName' => 'Existing Product',
                    'vendorProductDesc' => 'Existing Description',
                    'altName' => 'Existing Alt Name',
                ]
            ],
            'externalSkus' => [
                [
                    'skuDescription' => 'Existing SKU',
                    'skuRef' => 'SKU123',
                    'code' => 'SKU123',
                    'unitPrice' => 25,
                    'price' => 75,
                    'qty' => 3
                ]
            ],
            'sensitiveData' => false,
            'priceable' => true
        ];

        $decodedData = [
            'key1' => 'value1',
            'external_prod' => [$existingExternalProd]
        ];

        $result = $this->externalProd->createAdditionalData($item, $decodedData);

        $this->assertSame($decodedData, $result);
        $this->assertArrayHasKey('external_prod', $result);
        $this->assertCount(1, $result['external_prod']);
        $this->assertSame($existingExternalProd, $result['external_prod'][0]);
    }

    /**
     * Test createAdditionalData with empty ItemId and instanceIdFixToggle enabled
     */
    public function testCreateAdditionalDataWithEmptyItemIdAndToggleEnabled(): void
    {
        $product = $this
            ->getMockBuilder(Product::class)
            ->setMethods(['getProductId', 'getData'])
            ->disableOriginalConstructor()->getMock();
        $product->method('getProductId')->willReturn(1);
        $product->method('getData')->willReturnMap([
            ['map_sku', null, '123456'],
        ]);

        $item = $this
            ->getMockBuilder(Item::class)
            ->setMethods(['getItemId', 'getQty', 'getProduct', 'getAdditionalData', 'getName'])
            ->disableOriginalConstructor()->getMock();
        $item->method('getProduct')->willReturn($product);
        $item->method('getItemId')->willReturn(null);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getQty')->willReturn(1);
        $item->method('getAdditionalData')->willReturn('{"unit_price": 10, "total": 10}');

        $shop = $this
            ->getMockBuilder(ShopInterface::class)
            ->setMethods(['getId', 'getSellerAltName', 'getShopByProduct'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $shop->method('getId')->willReturn('1');
        $shop->method('getSellerAltName')->willReturn('Alt Name');

        $this->shopManagement->method('getShopByProduct')->willReturn($shop);

        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturnMap([
                ['explorers_instanceid_d_199138_fix', true]
            ]);

        $decodedData = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->externalProd->createAdditionalData($item, $decodedData);

        $this->assertArrayHasKey('external_prod', $result);
        $this->assertCount(1, $result['external_prod']);

        $instanceId = $result['external_prod'][0]['product']['instanceId'];
        $this->assertIsNumeric($instanceId);
        $this->assertGreaterThanOrEqual(10 ** 11, $instanceId);
        $this->assertLessThanOrEqual(10 ** 12 - 1, $instanceId);

        $this->assertEquals('Test Product', $result['external_prod'][0]['product']['name']);
        $this->assertEquals(1, $result['external_prod'][0]['product']['qty']);
        $this->assertEquals(1, $result['external_prod'][0]['externalSkus'][0]['qty']);
        $this->assertEquals(10, $result['external_prod'][0]['externalSkus'][0]['unitPrice']);
        $this->assertEquals(10, $result['external_prod'][0]['externalSkus'][0]['price']);
        $this->assertFalse($result['external_prod'][0]['sensitiveData']);
    }

    /**
     * Test createAdditionalData with WrongQuoteInformationPassed toggle enabled
     */
    public function testCreateAdditionalDataWithWrongQuoteInformationPassedToggleEnabled(): void
    {
        $product = $this
            ->getMockBuilder(Product::class)
            ->setMethods(['getProductId', 'getData'])
            ->disableOriginalConstructor()->getMock();
        $product->method('getProductId')->willReturn(1);
        $product->method('getData')->willReturnMap([
            ['map_sku', null, '123456'],
        ]);

        $item = $this
            ->getMockBuilder(Item::class)
            ->setMethods(['getItemId', 'getQty', 'getProduct', 'getAdditionalData', 'getName'])
            ->disableOriginalConstructor()->getMock();
        $item->method('getProduct')->willReturn($product);
        $item->method('getItemId')->willReturn(123);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getQty')->willReturn(1);
        $item->method('getAdditionalData')->willReturn('{"unit_price": 10, "total": 10}');

        $shop = $this
            ->getMockBuilder(ShopInterface::class)
            ->setMethods(['getId', 'getSellerAltName', 'getShopByProduct'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $shop->method('getId')->willReturn('1');
        $shop->method('getSellerAltName')->willReturn('Alt Name');

        $this->shopManagement->method('getShopByProduct')->willReturn($shop);

        $this->toggleConfig->method('getToggleConfigValue')
            ->willReturnMap([
                ['explorers_instanceid_d_199138_fix', false],
                ['tiger_team_d384213_wrong_quote_information_passed', true]
            ]);

        $decodedData = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->externalProd->createAdditionalData($item, $decodedData);

        $this->assertArrayHasKey('external_prod', $result);
        $this->assertCount(1, $result['external_prod']);

        $this->assertArrayHasKey('is_marketplace', $result['external_prod'][0]);
        $this->assertTrue($result['external_prod'][0]['is_marketplace']);

        $this->assertEquals(123, $result['external_prod'][0]['product']['instanceId']);
        $this->assertEquals('Test Product', $result['external_prod'][0]['product']['name']);
        $this->assertEquals(1, $result['external_prod'][0]['product']['qty']);
        $this->assertEquals('123456', $result['external_prod'][0]['externalSkus'][0]['code']);
        $this->assertEquals(10, $result['external_prod'][0]['externalSkus'][0]['unitPrice']);
        $this->assertEquals(10, $result['external_prod'][0]['externalSkus'][0]['price']);
    }
}
