<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\Shipment\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Model\RetryShipmentCreationListener;
use Magento\Framework\App\RequestInterface;
use Fedex\Shipment\Helper\ShipmentEmail;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Test class for Fedex\Shipment\Model\RetryShipmentCreationListener
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class RetryShipmentCreationListenerTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $request;
    protected $orderRepository;
    protected $submitOrderHelper;
    protected $orderModel;
    protected $quoteFactory;
    protected $quoteMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    public const ORDER_ID = 12;
    public const QUOTE_ID = 10;

    /** @var RetryShipment|MockObject */
    protected $retryShipmentObject;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
            
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['getContent'])
                ->getMockForAbstractClass();
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
                ->disableOriginalConstructor()
                ->setMethods(['get'])
                ->getMockForAbstractClass();
        $this->submitOrderHelper = $this->createMock(SubmitOrderHelper::class);
        $this->orderModel = $this->getMockBuilder(Order::class)
                ->setMethods(['load', 'getQuoteId'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
                ->setMethods(['load', 'create'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
                ->setMethods(['load'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->retryShipmentObject = $this->objectManager->getObject(
            RetryShipmentCreationListener::class,
            [
                'orderRepository'   => $this->orderRepository,
                'submitOrderHelper' => $this->submitOrderHelper,
                'quoteFactory'      => $this->quoteFactory,
                'logger'            => $this->logger
            ]
        );
    }

    /**
     * Test testRetryShipment.
     */
    public function testRetryShipment()
    {
        $counter = 0;
        $messageRequest = ['orderId' => static::ORDER_ID, 'counter' => $counter];
        $messageRequest = json_encode($messageRequest);

        $this->request->expects($this->any())->method('getContent')->willReturn($messageRequest);
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->orderModel);
        $this->orderModel->expects($this->any())->method('getQuoteId')->willReturn(static::QUOTE_ID);
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->with(static::QUOTE_ID)->willReturnSelf();

        $this->submitOrderHelper->expects($this->any())->method('createShipment')
            ->with($this->quoteMock, static::ORDER_ID)->willReturn(true);
        
        $this->assertTrue($this->retryShipmentObject->retryShipmentCreation($messageRequest));
    }

    /**
     * Test testRetryShipment.
     */
    public function testRetryShipmentWithCounterWithCounter()
    {
        $counter = 0;
        $messageRequest = ['orderId' => static::ORDER_ID, 'counter' => $counter];
        $messageRequest = json_encode($messageRequest);

        $this->request->expects($this->any())->method('getContent')->willReturn($messageRequest);
        $this->orderRepository->expects($this->any())->method('get')->willReturn($this->orderModel);
        $this->orderModel->expects($this->any())->method('getQuoteId')->willReturn(static::QUOTE_ID);
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->with(static::QUOTE_ID)->willReturnSelf();

        $this->submitOrderHelper->expects($this->any())->method('createShipment')
            ->with($this->quoteMock, static::ORDER_ID)->willReturn(false);
        
        $this->assertTrue($this->retryShipmentObject->retryShipmentCreation($messageRequest));
    }

    /**
     * test method for RetryShipmentWithException
     */
    public function testRetryShipmentWithException()
    {
        $counter = 0;
        $messageRequest = ['orderId' => static::ORDER_ID, 'counter' => $counter];
        $messageRequest = json_encode($messageRequest);
        $this->request->expects($this->any())->method('getContent')->willReturn($messageRequest);
        $this->orderRepository->expects($this->any())->method('get')->willThrowException(new \Exception());

        $this->assertNull($this->retryShipmentObject->retryShipmentCreation($messageRequest));
    }
}
