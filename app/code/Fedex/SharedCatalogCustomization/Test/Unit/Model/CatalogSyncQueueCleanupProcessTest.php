<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcess;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogSyncQueueCleanupProcessTest extends TestCase
{
    /**
     * @var (\Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcess & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogSyncQueueCleanupProcess;
    protected $catalogSyncQueueCleanupProcessMock;
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
    
        $this->catalogSyncQueueCleanupProcess = $this->getMockBuilder(CatalogSyncQueueCleanupProcess::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueCleanupProcessMock = $this->objectManager->getObject(
            CatalogSyncQueueCleanupProcess::class,
            []
        );
    }

    /**
     * @test getCatalogSyncQueueCleanupProcessId
     * *
     * @return void
     */
    public function testGetCatalogSyncQueueCleanupProcessId()
    {
        $this->assertEquals(null, $this->catalogSyncQueueCleanupProcessMock->getCatalogSyncQueueCleanupProcessId());
    }
}
