<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcessFactory;
use Fedex\SharedCatalogCustomization\Cron\CatalogSyncDeleteItemCron;
use Fedex\SharedCatalogCustomization\Model\CatalogCleanup;
use Psr\Log\LoggerInterface;

class CatalogCleanupTest extends TestCase
{
    protected $messageInterfaceMock;
    protected $cleanupProcessFactoryMock;
    protected $catalogSyncQueueCleanupProcess;
    protected $catalogSyncCleanupItem;
    protected $catalogSyncDeleteItemCronMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerInterfaceMock;
    protected $catalogCleanupMock;
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @var \Exception|MockObject
     */
    private $exception;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->messageInterfaceMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessage'])
            ->getMockForAbstractClass();

        $this->cleanupProcessFactoryMock = $this->getMockBuilder(CatalogSyncQueueCleanupProcessFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueCleanupProcess = $this->getMockBuilder(CatalogSyncQueueCleanupProcess::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncCleanupItem = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCatalogType', 'getStatus', 'setStatus', 'getProductId', 'getSku', 'save'])
            ->getMock();

        $this->catalogSyncDeleteItemCronMock = $this->getMockBuilder(CatalogSyncDeleteItemCron::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteItem'])
            ->getMock();

        $this->loggerInterfaceMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['error'])
            ->getMockForAbstractClass();

        $this->exception = new \Exception();

        $this->objectManager = new ObjectManager($this);
        $this->catalogCleanupMock = $this->objectManager->getObject(
            CatalogCleanup::class,
            [
                'catalogSyncQueueCleanupProcessFactory' => $this->cleanupProcessFactoryMock,
                'catalogSyncDeleteItemCron' => $this->catalogSyncDeleteItemCronMock,
                'logger' => $this->loggerInterfaceMock
            ]
        );
    }

    /**
     * @test processMessage
     *
     */
    public function testProcessMessage()
    {
        $productId = 12;
        $productSku = 'test';
        $this->messageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($productId);
        $this->cleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcess);

        $this->catalogSyncQueueCleanupProcess->expects($this->any())->method('load')->with($productId)
            ->willReturn($this->catalogSyncCleanupItem);

        $this->catalogSyncCleanupItem->expects($this->any())->method('getCatalogType')->willReturn('product');
        $this->catalogSyncCleanupItem->expects($this->any())->method('getStatus')
            ->willReturn(self::STATUS_PROCESSING);
        $this->catalogSyncCleanupItem->expects($this->any())->method('getProductId')->willReturn($productId);
        $this->catalogSyncCleanupItem->expects($this->any())->method('getSku')->willReturn($productSku);
        $this->catalogSyncDeleteItemCronMock->expects($this->any())->method('deleteItem')
            ->with($productId, $productSku)
            ->willReturnSelf();

        $this->catalogSyncCleanupItem->expects($this->any())->method('setStatus')
            ->with(self::STATUS_COMPLETED)
            ->willReturnSelf();
        $this->catalogSyncCleanupItem->expects($this->any())->method('save');
        $this->assertEquals(null, $this->catalogCleanupMock->processMessage($this->messageInterfaceMock));
    }

    /**
     * @test processMessage
     *
     */
    public function testProcessMessageWithException()
    {
        $productId = 12;
        $this->messageInterfaceMock->expects($this->any())->method('getMessage')->willReturn($productId);
        $this->cleanupProcessFactoryMock->expects($this->any())->method('create')
            ->willReturn($this->catalogSyncQueueCleanupProcess);

        $this->catalogSyncQueueCleanupProcess->expects($this->any())->method('load')
            ->willThrowException($this->exception);
        $this->assertEquals(null, $this->catalogCleanupMock->processMessage($this->messageInterfaceMock));
    }
}
