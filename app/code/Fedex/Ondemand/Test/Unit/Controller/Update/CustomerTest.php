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
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\Store;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fedex\Ondemand\Controller\Update\Customer;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\Indexer;

class CustomerTest extends TestCase
{
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    protected $storeFactoryMock;
    protected $storeMock;
    protected $moduleDataSetupInterfaceMock;
    protected $adapterInterfaceMock;
    protected $loggerMock;
    protected $jsonHelperMock;
    protected $indexerFactoryMock;
    protected $indexerMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $customerMock;
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
            ->setMethods(['load', 'getGroupId', 'getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeFactoryMock  = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeMock  = $this->getMockBuilder(Store::class)
            ->setMethods(['load', 'getName'])
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

        $this->jsonHelperMock  = $this->getMockBuilder(Data::class)
            ->setMethods(['jsonEncode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerFactoryMock  = $this->getMockBuilder(IndexerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerMock  = $this->getMockBuilder(Indexer::class)
            ->setMethods(['load', 'reindexAll', 'reindexRow'])
            ->disableOriginalConstructor()
            ->getMock();


        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->customerMock = $this->objectManagerHelper->getObject(
            Customer::class,
            [
                    'groupFactory' => $this->storeGroupFactoryMock,
                    'storeFactory' => $this->storeFactoryMock,
                    'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                    'indexerFactory' => $this->indexerFactoryMock,
                    'logger' => $this->loggerMock,
                    'jsonHelper' => $this->jsonHelperMock,
                ]
        );
    }

    /**
     * testExecute
     */
    public function testExecute()
    {
        $tables = ['customer_entity'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->storeFactoryMock->expects($this->any())->method('create')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeMock->expects($this->any())->method('getName')->willReturn('Ondemand');

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getTable')->willReturn('customer_entity');
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('update')->willReturn(3);

        $this->jsonHelperMock->expects($this->any())->method('jsonEncode')
            ->willReturn('{"customer_entity":0}');

        $this->indexerFactoryMock->expects($this->any())->method('create')->willReturn($this->indexerMock);
        $this->indexerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->indexerMock->expects($this->any())->method('reindexAll')->willReturnSelf();
        $this->indexerMock->expects($this->any())->method('reindexRow')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertNull($this->customerMock->execute());
    }

    /**
     * testExecuteWithExecption
     */
    public function testExecuteWithExecption()
    {
        $tables = ['customer_entity'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $exception = new \Exception();

        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->storeFactoryMock->expects($this->any())->method('create')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeMock->expects($this->any())->method('getName')->willReturn('Ondemand');

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getTable')->willReturn('customer_entity');
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('update')->willThrowException($exception);
        $this->assertNull($this->customerMock->execute());
    }
}
