<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model;

use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationFactory;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfiguration as SharedCatalogSyncQueueConfigurationModel;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * SharedCatalogSyncQueueConfigurationRepository Repository Test
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SharedCatalogSyncQueueConfigurationRepositoryTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $sharedCatalogSyncQueueConfigurationRepository;
    /**
     * @var SharedCatalogSyncQueueConfigurationFactory
     */
    private $sharedCatalogSyncQueueConfigurationFoctoryMock;

    /**
     * @var SharedCatalogSyncQueueConfigurationModel
     */
    private $sharedCatalogSyncQueueConfigurationModelMock;

    /**
     * @var SharedCatalogSyncQueueConfiguration
     */
    private $sharedCatalogSyncQueueConfigurationResourceMock;
    
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

        $this->sharedCatalogSyncQueueConfigurationFoctoryMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogSyncQueueConfigurationModelMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationModel::class)
            ->setMethods(['load', 'getId', 'getIds'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogSyncQueueConfigurationResourceMock = $this->getMockBuilder(SharedCatalogSyncQueueConfiguration::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();
        $this->sharedCatalogSyncQueueConfigurationRepository = $this->objectManager->getObject(
            SharedCatalogSyncQueueConfigurationRepository::class,
            [
                'sharedCatalogSyncQueueConfigurationFactory' => $this->sharedCatalogSyncQueueConfigurationFoctoryMock,
                'sharedCatalogSyncQueueConfiguration' => $this->sharedCatalogSyncQueueConfigurationResourceMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test getById.
     *
     * @return void
     */
    public function testGetById()
    {
        $this->sharedCatalogSyncQueueConfigurationFoctoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sharedCatalogSyncQueueConfigurationModelMock);
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->assertIsObject($this->sharedCatalogSyncQueueConfigurationRepository->getById(1));
    }

    /**
     * Test getById.
     *
     * @return void
     */
    public function testGetByIdWithExeption()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Unable to find catalog sync legacy configuration with ID "1"');

        $this->sharedCatalogSyncQueueConfigurationFoctoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sharedCatalogSyncQueueConfigurationModelMock);
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->sharedCatalogSyncQueueConfigurationRepository->getById(1);
    }

    /**
     * Test geBySharedCatalogId.
     *
     * @return void
     */
    public function testGetBySharedCatalogId()
    {
        $this->sharedCatalogSyncQueueConfigurationFoctoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sharedCatalogSyncQueueConfigurationModelMock);
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('getId')
            ->willReturn(9);

        $this->assertIsObject($this->sharedCatalogSyncQueueConfigurationRepository->getBySharedCatalogId(9));
    }

    /**
     * Test getBySharedCatalogId.
     *
     * @return void
     */
    public function testGetBySharedCatalogIdWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Unable to find catalog sync legacy configuration with ID "9"');

        $this->sharedCatalogSyncQueueConfigurationFoctoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sharedCatalogSyncQueueConfigurationModelMock);
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->sharedCatalogSyncQueueConfigurationModelMock->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->sharedCatalogSyncQueueConfigurationRepository->getBySharedCatalogId(9);
    }
}
