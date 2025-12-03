<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Cron;

use Fedex\SharedCatalogCustomization\Cron\CatalogSyncDeleteItemCron;
use Magento\Framework\App\ResourceConnection;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogSyncDeleteItemCronTest extends TestCase
{
    protected $adapterInterfaceMock;
    public const ATTRIBUTE_SET_NAME = 'PrintOnDemand';

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var ManageCatalogItems|MockObject
     */
    private $manageCatalogItemsMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryInterfaceMock;

    /**
     * @var Registry|MockObject
     */
    private $registryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DBSelect|MockObject
     */
    private $dbSelectMock;

    /**
     * @var CatalogSyncDeleteItemCron|MockObject
     */
    private $catalogSyncDeleteItemCron;

    /**
     * @var ProductModel|MockObject
     */
    private $productMock;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

    /**
     * @var ProductItemRepositoryInterface|MockObject
     */
    private $itemRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var \Exception|MockObject
     */
    private $exception;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'select', 'fetchAll'])
            ->getMock();

        $this->manageCatalogItemsMock = $this->getMockBuilder(ManageCatalogItems::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepositoryInterfaceMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this->createMock(ProductModel::class);

        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where', 'join'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchAll'])
            ->getMockForAbstractClass();

        $this->itemRepositoryMock = $this->getMockBuilder(ProductItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'getItems', 'delete'])
            ->getMockForAbstractClass();

        $this->itemRepositoryMock->method('getList')->willReturn($this->itemRepositoryMock);
        $productItem = $this->getMockForAbstractClass(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class);
        $this->itemRepositoryMock->method('getItems')->willReturn([$productItem]);
        $this->searchCriteriaBuilderMock = $this->createPartialMock(
            SearchCriteriaBuilder::class,
            ['create', 'addFilter']
        );
        $searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteria);

        $this->exception = new \Exception();

        $this->objectManager = new ObjectManager($this);

        $this->catalogSyncDeleteItemCron = $this->objectManager->getObject(
            CatalogSyncDeleteItemCron::class,
            [
                'resourceConnection' => $this->resourceConnectionMock,
                'manageCatalogItemsHelper' => $this->manageCatalogItemsMock,
                'productRepositoryInterface' => $this->productRepositoryInterfaceMock,
                'registry' => $this->registryMock,
                'logger' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'itemRepository' => $this->itemRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {

        $this->getProductsWithoutCategory();
        $this->deleteItem();
        $this->catalogSyncDeleteItemCron->execute();
    }

    private function getProductsWithoutCategory()
    {
        $productsIdsWithoutCategory = [['entity_id' => 947, 'sku' => 'test23234']];

        $this->manageCatalogItemsMock->expects($this->any())
            ->method('getAttrSetId')
            ->with(self::ATTRIBUTE_SET_NAME)
            ->willReturn(12);
        $this->resourceConnectionMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())
            ->method('select')
            ->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())
            ->method('from')
            ->with(
                ['cpe' => 'catalog_product_entity'],
                ['cpe.entity_id', 'cpe.sku']
            )
            ->willReturnSelf();
        $this->dbSelectMock->expects($this->any())
            ->method('where')
            ->withConsecutive(
                ['cpe.attribute_set_id=?', 12],
                ['cpe.entity_id not in (select distinct product_id from catalog_category_product)']
            )
            ->willReturnSelf();
        $this->adapterInterfaceMock->expects($this->any())
            ->method('fetchAll')
            ->willReturn($productsIdsWithoutCategory);
    }

    /**
     * Test getProductsWithoutCategory
     *
     * @return void
     */
    public function testGetProductsWithoutCategoryException()
    {

        $productsIdsWithoutCategory = [['entity_id' => 947]];

        $this->resourceConnectionMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())
            ->method('select')
            ->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())
            ->method('from')
            ->with(
                ['cpe' => 'catalog_product_entity'],
                ['cpe.entity_id']
            )
            ->willReturnSelf();
        $this->dbSelectMock->expects($this->any())
            ->method('where')
            ->withConsecutive(
                ['cpe.attribute_set_id=?', 12],
                ['cpe.entity_id not in (select distinct product_id from catalog_category_product)']
            )
            ->willReturnSelf();
        $this->adapterInterfaceMock->expects($this->any())
            ->method('fetchAll')
            ->willThrowException($this->exception);
        $this->assertEquals(null,$this->catalogSyncDeleteItemCron->getProductsWithoutCategory());
    }

    /**
     * Test deleteItem
     *
     * @return boolean  true
     */
    public function deleteItem()
    {
        $productId = 947;
        $sku = 'test';

        $registryValueMap = [
            ['product', $this->productMock, $this->registryMock],
            ['current_product', $this->productMock, $this->registryMock]
        ];

        $this->registryMock->expects($this->any())
            ->method('register')
            ->willReturn($registryValueMap);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMockForAbstractClass();
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('getById')
            ->with(947)
            ->willReturn($product);
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('delete');
        $this->registryMock->expects($this->any())
            ->method('unregister')
            ->willReturn('isSecureArea');

        $this->assertEquals(false, $this->catalogSyncDeleteItemCron->deleteItem($productId, $sku));

        return true;
    }

    /**
     * Test deleteItem method with exception
     *
     * @return void
     */
    public function testDeleteItemWithException()
    {
        $productId = 947;
        $sku = 'test';

        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMockForAbstractClass();

        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('getById')
            ->with($productId)
            ->willReturn($product);
        $this->productRepositoryInterfaceMock->expects($this->any())
            ->method('delete')
            ->willThrowException($this->exception);

        $this->assertEquals(false, $this->catalogSyncDeleteItemCron->deleteItem($productId, $sku));
    }

    /**
     * Test deleteSharedCatalogItem method
     *
     * @return void
     */
    public function testDeleteSharedCatalogItemException()
    {
        $productId = 947;
        $sku = 'test';

        $this->itemRepositoryMock->method('getList')->willReturn($this->itemRepositoryMock);
        $productItem = $this->getMockForAbstractClass(\Magento\SharedCatalog\Api\Data\ProductItemInterface::class);
        $this->itemRepositoryMock->method('getItems')->willReturn([$productItem]);

        $searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('create')->willThrowException($this->exception);

        $this->assertEquals(false, $this->catalogSyncDeleteItemCron->deleteSharedCatalogItem($productId, $sku));
    }
}
