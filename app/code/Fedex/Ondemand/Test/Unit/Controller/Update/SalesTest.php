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
use Fedex\Ondemand\Controller\Update\Sales;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;

class SalesTest extends TestCase
{
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    protected $moduleDataSetupInterfaceMock;
    protected $adapterInterfaceMock;
    protected $loggerMock;
    protected $jsonHelperMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $salesMock;
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

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->salesMock = $this->objectManagerHelper->getObject(
            Sales::class,
            [
                    'groupFactory' => $this->storeGroupFactoryMock,
                    'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
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
        $tables = ['sales_order', 'sales_order_grid', 'sales_order_item'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getTable')->willReturn('sales_order');
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('update')->willReturn(3);

        $this->jsonHelperMock->expects($this->any())->method('jsonEncode')
            ->willReturn('{"sales_order":0,"sales_order_grid":0,"sales_order_item":0}');

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertNull($this->salesMock->execute());
    }

    /**
     * testExecuteWithExecption
     */
    public function testExecuteWithExecption()
    {
        $tables = ['sales_order', 'sales_order_grid', 'sales_order_item'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $exception = new \Exception();

        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getTable')->willReturn('sales_order');
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
            ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('update')->willThrowException($exception);
        $this->assertNull($this->salesMock->execute());
    }
}
