<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Psr\Log\LoggerInterface;
use Fedex\SharedCatalogCustomization\Helper\Data;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\Collection;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Fedex\SharedCatalogCustomization\Cron\CatalogSyncQueueProcessCron;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

//use Magento\Framework\DataObject;

class CatalogSyncQueueProcessCronTest extends TestCase
{
    protected $catalogSyncCollectionMock;
    protected $catalogSyncCollectionFactoryMock;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $catalogSyncQueueFactoryMock;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataHelperMock;
    protected $toggleConfigMock;
    protected $catalogSyncQueueProcessCronMock;
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';

	/**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CatalogSyncQueueFactory
     */
    protected $catalogSyncQueueFactory;

    /**
     * @var Data
     */
    protected $catalogSyncHelperData;

    /**
     * @var CollectionFactory
     */
    protected $catalogSyncCollectionFactory;

    /**
     * @var ManageCatalogItems
     */
    protected $manageCatalogItems;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
		$this->objectManager = new ObjectManager($this);

        $this->catalogSyncCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'getSize', 'getFirstItem'])
            ->getMock();
    
        $this->catalogSyncCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->catalogSyncQueueFactoryMock = $this->getMockBuilder(CatalogSyncQueueFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

       	$this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
			->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->catalogSyncQueueProcessCronMock = $this->objectManager->getObject(
            CatalogSyncQueueProcessCron::class,
            [
                'catalogSyncQueueFactory' => $this->catalogSyncQueueFactoryMock,
                'helperData' => $this->dataHelperMock,
                'catalogSyncCollectionFactory' => $this->catalogSyncCollectionFactoryMock,
                'toggleConfig' => $this->toggleConfigMock
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
        $this->catalogSyncCollectionMock->expects(static::any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        
        $this->catalogSyncCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->catalogSyncCollectionMock);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->assertNull($this->catalogSyncQueueProcessCronMock->execute());
	}
}
