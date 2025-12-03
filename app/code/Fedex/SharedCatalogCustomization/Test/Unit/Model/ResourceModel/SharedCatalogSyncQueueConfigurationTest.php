<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model\ResourceModel;

use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class SharedCatalogSyncQueueConfigurationTest extends TestCase
{
    protected $sharedCatalogSyncQueueConfiguration;
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

        $this->sharedCatalogSyncQueueConfiguration = $this->objectManager->getObject(
            SharedCatalogSyncQueueConfiguration::class,
            []
        );
    }

    /**
     * Test getTableName.
     *
     * @return void
     */
    public function testGetTableName()
    {
        $this->assertEquals('shared_catalog_sync_queue_configuration',$this->sharedCatalogSyncQueueConfiguration->getTableName());
    }
}
