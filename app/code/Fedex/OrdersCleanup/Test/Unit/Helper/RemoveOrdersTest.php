<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

namespace Fedex\OrdersCleanup\Test\Unit\Helper;

use Fedex\OrdersCleanup\Helper\RemoveOrders;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PHPUnit\Framework\TestCase;

class RemoveOrdersTest extends TestCase
{

    protected $orderInterfaceMock;
    protected $orderCollection;
    protected $orderCollectionFactoryMock;
    protected $orderResourceFactoryMock;
    protected $moduleConfigMock;
    protected $orderRepositoryMock;
    protected $connectionMock;
    protected $objectManager;
    protected $removeOrdersMock;
    protected function setUp(): void
    {
        $this->orderInterfaceMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->getMockForAbstractClass();

        $this->orderCollection = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderResourceFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->addMethods(['getConnection','getTable'])
            ->getMock();

        $this->moduleConfigMock = $this->getMockBuilder(\Fedex\OrdersCleanup\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepositoryMock = $this->getMockBuilder(\Magento\Sales\Model\OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->removeOrdersMock = $this->objectManager->getObject(
            RemoveOrders::class,
            [
                'orderCollectionFactory' => $this->orderCollectionFactoryMock,
                'orderResourceFactory' => $this->orderResourceFactoryMock,
                'moduleConfig' => $this->moduleConfigMock,
                'orderRepository' => $this->orderRepositoryMock
            ]
        );
    }

    public function testRemoveOrdersDisabled()
    {
        $this->moduleConfigMock->expects($this->any())->method('isRemoveEnabled')->willReturn(false);
        $this->assertEmpty($this->removeOrdersMock->removeOrders('2023-11-01', 12));
    }

    public function testRemoveOrdersNoOrders()
    {
        $this->moduleConfigMock->expects($this->any())->method('isRemoveEnabled')->willReturn(true);
        $date = '2024-12-05';
        $this->orderCollectionFactoryMock->expects($this->any())->method('create')->willReturn($this->orderCollection);
        $this->orderCollection->expects($this->any())->method('getSize')->willReturn(0);
        $this->assertEmpty($this->removeOrdersMock->removeOrders($date));
    }

    public function testDeleteRecordSuccessfully()
    {
        $orderId = 1;
        $connection = $this->createMock(AdapterInterface::class);
        $resource = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->orderResourceFactoryMock->method('create')->willReturn($resource);
        $resource->method('getConnection')->willReturn($connection);
        $connection->expects($this->exactly(4))->method('delete');
        $this->removeOrdersMock->deleteRecord($orderId);
    }

    public function testDeleteRecordThrowsException()
    {
        $orderId = 1;
        $connection = $this->createMock(AdapterInterface::class);
        $resource = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->orderResourceFactoryMock->method('create')->willReturn($resource);
        $resource->method('getConnection')->willReturn($connection);
        $connection->method('delete')->willThrowException(new \Exception('Error'));
        $this->expectException(\Exception::class);
        $this->removeOrdersMock->deleteRecord($orderId);
    }

    public function testRemoveOrdersWithNoOrdersToRemove()
    {
        $this->moduleConfigMock->method('isRemoveEnabled')->willReturn(true);
        $this->moduleConfigMock->method('getTerminateLimit')->willReturn(10);
        $this->moduleConfigMock->method('getLoggedInUsersRetentionDays')->willReturn(6);
        $connection = $this->createMock(AdapterInterface::class);
        $resource = $this->createMock(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->orderResourceFactoryMock->method('create')->willReturn($resource);
        $resource->method('getConnection')->willReturn($connection);
        $this->orderCollectionFactoryMock->method('create')->willReturn($this->orderCollection);
        $this->orderCollection->method('getSize')->willReturn(0);
        $this->orderCollection->method('addFieldToFilter')->willReturnSelf();
        $this->removeOrdersMock->removeOrders('2023-01-01');
        $this->assertEmpty($this->removeOrdersMock->errorOrders);
    }

}
