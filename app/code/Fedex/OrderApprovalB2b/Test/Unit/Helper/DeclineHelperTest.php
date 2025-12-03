<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Helper;

use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Fedex\OrderApprovalB2b\Helper\DeclineHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;

/**
 * DeclineHelperTest Class for DeclineHelper class
 */
class DeclineHelperTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderApprovalViewModel;
    protected $adminConfigHelperMock;
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var DeclineHelper $declineHelper
     */
    private $declineHelper;
    
    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $quoteRepository;

    /**
     * @var SubmitOrderModelAPI|MockObject
     */
    protected $submitOrderModelApi;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderApprovalViewModel = $this->getMockBuilder(OrderApprovalViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['b2bOrderSendEmail'])
            ->getMock();

        $this->adminConfigHelperMock = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getId'])
            ->getMockForAbstractClass();
        
        $this->submitOrderModelApi = $this->getMockBuilder(SubmitOrderModelApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateQuoteStatusAndTimeoutFlag'])
            ->getMock();

        $this->declineHelper = $objectManager->getObject(
            DeclineHelper::class,
            [
                'context' => $this->contextMock,
                'logger' => $this->loggerMock,
                'orderRepository' => $this->orderRepositoryMock,
                'orderApprovalViewModal' => $this->orderApprovalViewModel,
                'adminConfigHelper' => $this->adminConfigHelperMock,
                'quoteRepository' => $this->quoteRepository,
                'submitOrderModelApi' => $this->submitOrderModelApi
            ]
        );
    }

    /**
     * Test method for declineOrder function
     *
     * @return void
     */
    public function testDeclinedOrder()
    {
        $orderId = 123;
        $additionalComment = 'Some additional comment';

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('getState')
            ->willReturn(false);
        $orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('pending_approval');
        $orderMock->expects($this->once())
            ->method('setStatus')
            ->with('declined');
        $this->adminConfigHelperMock->method('isB2bDeclineReorderEnabled')->willReturn(true);
        $orderMock->expects($this->once())
            ->method('addStatusHistoryComment')
            ->with($additionalComment);
        $orderMock->expects($this->once())
            ->method('save');
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->quoteRepository->expects($this->once())
            ->method('getId')
            ->willReturn('123');
        $this->loggerMock->expects($this->any())->method('info');

        $admin = $this->adminConfigHelperMock->expects($this->once())
        ->method('getB2bOrderApprovalConfigValue');

        $response = $this->declineHelper->declinedOrder($orderId, $additionalComment);

        $this->assertNotEquals(['success' => true, 'message' => $admin ], $response);
    }

    /**
     * Test method for declineOrder function while approval
     *
     * @return void
     */
    public function testDeclinedOrderWhileApproval()
    {
        $orderId = 123;
        $additionalComment = 'Some additional comment';

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('declined');
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);
        $response = $this->declineHelper->declinedOrder($orderId, $additionalComment);

        $this->assertNotEquals(['success' => true, 'message' => 'test' ], $response);
    }

    /**
     * Test method for declineOrder function with Execption
     *
     * @return void
     */
    public function testExceptionThrown()
    {
        $orderId = 123;
        $additionalComment = 'Some additional comment';
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('declined');
        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $response = $this->declineHelper->declinedOrder($orderId, $additionalComment);

        $this->assertNotEquals(['success' => false, 'message' => 'Order status and comment not save.'], $response);
    }
}
