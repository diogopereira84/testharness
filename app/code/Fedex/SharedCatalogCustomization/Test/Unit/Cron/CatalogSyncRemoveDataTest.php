<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Cron;

use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\Collection;
use Magento\Framework\DataObject;
use Fedex\SharedCatalogCustomization\Cron\CatalogSyncRemoveData;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogSyncRemoveDataTest extends TestCase
{

    protected $catalogSyncRemoveDataMock;
    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->collectionMock = $this->createMock(Collection::class, ['load', 'addFieldToFilter', 'delete', 'getSize']);
        $this->collectionFactoryMock->expects($this->atLeastOnce())->method('create')
            ->willReturn($this->collectionMock);

        $this->objectManager = new ObjectManager($this);

        $this->catalogSyncRemoveDataMock = $this->objectManager->getObject(
            CatalogSyncRemoveData::class,
            [
                '_catalogSyncQueueCollectionFactory' => $this->collectionFactoryMock
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
        $id = 2;
        $companyId = 2;
        $legacyCatalogRootFolderId = 6;
        $date = date('Y-m-d', strtotime('-30 day'));
        $oldDate = date('Y-m-d', strtotime('-32 day'));

        $collectionItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();

        $item = new DataObject(
            [
                'id' => $id,
                'company_id' => $companyId,
                'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                'created_at' => $oldDate
            ]
        );

        $this->collectionMock->expects($this->atLeastOnce())->method('addFieldToFilter')
            ->with('created_at', ['lt' => $date])
            ->willReturn($this->collectionMock);
        $this->collectionMock->addItem($item);
        $this->collectionMock->expects($this->any())->method('getSize')->willReturn(1);
        $this->collectionMock->expects($this->any())->method('getIterator')->willReturn(
            new \ArrayIterator([$collectionItem])
        );

        $this->assertEquals(null, $this->catalogSyncRemoveDataMock->execute());
    }
}
