<?php
declare(strict_types=1);

namespace Fedex\SSO\Test\Unit\Controller\Order;

use Fedex\SSO\Controller\Order\Associate;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Sales\Model\Order\CustomerAssignment;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Sales\Model\Order;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AssociateTest extends TestCase
{
    private $objectManager;
    private $orderRepositoryMock;
    private $customerRepositoryMock;
    private $resultJsonFactoryMock;
    private $customerAssignmentMock;
    private $searchCriteriaBuilderMock;
    private $loggerMock;
    private $customerSessionMock;
    private $requestMock;
    private $resultJsonMock;
    private $associateController;
    private $orderSuccessMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->customerAssignmentMock = $this->createMock(CustomerAssignment::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->requestMock = $this->createMock(HttpRequest::class);
        $this->resultJsonMock = $this->createMock(Json::class);
        $this->orderSuccessMock = $this->createMock(\Fedex\SubmitOrderSidebar\ViewModel\OrderSuccess::class);

        $this->resultJsonFactoryMock->method('create')->willReturn($this->resultJsonMock);
        $this->resultJsonMock->method('setData')->willReturnSelf();
        $this->orderSuccessMock->method('isPopupEnabled')->willReturn(true);

        $this->associateController = $this->objectManager->getObject(
            Associate::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'resultJsonFactory' => $this->resultJsonFactoryMock,
                'customerAssignment' => $this->customerAssignmentMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'logger' => $this->loggerMock,
                'customerSession' => $this->customerSessionMock,
                'request' => $this->requestMock,
                'orderSuccess' => $this->orderSuccessMock
            ]
        );
    }

    public function testExecuteUserNotLoggedIn()
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(false);

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => false, 'message' => 'User not logged in']);

        $this->assertSame($this->resultJsonMock, $this->associateController->execute());
    }

    public function testExecuteOrderOrCustomerIdMissing()
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')->with('orderId')->willReturn(null);
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => false, 'message' => 'Order ID or Customer ID missing']);

        $this->assertSame($this->resultJsonMock, $this->associateController->execute());
    }

    public function testExecuteOrderNotFound()
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')->with('orderId')->willReturn('1000001');
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteria);

        $searchResultsMock = $this->createMock(SearchResultsInterface::class);
        $searchResultsMock->method('getItems')->willReturn([]);

        $this->orderRepositoryMock->method('getList')->with($searchCriteria)->willReturn($searchResultsMock);

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => false, 'message' => 'Order not found']);

        $this->assertSame($this->resultJsonMock, $this->associateController->execute());
    }

    public function testExecuteOrderAlreadyAssigned()
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')->with('orderId')->willReturn('1000001');
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteria);

        $order = $this->createMock(Order::class);
        $order->method('getCustomerIsGuest')->willReturn(false);

        $searchResultsMock = $this->createMock(SearchResultsInterface::class);
        $searchResultsMock->method('getItems')->willReturn([$order]);

        $this->orderRepositoryMock->method('getList')->with($searchCriteria)->willReturn($searchResultsMock);

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => false, 'message' => 'Order is already assigned to a registered user']);

        $this->assertSame($this->resultJsonMock, $this->associateController->execute());
    }

    public function testExecuteSuccess()
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')->with('orderId')->willReturn('1000001');
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        $searchCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->method('create')->willReturn($searchCriteria);

        $order = $this->createMock(Order::class);
        $order->method('getCustomerIsGuest')->willReturn(true);

        $searchResultsMock = $this->createMock(SearchResultsInterface::class);
        $searchResultsMock->method('getItems')->willReturn([$order]);

        $this->orderRepositoryMock->method('getList')->with($searchCriteria)->willReturn($searchResultsMock);

        $customer = $this->createMock(CustomerInterface::class);
        $this->customerRepositoryMock->method('getById')->with(1)->willReturn($customer);

        $this->customerAssignmentMock->expects($this->once())->method('execute')->with($order, $customer);

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true, 'message' => 'Order successfully associated with the logged-in user']);

        $this->assertSame($this->resultJsonMock, $this->associateController->execute());
    }

    public function testExecuteExceptionHandling()
    {
        $this->customerSessionMock->method('isLoggedIn')->willReturn(true);
        $this->requestMock->method('getParam')->with('orderId')->willReturn('1000001');
        $this->customerSessionMock->method('getCustomerId')->willReturn(1);

        $this->searchCriteriaBuilderMock->method('addFilter')->willThrowException(new \Exception('Some error'));

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with('Error associating order: Some error');

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => false, 'message' => 'Error associating order']);

        $this->assertSame($this->resultJsonMock, $this->associateController->execute());
    }
}
