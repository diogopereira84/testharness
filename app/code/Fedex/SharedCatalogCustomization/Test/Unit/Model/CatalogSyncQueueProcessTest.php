<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcess;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CatalogSyncQueueProcessTest extends TestCase
{	
	protected $catalogSyncQueueProcessMock;
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
    
        $this->catalogSyncQueueProcessMock = $this->getMockBuilder(CatalogSyncQueueProcess::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogSyncQueueProcessMock = $this->objectManager->getObject(
            CatalogSyncQueueProcess::class,
            []
        );     
		
    }

    /**
     * @test getCatalogSyncQueueProcessId
     * *
     * @return void
     */
    public function testGetCatalogSyncQueueProcessId()
    {

        $this->assertEquals(null, $this->catalogSyncQueueProcessMock->getCatalogSyncQueueProcessId());
    }

    
}