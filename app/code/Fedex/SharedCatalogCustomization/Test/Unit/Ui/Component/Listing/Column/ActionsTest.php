<?php
/**
 * Copyright © FeDEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Fedex\SharedCatalogCustomization\Api\Data\SharedCatalogSyncQueueConfigurationInterface;
use Psr\Log\LoggerInterface;
use Fedex\SharedCatalogCustomization\Ui\Component\Listing\Column\Actions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionsTest extends TestCase
{
    /**
     * @var (\Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $sharedCatalogConfigFactoryMock;
    /**
     * @var ContextInterface|MockObject
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    protected $uiComponentFactory;

    /**
     * @var SharedCatalogSyncQueueConfigurationFactory|MockObject
     */
    protected $sharedCatalogConfigFactory;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var SharedCatalogSyncQueueConfigurationRepository|MockObject
     */
    protected $sharedCatalogSyncQueueConfigurationRepositoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var Actions|MockObject
     */
    protected $actionsMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockForAbstractClass(ContextInterface::class);
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->sharedCatalogConfigFactoryMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBySharedCatalogId'])
            ->getMock();
        $this->uiComponentFactory = $this->createMock(UiComponentFactory::class);
        $processor = $this->getMockBuilder(Processor::class)
            ->addMethods(['getProcessor'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->loggerMock = $this
            ->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->never())->method('getProcessor')->willReturn($processor);
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);

        $objectManager = new ObjectManager($this);

        $this->actionsMock = $objectManager->getObject(
            Actions::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlBuilder' => $this->urlBuilder,
                'sharedCatalogConfRepository' => $this->sharedCatalogSyncQueueConfigurationRepositoryMock,
                'toggleConfig' => $this->toggleConfigMock,
                'logger' => $this->loggerMock,
                'components' => [],
                'data' => [],
                'editUrl' => '',
            ]
        );
    }

    /**
     * Test for method prepareDataSource
     */
    public function testPrepareDataSource()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(0);

        $sharedCatalogSyncQueueConfigurationInterfaceMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock->expects($this->any())
            ->method('getBySharedCatalogId')
            ->with(1)
            ->willReturn($sharedCatalogSyncQueueConfigurationInterfaceMock);
        
        $dataSource['data']['items']['item'] = [SharedCatalogInterface::SHARED_CATALOG_ID => 1, 'name' => 'test', SharedCatalogInterface::CUSTOMER_GROUP_ID => 1, 'legacy_catalog_root_folder_id' => 'legacyCatalog123'] ;
        $this->actionsMock->prepareDataSource($dataSource);
    }

    /**
     * Test for method prepareDataSource with toggle
     */
    public function testPrepareDataSourceWithToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $sharedCatalogSyncQueueConfigurationInterfaceMock = $this->getMockBuilder(SharedCatalogSyncQueueConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock->expects($this->any())
            ->method('getBySharedCatalogId')
            ->with(1)
            ->willReturn($sharedCatalogSyncQueueConfigurationInterfaceMock);

        $dataSource['data']['items']['item'] = [SharedCatalogInterface::SHARED_CATALOG_ID => 1, 'name' => 'test', SharedCatalogInterface::CUSTOMER_GROUP_ID => 1, 'legacy_catalog_root_folder_id' => 'legacyCatalog123'] ;
        $this->actionsMock->prepareDataSource($dataSource);
    }

     /**
      * Test for method prepareDataSource toggle Exception
      */
    public function testPrepareDataSourceToggleWithException()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);
        $this->sharedCatalogSyncQueueConfigurationRepositoryMock
            ->expects($this->any())
            ->method('getBySharedCatalogId')
            ->with(1)
            ->willThrowException(new \Magento\Framework\Exception\NoSuchEntityException());

        $dataSource['data']['items']['item'] = [SharedCatalogInterface::SHARED_CATALOG_ID => 1, 'name' => 'test', SharedCatalogInterface::CUSTOMER_GROUP_ID => 1, 'legacy_catalog_root_folder_id' => 'legacyCatalog123'] ;
        $this->actionsMock->prepareDataSource($dataSource);
    }
}
