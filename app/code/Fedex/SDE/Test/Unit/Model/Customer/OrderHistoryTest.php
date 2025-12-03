<?php

namespace Fedex\SDE\Test\Unit\Model\Customer;

use Fedex\SDE\Model\Customer\OrderHistory;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Fedex\Shipment\Api\NewOrderUpdateInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class OrderHistoryTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManagerInstance;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $localeDateMock;
    protected $customerSessionMock;
    protected $orderHistoryDataHelperMock;
    protected $contextMock;
    protected $registryMock;
    protected $orderCollectionFactoryMock;
    protected $orderHistoryMock;
    protected $orderCollectionMock;

    protected function setUp(): void
    {
        $this->localeDateMock = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'convertConfigTimeToUtc', 'format', 'sub'])
            ->getMockForAbstractClass();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getId'])
            ->getMock();
        $this->orderHistoryDataHelperMock = $this->getMockBuilder(OrderHistoryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSDEHomepageEnable'])
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCollectionFactoryMock =  $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->orderCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count'])
            ->getMock();
        $this->objectManagerInstance = \Magento\Framework\App\ObjectManager::getInstance();

        $this->objectManager = new ObjectManager($this);

        $this->orderHistoryMock = $this->objectManager->getObject(
            OrderHistory::class,
            [
                'orderHistoryDataHelper' => $this->orderHistoryDataHelperMock,
                'customerSession' => $this->customerSessionMock,
                'localeDate' => $this->localeDateMock,
                'orderCollectionFactory' => $this->orderCollectionFactoryMock,
                '_registry' => $this->registryMock,
                'context' => $this->contextMock,
            ]
        );
    }
    /**
     * Test function for getOrderCollectionCount
     */
    public function testGetOrderCollectionCountWithoutStatus()
    {
        $this->orderHistoryDataHelperMock->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(1);
        $this->localeDateMock->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDateMock->expects($this->any())->method('convertConfigTimeToUtc')->willReturnSelf();
        $this->localeDateMock->expects($this->any())->method('format')->willReturnSelf();
        $this->localeDateMock->expects($this->any())->method('sub')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);


        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->orderCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->any())
            ->method('count')
            ->willReturn(2);
        $this->assertEquals('2', $this->orderHistoryMock->getOrderCollectionCount());
    }
    /**
     * Test function for getOrderCollectionCount
     */
    public function testGetOrderCollectionCountWithStatus()
    {
        $status = [NewOrderUpdateInterface::INPROCESS];
        $this->orderHistoryDataHelperMock->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(1);
        $this->localeDateMock->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDateMock->expects($this->any())->method('convertConfigTimeToUtc')->willReturnSelf();
        $this->localeDateMock->expects($this->any())->method('format')->willReturnSelf();
        $this->localeDateMock->expects($this->any())->method('sub')->willReturnSelf();
        $this->customerSessionMock->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);


        $this->customerSessionMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->orderCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturn($this->orderCollectionMock);
        $this->orderCollectionMock->expects($this->any())
            ->method('count')
            ->willReturn(2);
        $this->assertEquals('2', $this->orderHistoryMock->getOrderCollectionCount($status));
    }
    /**
     * Test function for getSubmittedOrderCount
     */
    public function testGetSubmittedOrderCount()
    {
        $orderCount = 2;
        $this->testGetOrderCollectionCountWithoutStatus();
        $this->assertEquals($orderCount, $this->orderHistoryMock->getSubmittedOrderCount());
    }
    /**
     * Test function for getInProgressOrderCount
     */
    public function testGetInProgressOrderCount()
    {
        $status = [NewOrderUpdateInterface::INPROCESS];
        $orderCount = 2;
        $this->testGetOrderCollectionCountWithStatus();
        $this->assertEquals($orderCount, $this->orderHistoryMock->getInProgressOrderCount($status));
    }
    /**
     * Test function for getCompletedOrderCount
     */
    public function testGetCompletedOrderCount()
    {
        $status = [
            NewOrderUpdateInterface::READYFORPICKUP,
            NewOrderUpdateInterface::SHIPPED,
            NewOrderUpdateInterface::COMPLETE,
        ];
        $orderCount = 2;
        $this->testGetOrderCollectionCountWithStatus();
        $this->assertEquals($orderCount, $this->orderHistoryMock->getCompletedOrderCount($status));
    }
    /**
     * Test function for getOrderCountForHomepage
     */
    public function testGetOrderCountForHomepage()
    {
        $orderCountData = ['submitted' => '2', 'progress' => '2', 'completed' => '2'];
        $this->testGetSubmittedOrderCount();
        $this->testGetInProgressOrderCount();
        $this->testGetCompletedOrderCount();
        $this->assertEquals($orderCountData, $this->orderHistoryMock->getOrderCountForHomepage());
    }
}
