<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\OrderSearchRequest;

use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\OrderSearchRequestHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\TestCase;

class OrderSearchRequestHelperTest extends TestCase
{
    protected $orderSearchRequestHelper;
    protected function setUp(): void
    {
        $this->orderSearchRequestHelper = new OrderSearchRequestHelper();
    }

    public function testCheckInstoreWithInstoreOrderId(): void
    {
        $orderIncrementId = '20201598712123';
        $this->assertTrue($this->orderSearchRequestHelper->checkInstore($orderIncrementId));
    }

    public function testCheckInstoreWithNonInstoreOrderId(): void
    {
        $orderIncrementId = '20101598712123';
        $this->assertFalse($this->orderSearchRequestHelper->checkInstore($orderIncrementId));
    }

    public function testGetQuantity(): void
    {
        $orderItem = $this->createMock(OrderItemInterface::class);
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrderItem'])
            ->getMock();

        $item->method('getOrderItem')->willReturn($orderItem);
        $orderItem->method('getQtyOrdered')->willReturn(5);

        $this->assertEquals(5, $this->orderSearchRequestHelper->getQuantity($item));
    }

    public function testGetFormattedCstDate(): void
    {
        $datetime = '2024-08-02 15:00:00';
        $cstDate = '2024-08-02T15:00:00-06:00';

        $this->assertEquals($cstDate, $this->orderSearchRequestHelper->getFormattedCstDate($datetime));
    }

    public function testGetFormattedCstDateInvalidData(): void
    {
        $datetime = 'invalid input';
        $this->assertEquals($datetime, $this->orderSearchRequestHelper->getFormattedCstDate($datetime));
    }

    public function testGetItemsWithoutShipment(): void
    {
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAllItems', 'getShipmentsCollection'])
            ->getMockForAbstractClass();

        $orderItem1 = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId'])
            ->onlyMethods(['getItemId'])
            ->getMockForAbstractClass();
        $orderItem2 = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getMiraklOfferId'])
            ->onlyMethods(['getItemId'])
            ->getMockForAbstractClass();

        $orderItem1->expects($this->once())->method('getItemId')->willReturn(1);
        $orderItem2->expects($this->once())->method('getItemId')->willReturn(2);

        $order->expects($this->once())->method('getAllItems')->willReturn([$orderItem1, $orderItem2]);

        $shipment = $this->createMock(ShipmentInterface::class);
        $shipmentItem = $this->createMock(ShipmentItemInterface::class);
        $shipmentItem->expects($this->once())->method('getOrderItemId')->willReturn(2);
        $shipment->expects($this->once())->method('getItems')->willReturn([$shipmentItem]);

        $order->expects($this->once())->method('getShipmentsCollection')->willReturn([$shipment]);

        $expectedResult = [
            '1p' => [$orderItem1]
        ];

        $result = $this->orderSearchRequestHelper->getItemsWithoutShipment($order);
        $this->assertEquals($expectedResult, $result);
    }
}
