<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\OrderApprovalB2b\Test\Unit\Cron;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;
use Fedex\OrderApprovalB2b\Cron\OrderSendReminderEmail;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * Class OrderSendReminerEmailTest
 *
 * Unit tests for OrderSendReminderEmail
 */
class OrderSendReminerEmailTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;
    
    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilderMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $timezoneMock;

    /**
     * @var OrderEmailHelper|MockObject
     */
    private $orderEmailHelperMock;

    /**
     * @var OrderSendReminderEmail|MockObject
     */
    private $orderSendReminderEmailMock;

    /**
     * @var SearchCriteriaInterface|MockObject
     */
    private $searchCriteriaMock;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var SearchResultsInterface|MockObject
     */
    private $orderListMock;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getList','save'])
        ->getMockForAbstractClass();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->timezoneMock = $this->getMockBuilder(TimezoneInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
       
        $this->orderEmailHelperMock = $this->getMockBuilder(OrderEmailHelper::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        
        $this->orderListMock =  $this->getMockBuilder(SearchResultsInterface::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->orderSendReminderEmailMock = new OrderSendReminderEmail(
            $this->orderRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->filterBuilderMock,
            $this->timezoneMock,
            $this->loggerMock,
            $this->orderEmailHelperMock
        );
    }

    /**
     * Test the execute method
     *
     * Ensures that declineOldOrders and sendReminderEmails are called
     */
    public function testExecute()
    {
        $this->orderSendReminderEmailMock = $this->getMockBuilder(OrderSendReminderEmail::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['declineOldOrders', 'sendReminderEmails'])
        ->getMock();

        $this->orderSendReminderEmailMock->expects($this->once())->method('declineOldOrders');
        $this->orderSendReminderEmailMock->expects($this->once())->method('sendReminderEmails');

        $this->orderSendReminderEmailMock->execute();
    }

    /**
     * Test the declineOldOrders method
     *
     * Ensures orders older than 31 days are set to declined
     */
    public function testDeclineOldOrders()
    {
        $this->timezoneMock->method('date')->willReturn(new \DateTime('2021-01-01'));

        $this->filterBuilderMock->method('setField')->willReturnSelf();
        $this->filterBuilderMock->method('setConditionType')->willReturnSelf();
        $this->filterBuilderMock->method('setValue')->willReturnSelf();
        $this->filterBuilderMock->method('create')->willReturn($this->createMock(\Magento\Framework\Api\Filter::class));

        $this->searchCriteriaBuilderMock->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);

        $this->orderRepositoryMock->method('getList')->willReturn($this->orderListMock);
        $this->orderListMock->method('getItems')->willReturn([$this->orderMock]);

        $this->orderMock->expects($this->once())->method('setState')->with('declined');
        $this->orderMock->expects($this->once())->method('setStatus')->with('declined');
        $this->orderRepositoryMock->expects($this->once())->method('save')->with($this->orderMock);

        $this->orderSendReminderEmailMock->declineOldOrders();
    }

    /**
     * Test the sendReminderEmails method
     *
     * Ensures reminder emails are sent for orders created 26 days ago
     */
    public function testSendReminderEmails()
    {
        $this->timezoneMock->method('date')->willReturn(new \DateTime('2021-01-01'));

        $this->filterBuilderMock->method('setField')->willReturnSelf();
        $this->filterBuilderMock->method('setConditionType')->willReturnSelf();
        $this->filterBuilderMock->method('setValue')->willReturnSelf();
        $this->filterBuilderMock->method('create')->willReturn($this->createMock(\Magento\Framework\Api\Filter::class));

        $this->searchCriteriaBuilderMock->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);

        $this->orderRepositoryMock->method('getList')->willReturn($this->orderListMock);
        $this->orderListMock->method('getItems')->willReturn([$this->orderMock]);

        $this->orderEmailHelperMock->expects($this->once())->method('sendOrderGenericEmail')->with([
            'order_id' => $this->orderMock->getEntityId(),
            'status' => 'expired'
        ]);

        $this->orderSendReminderEmailMock->sendReminderEmails();
    }
}
