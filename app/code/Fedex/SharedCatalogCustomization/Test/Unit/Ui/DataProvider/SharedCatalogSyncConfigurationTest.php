<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Ui\DataProvider;

use Fedex\SharedCatalogCustomization\Ui\DataProvider\SharedCatalogSyncConfiguration;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\Collection;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfiguration;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Test for UI DataProvider\SharedCatalogSynConfiguration.
 */
class SharedCatalogSyncConfigurationTest extends TestCase
{

    /**
     * @var CollectionFactory|MockObject
     */
    private $sharedCatalogSynConfCollectionFactory;

    /**
     * @var Collection|MockObject
     */
    private $sharedCatalogSynConfCollectionMock;
    
    /**
     * @var SharedCatalogSyncQueueConfiguration|MockObject
     */
    private $sharedCatalogSynConfMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var SharedCatalogSyncConfiguration|MockObject
     */
    private $sharedCatalogSynConfigurationMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->sharedCatalogSynConfCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->sharedCatalogSynConfCollectionMock = $this->createMock(
            Collection::class,
            [
                'getItems',
                'count'
            ]
        );

        $this->sharedCatalogSynConfMock = $this->createMock(SharedCatalogSyncQueueConfiguration::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->sharedCatalogSynConfCollectionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->sharedCatalogSynConfCollectionMock);

        $this->sharedCatalogSynConfigurationMock = $this->objectManager->getObject(
            SharedCatalogSyncConfiguration::class,
            [
                'name' => 'shared_catalog_sync_configuration_form_data_source',
                'primaryFieldName' => 'shared_catalog_id',
                'requestFieldName' => 'shared_catalog_id',
                'collectionFactory' => $this->sharedCatalogSynConfCollectionFactory,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * Test for getData().
     *
     * @return array
     */
    public function testGetData()
    {
        $sharedCatalogId = 9;
    
        $this->sharedCatalogSynConfCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->sharedCatalogSynConfMock]);
        $this->sharedCatalogSynConfCollectionMock->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn($sharedCatalogId);

        $this->sharedCatalogSynConfigurationMock->getData();
    }

    /**
     * Test for getData().
     *
     * @return array
     */
    public function testGetDataWithNoData()
    {
        $sharedCatalogId = 9;

        $this->sharedCatalogSynConfCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->sharedCatalogSynConfMock]);

        $this->sharedCatalogSynConfCollectionMock->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->requestMock->expects($this->any())->method('getParam')->willReturn($sharedCatalogId);
        $this->sharedCatalogSynConfigurationMock->getData();
    }

    /**
     * Test for getData().
     *
     * @return array
     */
    public function testGetDataWithLoadData()
    {
        $sharedCatalogId = 9;
        $data = 'sample data';

        // class member data set value
        $reflection = new \ReflectionClass($this->sharedCatalogSynConfigurationMock);
        $reflectionProperty = $reflection->getProperty('loadedData');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->sharedCatalogSynConfigurationMock, $data);

        $this->sharedCatalogSynConfigurationMock->getData();
    }
}
