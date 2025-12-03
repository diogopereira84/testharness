<?php
/**
 * Copyright ©FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Controller\Adminhtml\Options;

use Fedex\CustomerGroup\Controller\Adminhtml\Options\FindSearchedUsers;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\Request\Http;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\Customer;
use PHPUnit\Framework\TestCase;

class FindSearchedUsersTest extends TestCase
{
    /** @var Context|MockObject */
    private $contextMock;
    /** @var JsonFactory|MockObject */
    private $resultJsonFactoryMock;
    /** @var Json|MockObject */
    private $resultJsonMock;
    /** @var Http|MockObject */
    private $requestMock;
    /** @var LoggerInterface|MockObject */
    private $loggerMock;
    /** @var CustomerRepositoryInterface|MockObject */
    private $customerRepositoryMock;
    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilderMock;
    /** @var CustomerFactory|MockObject */
    private $customerFactoryMock;
    /** @var CustomerCollection|MockObject */
    private $customerCollectionMock;
    /** @var FindSearchedUsers */
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
        $this->customerFactoryMock = $this->createMock(CustomerFactory::class);
        $this->customerCollectionMock = $this->getMockBuilder(CustomerCollection::class)
            ->addMethods(['getCollection'])
            ->onlyMethods(['addFieldToFilter', 'addAttributeToFilter', 'getItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new FindSearchedUsers(
            $this->contextMock,
            $this->resultJsonFactoryMock,
            $this->requestMock,
            $this->loggerMock,
            $this->customerRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->customerFactoryMock
        );
    }

    public function testExecuteSuccessWithResults()
    {
        $filterValue = 'John';
        $excludedUserIds = [2, 3];
        $params = [
            'filterValue' => $filterValue,
            'excludedUserIds' => $excludedUserIds
        ];

        $customerMock = $this->createMock(Customer::class);
        $customerMock->method('getId')->willReturn(1);
        $customerMock->method('getName')->willReturn('John Doe');

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->customerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->customerCollectionMock);

        $this->customerCollectionMock->expects($this->once())
            ->method('getCollection')
            ->willReturnSelf();

        $this->customerCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', ['nin' => $excludedUserIds])
            ->willReturnSelf();

        $this->customerCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with([
                ['attribute' => 'firstname', 'like' => '%' . $filterValue . '%'],
                ['attribute' => 'lastname', 'like' => '%' . $filterValue . '%']
            ])
            ->willReturnSelf();

        $this->customerCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$customerMock]);

        $expectedHtml = '<ul><li data-index-id="1">John Doe</li></ul>';

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['status' => 'success', 'html' => $expectedHtml])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteSuccessWithNoResults()
    {
        $filterValue = 'Jane';
        $params = [
            'filterValue' => $filterValue
        ];

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->customerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->customerCollectionMock);

        $this->customerCollectionMock->expects($this->once())
            ->method('getCollection')
            ->willReturnSelf();

        $this->customerCollectionMock->expects($this->never())
            ->method('addFieldToFilter');

        $this->customerCollectionMock->expects($this->once())
            ->method('addAttributeToFilter')
            ->with([
                ['attribute' => 'firstname', 'like' => '%' . $filterValue . '%'],
                ['attribute' => 'lastname', 'like' => '%' . $filterValue . '%']
            ])
            ->willReturnSelf();

        $this->customerCollectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([]);

        $expectedHtml = '';

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['status' => 'success', 'html' => $expectedHtml])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteErrorNoFilterValue()
    {
        $params = [
            'excludedUserIds' => [1, 2]
        ];

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
                'message' => 'Error finding searched users for Assign Permissions Modal.'
            ])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteErrorNoRequestData()
    {
        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn([]);

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'error',
                'message' => 'Error finding searched users for Assign Permissions Modal.'
            ])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }

    public function testExecuteException()
    {
        $filterValue = 'John';
        $params = [
            'filterValue' => $filterValue
        ];

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($params);

        $this->customerFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('Test Exception'));

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Error finding searched users for Assign Permissions Modal: Test Exception'));

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with([
                'status' => 'error',
                'message' => 'Test Exception'
            ])
            ->willReturnSelf();

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }
}
