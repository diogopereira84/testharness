<?php

declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Controller\Adminhtml\Options;

use Fedex\CustomerGroup\Controller\Adminhtml\Options\GetSelectedUsers;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\Request\Http;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;

class GetSelectedUsersTest extends TestCase
{
    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;
    /**
     * @var JsonFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonFactoryMock;
    /**
     * @var Json|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultJsonMock;
    /**
     * @var Http|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestMock;
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;
    /**
     * @var CustomerRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerRepositoryMock;
    /**
     * @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilderMock;
    /**
     * @var SearchCriteria|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaMock;
    /**
     * @var CustomerSearchResultsInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerSearchResultsMock;
    /**
     * @var GetSelectedUsers
     */
    private $controller;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->resultJsonMock = $this->createMock(Json::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->customerSearchResultsMock = $this->createMock(CustomerSearchResultsInterface::class);

        $this->controller = new GetSelectedUsers(
            $this->contextMock,
            $this->resultJsonFactoryMock,
            $this->requestMock,
            $this->loggerMock,
            $this->customerRepositoryMock,
            $this->searchCriteriaBuilderMock
        );
    }

    public function testExecuteSuccess()
    {
        $selectedIds = [1, 2];
        $params = ['selectedIds' => $selectedIds];

        $customer1 = $this->createMock(CustomerInterface::class);
        $customer1->method('getFirstname')->willReturn('John');
        $customer1->method('getLastname')->willReturn('Doe');

        $customer2 = $this->createMock(CustomerInterface::class);
        $customer2->method('getFirstname')->willReturn('Jane');
        $customer2->method('getLastname')->willReturn('Smith');

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('entity_id', $selectedIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->customerSearchResultsMock);

        $this->customerSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$customer1, $customer2]);

        $this->resultJsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteNoSelectedIds()
    {
        $params = [];

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'error',
                'message' => 'Error getting selected user ids for Assign Permissions Modal.'
            ])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteException()
    {
        $params = ['selectedIds' => [1, 2]];

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->willThrowException(new \Exception('Some error'));

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error getting selected user ids for Assign Permissions Modal: Some error'));

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'error',
                'message' => 'Some error'
            ])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testGetSelectedCustomersNames()
    {
        $selectedIds = [1, 2];

        $customer1 = $this->createMock(CustomerInterface::class);
        $customer1->method('getId')->willReturn('1');
        $customer1->method('getFirstname')->willReturn('John');
        $customer1->method('getLastname')->willReturn('Doe');

        $customer2 = $this->createMock(CustomerInterface::class);
        $customer2->method('getId')->willReturn('2');
        $customer2->method('getFirstname')->willReturn('Jane');
        $customer2->method('getLastname')->willReturn('Smith');

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('entity_id', $selectedIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->customerSearchResultsMock);

        $this->customerSearchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$customer1, $customer2]);

        $result = $this->controller->getSelectedCustomersNames($selectedIds);
        $this->assertEquals(['1' => 'John Doe', '2' => 'Jane Smith'], $result);
    }
}
