<?php
/**
 * Unit test for DueDateLogRepository
 *
 * @category  Fedex
 * @package   Fedex_Shipment
 * @author    GitHub Copilot
 * @copyright Copyright (c) 2025 Fedex
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
namespace Fedex\Shipment\Test\Unit\Model;

use Fedex\Shipment\Model\DueDateLogRepository;
use Fedex\Shipment\Model\ResourceModel\DueDateLog\CollectionFactory;
use Magento\Framework\Data\Collection;
use PHPUnit\Framework\TestCase;
use Fedex\Shipment\Model\ResourceModel\DueDateLog;

class DueDateLogRepositoryTest extends TestCase
{
    /** @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $collectionFactoryMock;

    /** @var Collection|\PHPUnit\Framework\MockObject\MockObject */
    private $collectionMock;

    /** @var DueDateLogRepository */
    private $repository;

    /**
     * @var DueDateLog|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dueDateLogResourceMock;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dueDateLogResourceMock = $this->getMockBuilder(DueDateLog::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = new DueDateLogRepository($this->collectionFactoryMock, $this->dueDateLogResourceMock);
    }

    public function testGetByOrderIdReturnsItemWhenFound()
    {
        $orderId = 123;
        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $itemMock->method('getId')->willReturn(1);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_id', $orderId)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('setOrder')
            ->with('updated_at', Collection::SORT_ORDER_DESC)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(1)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($itemMock);

        $result = $this->repository->getByOrderId($orderId);
        $this->assertSame($itemMock, $result);
    }

    public function testGetByOrderIdReturnsNullWhenNotFound()
    {
        $orderId = 123;
        $itemMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $itemMock->method('getId')->willReturn(null);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);
        $this->collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('order_id', $orderId)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('setOrder')
            ->with('updated_at', Collection::SORT_ORDER_DESC)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(1)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('setCurPage')
            ->with(1)
            ->willReturnSelf();
        $this->collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($itemMock);

        $result = $this->repository->getByOrderId($orderId);
        $this->assertNull($result);
    }
}

