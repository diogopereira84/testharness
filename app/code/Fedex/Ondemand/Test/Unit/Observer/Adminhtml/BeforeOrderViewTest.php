<?php

namespace Fedex\Ondemand\Test\Unit\Observer\Adminhtml;

use Fedex\Ondemand\Observer\Adminhtml\BeforeOrderView;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\GridPool;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreRepository;
use PHPUnit\Framework\TestCase;

class BeforeOrderViewTest extends TestCase
{

    protected $storeFactory;
    protected $store;
    protected $requestInterface;
    protected $orderFactory;
    protected $order;
    protected $gridPool;
    protected $storeRepository;
    protected $observerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $beforeOrderView;
    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeFactory = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->setMethods(['load', 'getId','getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestInterface = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(['load','getStoreId','setStoreId','save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridPool = $this->getMockBuilder(GridPool::class)
            ->setMethods(['refreshByOrderId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeRepository = $this->getMockBuilder(StoreRepository::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);

        $this->beforeOrderView = $this->objectManagerHelper->getObject(
            BeforeOrderView::class,
            [
                'requestInterface' => $this->requestInterface,
                'storeFactory' => $this->storeFactory,
                'storeRepository' => $this->storeRepository,
                'orderFactory' => $this->orderFactory,
                'gridPool' => $this->gridPool,
            ]
        );
    }

    public function testExecute()
    {
        $this->requestInterface->expects($this->any())->method('getParam')->willReturn(23);
        $this->storeFactory->expects($this->any())->method('create')->willReturn($this->store);
        $this->store->expects($this->any())->method('load')->willReturnSelf();
        $this->store->expects($this->any())->method('getId')->willReturn(108);
        $this->storeRepository->expects($this->any())->method('getList')->willReturn([$this->store]);
        $this->store->expects($this->any())->method('getData')->willReturn(18);
        $this->orderFactory->expects($this->any())->method('create')->willReturn($this->order);
        $this->order->expects($this->any())->method('load')->willReturnSelf();
        $this->order->expects($this->any())->method('getStoreId')->willReturn('20');
        $this->order->expects($this->any())->method('setStoreId')->willReturnSelf();
        $this->order->expects($this->any())->method('save')->willReturnSelf();
        $this->gridPool->expects($this->any())->method('refreshByOrderId')->willReturnSelf();
        $this->assertNull($this->beforeOrderView->execute($this->observerMock));
    }
}
