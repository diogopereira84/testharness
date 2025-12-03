<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfiguration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * SharedCatalogSyncQueueConfiguration Model Test
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SharedCatalogSyncQueueConfigurationTest extends TestCase
{

    protected $sharedCatalogSyncQueueConfigurationMock;
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

        $this->sharedCatalogSyncQueueConfigurationMock = $this->objectManager->getObject(
            SharedCatalogSyncQueueConfiguration::class,
            []
        );
    }

    /**
     * @test getId.
     *
     * @return void
     */
    public function testGetId()
    {
        $this->sharedCatalogSyncQueueConfigurationMock->setId(1);
        $this->assertEquals(1, $this->sharedCatalogSyncQueueConfigurationMock->getId());
    }

    /**
     * @test getSharedCatalogId.
     *
     * @return void
     */
    public function testGetSharedCatalogId()
    {
        $this->sharedCatalogSyncQueueConfigurationMock->setSharedCatalogId(9);
        $this->assertEquals(9, $this->sharedCatalogSyncQueueConfigurationMock->getSharedCatalogId());
    }

    /**
     * @test getLegacyCatalogRootFolderId.
     *
     * @return void
     */
    public function testGetLegacyCatalogRootFolderId()
    {
        $this->sharedCatalogSyncQueueConfigurationMock->setLegacyCatalogRootFolderId(null);
        $this->assertEquals(null, $this->sharedCatalogSyncQueueConfigurationMock->getLegacyCatalogRootFolderId());
    }

    /**
     * @test getCategoryId.
     *
     * @return void
     */
    public function testGetCategoryId()
    {
        $this->sharedCatalogSyncQueueConfigurationMock->setCategoryId(99);
        $this->assertEquals(99, $this->sharedCatalogSyncQueueConfigurationMock->getCategoryId());
    }

    /**
     * @test getStatus.
     *
     * @return void
     */
    public function testGetStatus()
    {
        $this->sharedCatalogSyncQueueConfigurationMock->setStatus(1);
        $this->assertEquals(1, $this->sharedCatalogSyncQueueConfigurationMock->getStatus());
    }
}
