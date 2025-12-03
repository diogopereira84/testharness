<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\ResourceModel;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Model\ResourceModel\Shop;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;
use Mirakl\Process\Model\Process;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;

class ShopTest extends TestCase
{
    /**
     * @var Shop
     */
    private $shop;

    /**
     * @var Mysql
     */
    private $mockAdapter;

    /**
     * @var AttributeRepository
     */
    private $mockAttributeRepository;

    /**
     * @var ShopCollection
     */
    private $mockShopCollection;

    /**
     * @var Process
     */
    private $mockProcess;

    /**
     * @var Context
     */
    private $mockContext;

    /**
     * @var ShopCollectionFactory
     */
    private $mockCollectionFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->mockAdapter = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['describeTable', 'insertOnDuplicate', 'insert', 'update', 'getConnection', 'lastInsertId'])
            ->getMock();

        $this->mockAdapter->expects($this->any())
            ->method('insert')
            ->willReturnSelf();

        $this->mockAdapter->expects($this->any())
            ->method('update')
            ->willReturnSelf();

        $this->mockAdapter->expects($this->any())
            ->method('lastInsertId')
            ->willReturn('1');

        $this->mockAttributeRepository = $this->getMockBuilder(AttributeRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->mockShopCollection = $this->getMockBuilder(ShopCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['count', 'toArray'])
            ->getMock();

        $this->mockProcess = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['output'])
            ->getMock();

        $this->mockContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockCollectionFactory = $this->getMockBuilder(ShopCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->shop = $this->getMockBuilder(Shop::class)
            ->setConstructorArgs([
                $this->mockCollectionFactory,
                $this->mockAttributeRepository,
                $this->mockContext
            ])
            ->onlyMethods(['getConnection', 'getEavOptionIds', 'getMainTable', 'getTable'])
            ->getMock();

        $this->shop->expects($this->any())
            ->method('getMainTable')
            ->willReturn('mirakl_shop');

        $this->shop->expects($this->any())
            ->method('getTable')
            ->willReturn('eav_attribute_option');

        $this->shop->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->mockAdapter);

        $yourCustomData = [
            1 => 1,
        ];

        $this->shop->expects($this->any())
            ->method('getEavOptionIds')
            ->willReturn($yourCustomData);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Model\ResourceModel\Shop::synchronize
     */
    public function testSynchronizeThrowsExceptionWhenShopCollectionIsEmpty()
    {
        $this->mockShopCollection->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Shops to synchronize cannot be empty.');

        $this->shop->synchronize($this->mockShopCollection, $this->mockProcess);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Model\ResourceModel\Shop::synchronize
     */
    public function testSynchronizeThrowsExceptionWhenAttributeNotFound()
    {
        $this->mockShopCollection->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->mockAttributeRepository->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('mirakl_shop_ids attribute is not created.');

        $this->shop->synchronize($this->mockShopCollection, $this->mockProcess);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Model\ResourceModel\Shop::synchronize
     */
    public function testSynchronizeCreatesNewShopOption()
    {
        $this->mockShopCollection->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->mockAttributeRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->createAttributeOption(111));

        $this->mockAdapter->expects($this->once())
            ->method('describeTable')
            ->willReturn([
                'id' => 1,
                'name' => 'Shop 1',
            ]);

        $this->mockShopCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Shop 1',
                    'field1' => 'value1',
                    'field2' => 'value2',
                    'shipping_info' => ['free_shipping' => '1'],
                    'date_created' => '2020-01-01 00:00:00',
                    'closed_from' => '2020-01-01 00:00:00',
                    'closed_to' => '2020-02-01 00:00:00',
                ]
            ]);

        $this->mockAdapter->expects($this->once())
            ->method('insertOnDuplicate')
            ->willReturn(1);

        $this->mockProcess->expects($this->once())
            ->method('output');

        $result = $this->shop->synchronize($this->mockShopCollection, $this->mockProcess);

        $this->assertEquals(1, $result);
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Model\ResourceModel\Shop::synchronize
     */
    public function testSynchronizeUpdatesExistingOption()
    {
        $this->mockShopCollection->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->mockAttributeRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->createAttributeOption(1));

        $this->mockAdapter->expects($this->once())
            ->method('describeTable')
            ->willReturn([
                'id' => 1,
                'name' => 'Shop 1',
            ]);

        $this->mockShopCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Shop 1',
                    'field1' => 'value1',
                    'field2' => 'value2',
                    'shipping_info' => ['free_shipping' => '1'],
                    'date_created' => '2020-01-01 00:00:00',
                    'closed_from' => '2020-01-01 00:00:00',
                    'closed_to' => '2020-02-01 00:00:00',
                ]
            ]);

        $this->mockAdapter->expects($this->once())
            ->method('insertOnDuplicate')
            ->willReturn(1);

        $this->mockProcess->expects($this->once())
            ->method('output');

        $result = $this->shop->synchronize($this->mockShopCollection, $this->mockProcess);

        $this->assertEquals(1, $result);
    }

    /**
     * @return MockObject|\Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private function createAttributeOption($optionId)
    {
        $attributeMock = $this->getMockBuilder('\Magento\Catalog\Api\Data\ProductAttributeInterface')
            ->onlyMethods(['getOptions'])
            ->addMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $attributeOption = $this->getMockBuilder('Magento\Eav\Api\Data\AttributeOptionInterface')
            ->addMethods(['getId', 'getOptions'])
            ->onlyMethods(['getLabel', 'getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $attributeMock->method('getId')->willReturn($optionId);
        $attributeOption->method('getLabel')->willReturn('Shop 1');
        $attributeOption->method('getValue')->willReturn($optionId);

        $attributeMock->method('getOptions')->willReturn([$attributeOption]);

        return $attributeMock;
    }

    /**
     * @covers \Fedex\MarketplaceCheckout\Model\ResourceModel\Shop::synchronize
     */
    public function testSynchronizeUpdatesShopNameWhenChanged(): void
    {
        $shopId = 1;
        $optionId = 1;
        $oldShopName = 'Old Shop Name';
        $newShopName = 'New Shop Name';

        $this->mockShopCollection->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $attributeMock = $this->getMockBuilder('\Magento\Catalog\Api\Data\ProductAttributeInterface')
            ->onlyMethods(['getOptions'])
            ->addMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $attributeOption = $this->getMockBuilder('Magento\Eav\Api\Data\AttributeOptionInterface')
            ->onlyMethods(['getLabel', 'getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $attributeMock->method('getId')->willReturn(111);
        $attributeOption->method('getLabel')->willReturn($oldShopName);
        $attributeOption->method('getValue')->willReturn($optionId);

        $attributeMock->method('getOptions')->willReturn([$attributeOption]);

        $this->mockAttributeRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);

        $this->shop->expects($this->any())
            ->method('getEavOptionIds')
            ->willReturn([$shopId => $optionId]);

        $this->mockAdapter->expects($this->once())
            ->method('describeTable')
            ->willReturn([
                'id' => 1,
                'name' => 'Shop Name',
            ]);

        $this->mockShopCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([
                [
                    'id' => $shopId,
                    'name' => $newShopName, // New name
                    'shipping_info' => ['free_shipping' => '1'],
                    'date_created' => '2020-01-01 00:00:00',
                ]
            ]);

        $this->shop->expects($this->any())
            ->method('getTable')
            ->willReturnCallback(function ($tableName) {
                if ($tableName === 'eav_attribute_option_value') {
                    return 'eav_attribute_option_value';
                }
                return 'eav_attribute_option';
            });

        $this->mockAdapter->expects($this->once())
            ->method('insertOnDuplicate')
            ->willReturn(1);

        $this->mockProcess->expects($this->once())
            ->method('output');

        $result = $this->shop->synchronize($this->mockShopCollection, $this->mockProcess);

        $this->assertEquals(1, $result);
    }
}
