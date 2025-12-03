<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Cron;

use Fedex\SharedCatalogCustomization\Cron\CatalogSyncQueue;
use Fedex\SharedCatalogCustomization\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CatalogSyncQueueTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var sharedCatalogCollectionFactory
     */
    private $sharedCatalogCollectionFactory;
    /**
     * @var sharedCatalogCollection
     */
    private $sharedCatalogCollection;
    /**
     * @var catalogSyncQueueHelper
     */
    private $catalogSyncQueueHelperMock;
    /**
     * @var Fedex\SharedCatalogCustomization\Cron\CatalogSyncQueue
     */
    private $catalogSyncQueueMock;
    /**
     * @var Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
     */
    private $sharedCatalogConfRepositoryMock;
    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->sharedCatalogCollection = $this->createPartialMock(Collection::class, ['load', 'addFieldToFilter']);

        $this->sharedCatalogCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create','getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogCollectionFactory->expects($this->once())
            ->method('create')->willReturn($this->sharedCatalogCollection);

        $this->sharedCatalogConfRepositoryMock=
        $this->getMockBuilder(SharedCatalogSyncQueueConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBySharedCatalogId','getStatus','getCategoryId','getLegacyCatalogRootFolderId'])
            ->getMock();

        $this->catalogSyncQueueHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock =$this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->catalogSyncQueueMock = $this->objectManager->getObject(
            CatalogSyncQueue::class,
            [
                'sharedCatalogCollectionFactory' => $this->sharedCatalogCollectionFactory,
                'catalogSyncQueueHelper' => $this->catalogSyncQueueHelperMock,
                'sharedCatalogConfRepository'=>$this->sharedCatalogConfRepositoryMock,
                'logger' =>  $this->loggerMock
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return Bool
     */
    public function testExecute()
    {
        $legacyCatalogRootFolderId = 6;
        $customerGroupId = 4;
        $id = 2;

        $this->sharedCatalogCollection->expects($this->any())->method('addFieldToFilter')
            ->with('legacy_catalog_root_folder_id', ['neq' => 'NULL'])
            ->willReturn($this->sharedCatalogCollection);

        $this->sharedCatalogConfRepositoryMock->expects($this->any())->method('getBySharedCatalogId')->willReturnSelf();

        $this->sharedCatalogConfRepositoryMock->expects($this->any())->method('getStatus')->willReturn(true);

        $this->sharedCatalogConfRepositoryMock->expects($this->any())->method('getCategoryId')->willReturn(2);

        $this->sharedCatalogConfRepositoryMock
        ->expects($this->any())->method('getLegacyCatalogRootFolderId')->willReturn(3);

        $item = new DataObject(
            [
                'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                'customer_group_id' => $customerGroupId,
                'id' => $id,
            ]
        );

        $this->sharedCatalogCollection->addItem($item);

        $this->assertEquals(true, $this->catalogSyncQueueMock->execute());
    }

    public function testExecuteWithNoSuchEntityException()
    {
        $legacyCatalogRootFolderId = 6;
        $customerGroupId = 4;
        $id = 2;
        
        $exception = new NoSuchEntityException();

        $this->sharedCatalogConfRepositoryMock
        ->expects($this->any())->method('getBySharedCatalogId')->willThrowException($exception);

        $item = new DataObject(
            [
                'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                'customer_group_id' => $customerGroupId,
                'id' => $id,
            ]
        );

        $this->sharedCatalogCollection->addItem($item);

        $this->assertEquals(true, $this->catalogSyncQueueMock->execute());
    }
}
