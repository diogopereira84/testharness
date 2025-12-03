<?php

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\OrderStoreRetriever;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Magento\Sales\Api\Data\OrderInterface;


class OrderStoreRetrieverTest extends TestCase
{
    /** @var OrderRepositoryInterface  */
    private OrderRepositoryInterface $orderRepository;

    /** @var OrderInterface  */
    private OrderInterface $order;

    /** @var OrderStoreRetriever  */
    private OrderStoreRetriever $orderStoreRetriever;

    public function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->order = $this->createMock(OrderInterface::class);

        $this->orderStoreRetriever = new OrderStoreRetriever($this->orderRepository);
    }

    public function testGetStoreIdFromOrder() {
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->order);

        $this->order->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->assertEquals(1, $this->orderStoreRetriever->getStoreIdFromOrder(123));
    }
}
