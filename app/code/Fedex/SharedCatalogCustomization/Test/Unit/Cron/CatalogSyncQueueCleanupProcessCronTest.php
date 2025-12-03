<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\Collection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcess;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcessFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Cron\CatalogSyncQueueCleanupProcessCron;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Psr\Log\LoggerInterface;

class CatalogSyncQueueCleanupProcessCronTest extends TestCase
{
    protected $catalogSyncCleanupProcessCollectionMock;
    protected $catalogSyncCleanupProcessCollectionFactoryMock;
    protected $catalogSyncQueueCleanupProcessMock;
    protected $cleanupProcessFactoryMock;
    protected $messageInterfaceMock;
    protected $productRepositoryInterfaceMock;
    protected $productInterfaceMock;
    protected $manageCatalogItemsHelperMock;
    /**
     * @var (\Magento\Framework\MessageQueue\PublisherInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $publisherMock;
    protected $catalogSyncCleanupItem;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $exception;
    protected $catalogSyncQueueCleanupProcessCronMock;
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->catalogSyncCleanupProcessCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getSize', 'getId', 'getProductId', 'getIterator'])
            ->getMock();

        $this->catalogSyncCleanupProcessCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->catalogSyncQueueCleanupProcessMock = $this->getMockBuilder(CatalogSyncQueueCleanupProcess::class)
            ->setMethods(['load', 'getId', 'getProductId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cleanupProcessFactoryMock = $this->getMockBuilder(CatalogSyncQueueCleanupProcessFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->messageInterfaceMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();

        $this->productRepositoryInterfaceMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->productInterfaceMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCategoryIds'])
            ->getMockForAbstractClass();

        $this->manageCatalogItemsHelperMock = $this->getMockBuilder(ManageCatalogItems::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkNegotiableQuote'])
            ->getMockForAbstractClass();

        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->catalogSyncCleanupItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['setStatus', 'setErrorMsg', 'save'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();

        $this->exception = new \Magento\Framework\Exception\NoSuchEntityException();

        $this->catalogSyncQueueCleanupProcessCronMock = $this->objectManager->getObject(
            CatalogSyncQueueCleanupProcessCron::class,
            [
                'productRepositoryInterface' => $this->productRepositoryInterfaceMock,
                'catalogSyncCleanupCollectionFactory' => $this->catalogSyncCleanupProcessCollectionFactoryMock,
                'catalogSyncQueueCleanupProcessFactory' => $this->cleanupProcessFactoryMock,
                'manageCatalogItemsHelper' => $this->manageCatalogItemsHelperMock,
                'publisher' => $this->publisherMock,
                'message' => $this->messageInterfaceMock,
                'logger' => $this->loggerMock
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
        $productId = 12;

        $this->catalogSyncCleanupProcessCollectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncCleanupProcessCollectionMock);

        $catalogSyncCollectionItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getProductId'])
            ->getMock();

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->once())->method('addFieldToFilter')
            ->with('status', ['eq' => ManageCatalogItems::STATUS_PENDING])
            ->willReturnSelf();

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->any())->method('getSize')
            ->willReturn(1);

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$catalogSyncCollectionItem]));

        $catalogSyncCollectionItem->expects($this->any())->method('getId')->willReturn(5);
        $catalogSyncCollectionItem->expects($this->any())->method('getProductId')
            ->willReturn($productId);

        $this->canProductDelete();
        $this->messageInterfaceMock->expects($this->any())->method('setMessage')->with(5)->willReturnSelf();

        $this->catalogSyncQueueCleanupProcessCronMock->execute();
    }

    /**
     * Test testExecuteWithCategory method
     *
     * @return void
     */
    public function testExecuteWithCategory()
    {
        $productId = 12;

        $this->catalogSyncCleanupProcessCollectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncCleanupProcessCollectionMock);

