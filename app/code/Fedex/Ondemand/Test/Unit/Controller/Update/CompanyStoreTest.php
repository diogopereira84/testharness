<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Controller\Update;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\Group;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\Ondemand\Controller\Update\CompanyStore;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreFactory;

class CompanyStoreTest extends TestCase
{
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    protected $moduleDataSetupInterfaceMock;
    protected $adapterInterfaceMock;
    protected $loggerMock;
    /**
     * @var (\Fedex\Ondemand\Test\Unit\Controller\Update\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfigMock;
    protected $eventManager;
    protected $storeFactory;
    protected $store;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $companyStoreMock;
    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeGroupFactoryMock  = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeGroupMock  = $this->getMockBuilder(Group::class)
            ->setMethods(['load', 'getDefaultStoreId', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleDataSetupInterfaceMock  = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['startSetup', 'getTable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->adapterInterfaceMock  = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['update'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeFactory = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->companyStoreMock = $this->objectManagerHelper->getObject(
            CompanyStore::class,
            [
                    'groupFactory' => $this->storeGroupFactoryMock,
                    'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                    'logger' => $this->loggerMock,
                    'storeFactory' => $this->storeFactory,
                    'eventManager' => $this->eventManager
                ]
        );
    }

    /**
     * testExecute
     */
    public function testExecute()
    {
        $companyAdditionalDataTable = 'company_additional_data';
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $this->storeFactory->expects($this->any())->method('create')->willReturn($this->store);
        $this->store->expects($this->any())->method('load')->willReturnSelf();
        $this->eventManager->expects($this->any())->method('dispatch')->willReturnSelf();
        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getId')->willReturn(2);
        $this->storeGroupMock->expects($this->any())->method('getDefaultStoreId')->willReturn(20);


        $this->moduleDataSetupInterfaceMock->expects($this->any())
            ->method('getTable')->willReturn($companyAdditionalDataTable);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('update')->willReturn(3);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertNull($this->companyStoreMock->execute());
    }

    public function testExecuteWithException()
    {
        $companyAdditionalDataTable = 'company_additional_data';
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $this->storeFactory->expects($this->any())->method('create')->willReturn($this->store);
        $this->store->expects($this->any())->method('load')->willReturnSelf();
        $this->eventManager->expects($this->any())->method('dispatch')->willReturnSelf();
        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getId')->willReturn(2);
        $this->storeGroupMock->expects($this->any())->method('getDefaultStoreId')->willReturn(20);


        $this->moduleDataSetupInterfaceMock->expects($this->any())
            ->method('getTable')->willReturn($companyAdditionalDataTable);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);

        $exception = new \Exception();
        $this->adapterInterfaceMock->expects($this->any())->method('update')->willThrowException($exception);
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertNull($this->companyStoreMock->execute());
    }
}
