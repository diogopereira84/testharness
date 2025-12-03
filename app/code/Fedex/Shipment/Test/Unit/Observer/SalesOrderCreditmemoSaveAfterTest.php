<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Test\Unit\Observer;

use Fedex\Shipment\Observer\SalesOrderCreditmemoSaveAfter;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Test class for ShipmentConfig
 */
class SalesOrderCreditmemoSaveAfterTest extends TestCase
{

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManagerHelper;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $salesOrderCreditmemoSaveAfter;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * @var Order|MockObject
     */
    protected $order;

    /**
     * @var Creditmemo|MockObject
     */
    protected $creditMemo;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent', 'getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(["getOrder", 'setState', 'setStatus', 'save', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);

        $this->salesOrderCreditmemoSaveAfter = $this->objectManagerHelper->getObject(
            SalesOrderCreditmemoSaveAfter::class,
            [
                'logger' => $this->loggerMock,
            ]
        );
    }

    /**
     * Test execute function
     */
    public function testExecute()
    {
        $this->observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturnSelf();

        $creditMemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock->expects($this->any())
            ->method('getCreditmemo')
            ->willReturn($creditMemo);

        $creditMemo->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->order->expects($this->any())
            ->method('setState')
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('setStatus')
            ->willReturnSelf();

        $this->order->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->loggerMock->expects($this->any())
            ->method('info')
            ->willReturnSelf();

        $this->assertEquals(null, $this->salesOrderCreditmemoSaveAfter->execute($this->observerMock));
    }

    /**
     * Test execute function with exception
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturnSelf();

        $this->observerMock->expects($this->any())
            ->method('getCreditmemo')
            ->willThrowException($exception);

        $this->assertEquals(null, $this->salesOrderCreditmemoSaveAfter->execute($this->observerMock));
    }
}