        $catalogSyncCollectionItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getProductId'])
            ->getMock();

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->once())->method('addFieldToFilter')
            ->with('status', ['eq' => self::STATUS_PENDING])
            ->willReturnSelf();

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->any())->method('getSize')
            ->willReturn(1);

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$catalogSyncCollectionItem]));

        $catalogSyncCollectionItem->expects($this->any())->method('getId')->willReturn(5);
        $catalogSyncCollectionItem->expects($this->any())->method('getProductId')
            ->willReturn($productId);

        $this->canProductDeleteWithCategory();

        $this->catalogSyncQueueCleanupProcessCronMock->execute();
    }

    /**
     * Test testExecuteWithSuchEntityException method
     *
     * @return void
     */
    public function testExecuteWithSuchEntityException()
    {
        $productId = 12;

        $this->catalogSyncCleanupProcessCollectionFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->catalogSyncCleanupProcessCollectionMock);

        $catalogSyncCollectionItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getProductId'])
            ->getMock();

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->once())->method('addFieldToFilter')
            ->with('status', ['eq' => self::STATUS_PENDING])
            ->willReturnSelf();

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->any())->method('getSize')
            ->willReturn(1);

        $this->catalogSyncCleanupProcessCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$catalogSyncCollectionItem]));

        $catalogSyncCollectionItem->expects($this->any())->method('getId')->willReturn(5);
        $catalogSyncCollectionItem->expects($this->any())->method('getProductId')
            ->willReturn($productId);

        $this->canProductDeleteWithSuchEntityException();

        $this->catalogSyncQueueCleanupProcessCronMock->execute();
    }

    /**
     * Check if product is not available in negotiable quote and category
     *
     * @return boolean
     */
    public function canProductDelete()
    {
        $productId = 12;
        $rowId = 5;

        $this->cleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('load')->with($rowId)
            ->willReturn($this->catalogSyncCleanupItem);

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')->with($productId)
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([]);

        $this->manageCatalogItemsHelperMock->expects($this->any())->method('checkNegotiableQuote')->with($productId)
            ->willReturn(false);

        $this->catalogSyncCleanupItem->expects($this->any())->method('setStatus')
            ->with(self::STATUS_PROCESSING)
            ->willReturnSelf();

        $this->catalogSyncCleanupItem->expects($this->any())->method('save');

        $this->catalogSyncQueueCleanupProcessCronMock->canProductDelete($productId, $rowId);
    }

    /**
     * Check if product is not available in negotiable quote and category
     *
     * @return boolean
     */
    public function canProductDeleteWithCategory()
    {
        $productId = 12;
        $rowId = 5;
        $this->cleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('load')->with($rowId)
            ->willReturn($this->catalogSyncCleanupItem);

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')->with($productId)
            ->willReturn($this->productInterfaceMock);

        $this->productInterfaceMock->expects($this->any())->method('getCategoryIds')->willReturn([10,15]);

        $this->manageCatalogItemsHelperMock->expects($this->any())->method('checkNegotiableQuote')->with($productId)
            ->willReturn(true);

        $this->catalogSyncCleanupItem->expects($this->any())->method('setErrorMsg')
            ->with('Product may be assigned into another category or negotiable quote is in active status.')
            ->willReturnSelf();

        $this->catalogSyncCleanupItem->expects($this->any())->method('setStatus')
            ->with(self::STATUS_FAILED)
            ->willReturn(self::STATUS_FAILED);

        $this->catalogSyncCleanupItem->expects($this->any())->method('save');

        $this->catalogSyncQueueCleanupProcessCronMock->canProductDelete($productId, $rowId);
    }

    /**
     * Test canProductDeleteWithSuchEntityException method with exception
     *
     */
    public function canProductDeleteWithSuchEntityException()
    {
        $productId = 12;
        $rowId = 5;

        $this->cleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcessMock);

        $this->catalogSyncQueueCleanupProcessMock->expects($this->any())->method('load')->with($rowId)
            ->willReturn($this->catalogSyncCleanupItem);

        $this->productRepositoryInterfaceMock->expects($this->any())->method('getById')
            ->with($productId)
            ->willThrowException($this->exception);

        $this->catalogSyncCleanupItem->expects($this->any())->method('setErrorMsg')
            ->with('"No such entity."')
            ->willReturnSelf();

        $this->catalogSyncCleanupItem->expects($this->any())->method('setStatus')
            ->with(self::STATUS_FAILED)
            ->willReturn(self::STATUS_FAILED);

        $this->catalogSyncCleanupItem->expects($this->any())->method('save');

        $this->catalogSyncQueueCleanupProcessCronMock->canProductDelete($productId, $rowId);
    }
}
