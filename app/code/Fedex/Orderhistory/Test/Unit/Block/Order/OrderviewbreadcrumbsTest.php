<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Orderhistory\Block\Order\Orderviewbreadcrumbs;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Status\History\Collection;
use Magento\Sales\Model\Order\Status\History;

class OrderviewbreadcrumbsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $registry;
    /**
     * @var (\Magento\Sales\Api\OrderRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderRepository;
    protected $storeManager;
    protected $storeMock;
    protected $helper;
    protected $Layout;
    protected $AbstractBlock;
    protected $breadcrumbMock;
    protected $order;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $OrderviewbreadcrumbsMock;
    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this
            ->getMockBuilder(Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

            $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getBaseUrl'])
            ->getMockForAbstractClass();

        $this->helper = $this
            ->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled','isPrintReceiptRetail'])
            ->disableOriginalConstructor()
            ->getMock();

            $this->Layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBlock','addCrumb','toHtml'])
            ->getMockForAbstractClass();

            $this->AbstractBlock = $this->getMockBuilder(\Magento\Framework\View\Element\AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayout'])
            ->getMockForAbstractClass();

            $this->breadcrumbMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['addCrumb'])
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatusHistoryCollection'])
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->OrderviewbreadcrumbsMock = $this->objectManager->getObject(
            Orderviewbreadcrumbs::class,
            [
                'context' => $this->contextMock,
                'helper' => $this->helper,
                'registry'=> $this->registry,
                'storeManager' => $this->storeManager,
                '_layout' => $this->Layout,
                'order' => $this->order
            ]
        );
    }

    /**
     * Assert getOrderviewbreadcrumbs When Module is enambled.
     *
     */
    public function testGetOrderviewbreadcrumbs()
    {
        $baseUrl = 'base-url';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->registry->expects($this->any())->method('registry')->with('current_order')->willReturn($order);

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);

        $this->AbstractBlock->expects($this->any())->method('getLayout')->willReturn($this->Layout);
        $this->Layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->breadcrumbMock->expects($this->any())
            ->method('addCrumb')
            ->willReturnMap([
                [
                    'myoreder',
                    [
                        'label' => __('My Orders'),
                        'title' => __('My Orders'),
                        'link' => $baseUrl . 'sales/order/history',
                    ],
                    $this->breadcrumbMock,
                ],
                [
                    'orderid',
                    [
                        'label' => 'Order #',
                        'title' => 'Order #',
                    ],
                    $this->breadcrumbMock,
                ],
            ]);

        $this->assertEquals(null, $this->OrderviewbreadcrumbsMock->getOrderviewbreadcrumbs());
    }
    /**
     * Assert getOrderviewbreadcrumbs when Marketplace Module is enabled.
     *
     */
    public function testGetOrderviewbreadcrumbsMarketplace()
    {
        $baseUrl = 'base-url';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->registry->expects($this->any())->method('registry')->with('current_order')->willReturn($order);

        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helper->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);

        $this->AbstractBlock->expects($this->any())->method('getLayout')->willReturn($this->Layout);
        $this->Layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->breadcrumbMock->expects($this->any())
            ->method('addCrumb')
            ->willReturnMap([
                [
                    'myoreder',
                    [
                        'label' => __('Home'),
                        'title' => __('Home'),
                        'link' => $baseUrl . 'sales/order/history',
                    ],
                    $this->breadcrumbMock,
                ],
                [
                    'orderid',
                    [
                        'label' => 'Order #',
                        'title' => 'Order #',
                    ],
                    $this->breadcrumbMock,
                ],
            ]);

        $this->assertEquals(null, $this->OrderviewbreadcrumbsMock->getOrderviewbreadcrumbs());
    }

    /**
     * Assert getOrderviewbreadcrumbs When Module is enambled.
     *
     */
    public function testGetOrderviewbreadcrumbsdisabled()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $baseUrl = 'base-url';
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->registry->expects($this->any())->method('registry')->with('current_order')->willReturn($order);
        $this->AbstractBlock->expects($this->any())->method('getLayout')->willReturn($this->Layout);
        $this->Layout->expects($this->any())->method('getBlock')->with('breadcrumbs')->willReturnSelf();
        $this->breadcrumbMock->expects($this->any())
            ->method('addCrumb')
            ->willReturnMap([
                [
                    'myoreder',
                    [
                        'label' => __('My Orders'),
                        'title' => __('My Orders'),
                        'link' => $baseUrl.'sales/order/history',
                    ],
                    $this->breadcrumbMock,
                ],
                [
                    'orderid',
                    [
                        'label' => 'Order #',
                        'title' => 'Order #',
                    ],
                    $this->breadcrumbMock,
                ],
            ]);

        //$this->OrderviewbreadcrumbsMock->getOrderviewbreadcrumbs();
        $this->assertEquals(null, $this->OrderviewbreadcrumbsMock->getOrderviewbreadcrumbs());
    }

    /**
     * Assert getBaseUrl - B-1053021
     *
     */
    public function testGetBaseUrl()
    {

        $baseUrl = 'base-url';
        $this->storeManager->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->assertEquals($baseUrl, $this->OrderviewbreadcrumbsMock->getBaseUrl());
    }

    /**
     * Test isOrderApprovalB2bEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bEnabled()
    {
        $this->assertNull($this->OrderviewbreadcrumbsMock->isOrderApprovalB2bEnabled());
    }

    /**
     * Test getOrderInfo
     *
     * @return void
     */
    public function testGetOrderInfo()
    {
        $this->assertNull($this->OrderviewbreadcrumbsMock->getOrderInfo());
    }

    /**
     * Test checkIsReviewActionSet
     *
     * @return void
     */
    public function testCheckIsReviewActionSet()
    {
        $this->assertNull($this->OrderviewbreadcrumbsMock->checkIsReviewActionSet());
    }

    /**
     * Test getDeclineDateWithNoStatusHistory
     *
     * @return void
     */
    public function testGetDeclineDateWithNoStatusHistory()
    {
        $statusHistoryCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statusHistoryCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->order->expects($this->once())
            ->method('getStatusHistoryCollection')
            ->willReturn($statusHistoryCollectionMock);

        $this->registry->expects($this->any())->method('registry')
            ->with('current_order')->willReturn($this->order);

        $this->assertNotNull($this->OrderviewbreadcrumbsMock->getDeclineDate());
    }

    /**
     * Test GetDeclineDateWithDeclinedStatus
     *
     * @return void
     */
    public function testGetDeclineDateWithDeclinedStatus()
    {
        $statusHistoryMock = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $statusHistoryMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('declined');
            
        $statusHistoryMock->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn('2024-06-17 12:00:00');
            
        $statusHistoryCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statusHistoryCollectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$statusHistoryMock]);
            
        $this->order->expects($this->once())
            ->method('getStatusHistoryCollection')
            ->willReturnSelf();
        
        $this->registry->expects($this->once())->method('registry')
            ->with('current_order')
            ->willReturn($this->order);

        $this->assertNotEquals('2024-06-17 12:00:00', $this->OrderviewbreadcrumbsMock->getDeclineDate());
    }
}
