<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\OrderSearchRequest;

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\OrderSearchRequest;

use Fedex\OrderGraphQl\Model\Resolver\DataProvider\ShipmentStatusLabel;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\JobSummariesOrderData;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\OrderSearchRequestHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\Data\Collection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use PHPUnit\Framework\TestCase;

class JobSummariesOrderDataTest extends TestCase
{
    protected $shipmentStatusLabelProvider;
    protected $orderSearchRequestHelper;
    protected $jobSummariesOrderData;
    private string $orderCurrency = 'USD';

    protected function setUp(): void
    {
        $this->shipmentStatusLabelProvider = $this->createMock(ShipmentStatusLabel::class);
        $this->orderSearchRequestHelper = $this->createMock(OrderSearchRequestHelper::class);

        $this->jobSummariesOrderData = new JobSummariesOrderData(
            $this->shipmentStatusLabelProvider,
            $this->orderSearchRequestHelper
        );
    }

    public function testGetDataWithShipments(): void
    {
        $order = $this->createMock(Order::class);

        $shipment = $this->createMock(Shipment::class);
        $track = $this->createMock(Track::class);
        $track->expects($this->once())->method('getTrackNumber')->willReturn('TRACK123');

        $collection = $this->createMock(Collection::class);
        $shipment->expects($this->once())->method('getTracksCollection')->willReturn($collection);
        $collection->expects($this->once())->method('getItems')->willReturn([$track]);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrderItemId'])
            ->onlyMethods(['getSku'])
            ->getMock();
        $item->expects($this->once())->method('getSku')->willReturn('sku1');
        $item->expects($this->once())->method('getOrderItemId')->willReturn('itemId1');

        $shipment->expects($this->once())->method('getItems')->willReturn([$item]);

        $order->expects($this->once())->method('getShipmentsCollection')->willReturn([$shipment]);
        $order->expects($this->exactly(2))->method('getOrderCurrency')
            ->willReturn($this->createMock(Currency::class));
        $order->getOrderCurrency()->method('getCurrencyCode')->willReturn($this->orderCurrency);

        $productAssociationsData = $this->getProductAssociationsMock();


        $quoteItemsInstanceId = ['itemId1' => 'quoteItemId1'];

        $this->shipmentStatusLabelProvider->method('getShipmentLabel')->willReturn('Shipped');
        $this->orderSearchRequestHelper->method('getQuantity')->willReturn(2);
        $this->orderSearchRequestHelper->method('getItemsWithoutShipment')->willReturn([]);

        $result = $this->jobSummariesOrderData->getData(
            $productAssociationsData,
            $quoteItemsInstanceId,
            $order
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('jobGTN', $result[0]);
        $this->assertEquals('Shipped', $result[0]['status']);
    }

    public function testGetDataWithNonShipmentItems(): void
    {
        $order = $this->createMock(Order::class);

        $item = $this->createMock(Item::class);
        $item->expects($this->once())->method('getSku')->willReturn('sku1');
        $item->expects($this->once())->method('getId')->willReturn('itemId1');
        $orderItems = [$item];

        $order->expects($this->once())->method('getShipmentsCollection')->willReturn([]);
        $order->expects($this->exactly(2))->method('getOrderCurrency')
            ->willReturn($this->createMock(Currency::class));
        $order->getOrderCurrency()->method('getCurrencyCode')->willReturn($this->orderCurrency);

        $productAssociationsData = $this->getProductAssociationsMock();

        $quoteItemsInstanceId = ['itemId1' => 'quoteItemId1'];

        $this->shipmentStatusLabelProvider->method('getShipmentLabel')->willReturn('Received');
        $this->orderSearchRequestHelper->method('getQuantity')->willReturn(2);
        $this->orderSearchRequestHelper->method('getItemsWithoutShipment')->willReturn([$orderItems]);

        $result = $this->jobSummariesOrderData->getData(
            $productAssociationsData,
            $quoteItemsInstanceId,
            $order
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('jobGTN', $result[0]);
        $this->assertEquals('Received', $result[0]['status']);
    }

    private function getProductAssociationsMock(): array
    {
        return [
            'sku1' => [
                'currency' => $this->orderCurrency,
                'reference' => '1531236895-5',
                'binLocation' => '1423',
                'seller' => 'FXO'
            ]
        ];
    }
}
