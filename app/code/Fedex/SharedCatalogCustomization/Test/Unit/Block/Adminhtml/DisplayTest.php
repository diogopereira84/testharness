<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Test\Unit\Block\Adminhtml;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\Block\Template\Context;
use Fedex\SharedCatalogCustomization\Block\Adminhtml\Display;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess\Collection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueProcess\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\Collection as CleanupCollection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\CollectionFactory as
CleanupCollectionFactory;

class DisplayTest extends TestCase
{
    protected $resourceConnectionMock;
    protected $connectionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $displayMock;
    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Collection|MockObject
     */
    protected $collectionMock;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $catalogSyncQueueProcessCollectionFactoryMock;

    /**
     * @var CleanupCollection|MockObject
     */
    protected $cleanupCollectionMock;

    /**
     * @var CleanupCollectionFactory|MockObject
     */
    protected $cleanupCollectionFactoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->catalogSyncQueueProcessCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->collectionMock = $this->createMock(Collection::class, ['addFieldToFilter', 'getSize']);

        $this->cleanupCollectionFactoryMock = $this->getMockBuilder(CleanupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->cleanupCollectionMock = $this->createMock(CleanupCollection::class, ['addFieldToFilter', 'getSize']);

        $this->resourceConnectionMock = $this
            ->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['fetchCol'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->displayMock = $this->objectManager->getObject(
            Display::class,
            [
                'catalogSyncQueueProcessCollectionFactory' => $this->catalogSyncQueueProcessCollectionFactoryMock,
                'cleanupCollectionFactory' => $this->cleanupCollectionFactoryMock,
                'resourceConnection' => $this->resourceConnectionMock,
                '_request' => $this->requestMock
            ]
        );
    }

    /**
     * @test getCatalogSyncDetails
     *
     * @return void
     */
    public function testGetCatalogSyncDetails()
    {
        $id = 3765;
        $failedCount = 0;
        $back_url = 'shared_catalog_customization/grid/index';
        $newActionTypeCount = 0;
        $updateActionTypeCount = 0;
        $deleteActionTypeCount = 0;

        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn($id);

        $this->catalogSyncQueueProcessCollectionFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
            ['action_type',['eq' => 'new'] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
            ['action_type',['eq' => 'update'] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
            ['action_type',['eq' => 'delete'] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'failed' ] ],
        )->willReturnSelf();

        $this->collectionMock->expects($this->exactly(4))->method('getSize')->willReturn('5', '2', '1', '1');
        $result = $this->displayMock->getCatalogSyncDetails();
    }

    /**
     * @test getCatalogSyncQueueDetails
     *
     * @return void
     */
    public function testGetCatalogSyncQueueDetails()
    {
        $id = 3765;
        $failedCount = 0;
        $back_url = 'shared_catalog_customization/grid/index';
        $newActionTypeCount = 0;
        $updateActionTypeCount = 0;
        $deleteActionTypeCount = 0;
        $query ='SELECT catalog_sync_queue_process.catalog_sync_queue_id FROM catalog_sync_queue_process WHERE (catalog_sync_queue_id ="3765" AND catalog_type="product" AND status ="failed") UNION ALL SELECT catalog_sync_queue_cleanup_process.catalog_sync_queue_id FROM catalog_sync_queue_cleanup_process WHERE (catalog_sync_queue_id ="3765" AND catalog_type="product" AND status ="failed")';

        $this->requestMock->expects($this->any())->method('getParam')->with('id')->willReturn($id);

        $this->catalogSyncQueueProcessCollectionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
            ['action_type',['eq' => 'new'] ],
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
            ['action_type',['eq' => 'update'] ],
        )->willReturnSelf();

        $this->cleanupCollectionFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->cleanupCollectionMock);

        $this->cleanupCollectionMock->expects($this->any())->method('addFieldToFilter')->withConsecutive(
            ['catalog_sync_queue_id',['eq' => $id] ],
            ['catalog_type',['eq' => 'product'] ],
            ['status', ['eq' => 'completed' ] ],
        )->willReturnSelf();
    
        $this->collectionMock->expects($this->exactly(2))->method('getSize')->willReturn('5', '2');
        $this->cleanupCollectionMock->expects($this->exactly(1))->method('getSize')->willReturn('1');

        $this->resourceConnectionMock->expects($this->once())->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->once())->method('fetchCol')->with($query)->willReturn([1]);

        $result = $this->displayMock->getCatalogSyncQueueDetails();
    }
}
