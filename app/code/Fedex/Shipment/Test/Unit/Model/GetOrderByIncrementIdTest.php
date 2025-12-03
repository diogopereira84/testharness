<?php
/**
 * Unit test for GetOrderByIncrementId
 *
 * @category  Fedex
 * @package   Fedex_Shipment
 * @author    GitHub Copilot
 * @copyright Copyright (c) 2025 Fedex
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Fedex\Shipment\Test\Unit\Model;

use Fedex\Shipment\Model\GetOrderByIncrementId;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\TestCase;

class GetOrderByIncrementIdTest extends TestCase
{
    /** @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $orderRepositoryMock;

    /** @var SearchCriteriaBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $searchCriteriaBuilderMock;

    /** @var GetOrderByIncrementId */
    private $model;

    private $searchCriteriaMock;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaMock = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteriaInterface::class)->getMock();

        $this->model = new GetOrderByIncrementId(
            $this->orderRepositoryMock,
            $this->searchCriteriaBuilderMock
        );
    }

    public function testExecuteReturnsOrderWhenFound()
    {
        $incrementId = '100000001';
        $orderMock = $this->getMockBuilder(OrderInterface::class)->getMock();
        $orders = [$orderMock];
        $orderListMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();
        $orderListMock->method('getItems')->willReturn($orders);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', $incrementId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->orderRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($orderListMock);

        $result = $this->model->execute($incrementId);
        $this->assertSame($orderMock, $result);
    }

    public function testExecuteReturnsNullWhenNotFound()
    {
        $incrementId = '100000002';
        $orders = [];
        $orderListMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();
        $orderListMock->method('getItems')->willReturn($orders);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', $incrementId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->orderRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($orderListMock);

        $result = $this->model->execute($incrementId);
        $this->assertNull($result);
    }

    public function testExecuteReturnsNullWhenMultipleOrdersFound()
    {
        $incrementId = '100000003';
        $orderMock1 = $this->getMockBuilder(OrderInterface::class)->getMock();
        $orderMock2 = $this->getMockBuilder(OrderInterface::class)->getMock();
        $orders = [$orderMock1, $orderMock2];
        $orderListMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();
        $orderListMock->method('getItems')->willReturn($orders);

        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', $incrementId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->orderRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($orderListMock);

        $result = $this->model->execute($incrementId);
        $this->assertNull($result);
    }
}

