<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Controller\Adminhtml\SharedCatalogSyncQueue;

use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationFactory;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfiguration;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\CollectionFactory as ConfigurationCollectionFactory;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\Collection as ConfigurationCollection;
use Fedex\SharedCatalogCustomization\Controller\Adminhtml\SharedCatalogSyncQueue\SaveConfiguration;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * SaveConfiguration Controller Test
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveConfigurationTest extends TestCase
{
    protected $sharedCatalogSyncQueueConfigurationFactoryMock;
    protected $sharedCatalogSyncQueueConfigurationRepositoryMock;
    /**
     * @var (\Fedex\SharedCatalogCustomization\Model\ResourceModel\SharedCatalogSyncQueueConfiguration\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configurationCollectionFactoryMock;
    protected $requestMock;
    protected $configurationCollection;
    protected $sharedCatalogSyncQueueConfiguration;
    protected $resultRedirectFactory;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $saveConfiguration;
    /**
     * @var SharedCatalogSyncQueueConfigurationFactory
     */
    protected $sharedCatalogConfigFactory;

    /**
     * @var SharedCatalogSyncQueueConfigurationRepository
     */
    protected $sharedCatalogConfigRepository;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sharedCatalogSyncQueueConfigurationFactoryMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getById',
                    'save'
                ]
            )
            ->getMock();
        $this->configurationCollectionFactoryMock = $this->getMockBuilder(ConfigurationCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->configurationCollection = $this->getMockBuilder(ConfigurationCollection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'addFieldToFilter',
                'getFirstItem',
                'getId'
            ])
            ->getMock();
        $this->sharedCatalogSyncQueueConfiguration = $this->getMockBuilder(SharedCatalogSyncQueueConfiguration::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCollection'
                ])
            ->getMock();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory =
            $this->getMockBuilder(\Magento\Framework\Controller\Result\RedirectFactory::class)
            ->setMethods(['create', 'setRefererOrBaseUrl'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
			->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);

        $this->saveConfiguration = $this->objectManager->getObject(
            SaveConfiguration::class,
            [
                'request' => $this->requestMock,
                'sharedCatSynConfigFactory' => $this->sharedCatalogSyncQueueConfigurationFactoryMock,
                'sharedCatalogConfigRepository' => $this->sharedCatalogSyncQueueConfigurationRepositoryMock,
                'sharedCatalogSyncConfcollectionFactory' => $this->configurationCollectionFactoryMock,
                '_request' => $this->requestMock,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test execute
     */
    public function testExecute()
    {
        $formData = [
                        'catalog_sync_config' =>
                            [
                                'shared_catalog_id' => 9,
                                'legacy_catalog_root_folder_id' => 'lefgacyfolderid432',
                                'category_id' => 99,
                                'status' => 1
                            ]
                    ];

        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($formData);
        $this->sharedCatalogSyncQueueConfigurationFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sharedCatalogSyncQueueConfiguration);
        $this->sharedCatalogSyncQueueConfiguration->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->configurationCollection);
        $this->configurationCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with("shared_catalog_id", 9)->willReturnSelf();
        $this->configurationCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf();
        $this->configurationCollection->expects($this->any())
            ->method('getId')
            ->willReturn(9);
        $sharedCatalogSyncQueueConfigurationInterfaceMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock->expects($this->any())
            ->method('getById')
            ->with(9)
            ->willReturn($sharedCatalogSyncQueueConfigurationInterfaceMock);
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock->expects($this->any())
            ->method('save')
            ->with($sharedCatalogSyncQueueConfigurationInterfaceMock)
            ->willReturn($this->sharedCatalogSyncQueueConfiguration);
        $this->messageManager->expects($this->any())
            ->method('addSuccessMessage')
            ->with("Configuration data updated successfully.")
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())
            ->method('setRefererOrBaseUrl')
            ->willReturnSelf();

        $this->saveConfiguration->execute();
    }

    /**
     * Test execute Null Data
     */
    public function testExecuteNullData()
    {
        $formData = [
            'catalog_sync_config' =>
                [
                    'shared_catalog_id' => 9,
                    'legacy_catalog_root_folder_id' => 'lefgacyfolderid432',
                    'category_id' => 99,
                    'status' => 1
                ]
        ];

        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($formData);
        $this->sharedCatalogSyncQueueConfigurationFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->sharedCatalogSyncQueueConfiguration);
        $this->sharedCatalogSyncQueueConfiguration->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->configurationCollection);
        $this->configurationCollection->expects($this->once())
            ->method('addFieldToFilter')
            ->with("shared_catalog_id", 9)
            ->willReturnSelf();
        $this->configurationCollection->expects($this->any())
            ->method('getFirstItem')
            ->willReturnSelf();
        $this->configurationCollection->expects($this->any())
            ->method('getId')
            ->willReturn(null);
        $sharedCatalogSyncQueueConfigurationInterfaceMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock->expects($this->any())
            ->method('getById')
            ->with(9)
            ->willReturn($sharedCatalogSyncQueueConfigurationInterfaceMock);
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock->expects($this->any())
            ->method('save')
            ->with($sharedCatalogSyncQueueConfigurationInterfaceMock)
            ->willReturn($this->sharedCatalogSyncQueueConfiguration);
        $this->messageManager->expects($this->any())
            ->method('addSuccessMessage')
            ->with("Configuration data updated successfully.")
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->any())
            ->method('setRefererOrBaseUrl')
            ->willReturnSelf();

        $this->saveConfiguration->execute();
    }
}
