<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Observer\NoCustomerPaymentObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class NoCustomerPaymentObserverTest extends TestCase
{
    /**
     * @var object|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfigMock;

    /**
     * @var object|\PHPUnit\Framework\MockObject\MockObject
     */
    private $observer;

    /**
     * @var object|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventMock;

    /**
     * @var object|\PHPUnit\Framework\MockObject\MockObject
     */
    private $createOrderMock;

    /**
     * @var NoCustomerPaymentObserver
     */
    private NoCustomerPaymentObserver $noCustomerPaymentObserver;

    /**
     * Sets up the test environment before each test method is executed.
     *
     * This method is used to initialize objects and prepare the state required
     * for each unit test in the test class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->noCustomerPaymentObserver = new NoCustomerPaymentObserver($this->toggleConfigMock);

        $this->createOrderMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['setPaymentWorkflow'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCreateOrder'])
            ->getMock();
        $this->eventMock->expects($this->once())
            ->method('getCreateOrder')
            ->willReturn($this->createOrderMock);

        $this->observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEvent'])
            ->getMock();
        $this->observer->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->eventMock);
    }

    /**
     * Test that the execute method sets the "no payment" workflow.
     *
     * This unit test verifies that when the execute method is triggered,
     * the observer correctly applies the "no payment" workflow scenario.
     *
     * @return void
     */
    public function testExecuteSetsNoPaymentWorkflow(): void
    {
        $this->createOrderMock->expects($this->once())
            ->method('setPaymentWorkflow')
            ->with(NoCustomerPaymentObserver::NO_PAYMENT_CODE);

        $this->noCustomerPaymentObserver->execute($this->observer);
    }
}
