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
use Fedex\Ondemand\Controller\Update\Quote;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;

class QuoteTest extends TestCase
{
    protected $storeGroupFactoryMock;
    protected $storeGroupMock;
    protected $storeFactory;
    protected $store;
    protected $moduleDataSetupInterfaceMock;
    protected $adapterInterfaceMock;
    protected $loggerMock;
    protected $jsonHelperMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $quoteMock;
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

        $this->storeFactory  = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->store  = $this->getMockBuilder(Store::class)
            ->setMethods(['load','getId'])
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

        $this->quoteMock = $this->objectManagerHelper->getObject(
            Quote::class,
            [
                    'groupFactory' => $this->storeGroupFactoryMock,
                    'storeFactory' => $this->storeFactory,
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
        $tables = ['negotiable_quote_grid', 'quote', 'quote_address_item', 'quote_item', 'quote_integration'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $this->storeFactory->expects($this->any())->method('create')->willReturn($this->store);
        $this->store->expects($this->any())->method('load')->willReturnSelf();
        $this->store
            ->method('getId')
            ->withConsecutive(
                [],
                [],
                []
            )
            ->willReturnOnConsecutiveCalls(
                1,
                285,
                45
            );

        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getTable')
                ->willReturn('negotiable_quote_grid');
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('update')->willReturn(3);

        $this->jsonHelperMock->expects($this->any())->method('jsonEncode')
            ->willReturn('{"negotiable_quote_grid":0,"quote":0,"quote_address_item":0}');

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertNull($this->quoteMock->execute());
    }

    /**
     * testExecuteWithExecption
     */
    public function testExecuteWithExecption()
    {
        $tables = ['negotiable_quote_grid', 'quote', 'quote_address_item', 'quote_item', 'quote_integration'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $this->storeFactory->expects($this->any())->method('create')->willReturn($this->store);
        $this->store->expects($this->any())->method('load')->willReturnSelf();
        $this->store
            ->method('getId')
            ->withConsecutive(
                [],
                [],
                []
            )
            ->willReturnOnConsecutiveCalls(
                1,
                285,
                45
            );

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];

        $exception = new \Exception();

        $this->storeGroupFactoryMock->expects($this->any())->method('create')->willReturn($this->storeGroupMock);
        $this->storeGroupMock->expects($this->any())->method('load')->willReturnSelf();
        $this->storeGroupMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->storeGroupMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();

        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getTable')
                ->willReturn('negotiable_quote_grid');
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('getConnection')
                ->willReturn($this->adapterInterfaceMock);

        $this->adapterInterfaceMock->expects($this->any())->method('update')->willThrowException($exception);
        $this->assertNull($this->quoteMock->execute());
    }
}
