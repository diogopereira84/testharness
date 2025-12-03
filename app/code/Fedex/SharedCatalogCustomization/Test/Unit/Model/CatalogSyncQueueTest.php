<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class CatalogSyncQueueTest extends TestCase
{   
    protected $catalogSyncQueueMock;
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
    
        $this->catalogSyncQueueMock = $this->getMockBuilder(CatalogSyncQueue::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueMock = $this->objectManager->getObject(
            CatalogSyncQueue::class,
            []
        );     
        
    }

    /**
     * @test getCatalogSyncQueueId
     * *
     * @return void
     */
    public function testGetCatalogSyncQueueId()
    {

        $this->assertEquals(null, $this->catalogSyncQueueMock->getCatalogSyncQueueId());
    }

    
}