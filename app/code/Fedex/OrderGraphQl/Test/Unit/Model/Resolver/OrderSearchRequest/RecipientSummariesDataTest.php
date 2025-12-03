<?php
/**
 * @category     Fedex
 * @package      Fedex_OrderGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Test\Unit\Model\Resolver\OrderSearchRequest;

use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\RecipientSummariesData;
use Fedex\OrderGraphQl\Model\Resolver\OrderSearchRequest\OrderSearchRequestHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;
use PHPUnit\Framework\TestCase;

class RecipientSummariesDataTest extends TestCase
{
    protected $orderSearchRequestHelper;
    protected $recipientSummariesData;
    protected function setUp(): void
    {
        $this->orderSearchRequestHelper = $this->createMock(OrderSearchRequestHelper::class);
        $this->recipientSummariesData = new RecipientSummariesData($this->orderSearchRequestHelper);
    }

    public function testGetDataWithShipment(): void
    {
        $order = $this->createMock(Order::class);
        $shipmentCollection = $this->createMock(Collection::class);
        $shippingAddress = $this->createMock(OrderAddressInterface::class);
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrderItemId'])
            ->onlyMethods(['getSku'])
            ->getMock();

        $quoteItemsInstanceId = ['1' => 'instance1'];
        $shipment = $this->createMock(Shipment::class);

        $order->method('getShipmentsCollection')->willReturn([$shipment]);
        $order->method('getShippingAddress')->willReturn($shippingAddress);
        $shipmentCollection->method('getItems')->willReturn([$shipment]);
        $shipment->method('getShippingAddress')->willReturn($shippingAddress);
        $shipment->method('getItems')->willReturn([$item]);
        $item->method('getOrderItemId')->willReturn(1);

        $this->orderSearchRequestHelper
            ->method('getItemsWithoutShipment')
            ->willReturn([]);

        $this->orderSearchRequestHelper
            ->method('getQuantity')
            ->willReturn(2);

        $shippingAddress->expects($this->once())->method('getFirstname')->willReturn('John');
        $shippingAddress->expects($this->once())->method('getLastname')->willReturn('Doe');
        $shippingAddress->expects($this->once())->method('getCompany')->willReturn('Acme Corp');
        $shippingAddress->expects($this->once())->method('getEmail')->willReturn('john.doe@example.com');
        $shippingAddress->expects($this->once())->method('getTelephone')->willReturn('1234567890');

        $result = $this->recipientSummariesData->getData($quoteItemsInstanceId, $order);

        $expectedResult = [
            [
                'reference' => null,
                'contact' => [
                    'personName' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe'
                    ],
                    'company' => [
                        'name' => 'Acme Corp'
                    ],
                    'emailDetail' => [
                        'emailAddress' => 'john.doe@example.com'
                    ],
                    'phoneNumberDetails' => [[
                        'phoneNumber' => [
                            'number' => '1234567890'
                        ],
                        'usage' => 'PRIMARY'
                    ]]
                ],
                'productAssociations' => [
                    [
                        'id' => 'instance1',
                        'quantity' => 2
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetDataWithNonShipmentItems(): void
    {
        $order = $this->createMock(Order::class);
        $shippingAddress = $this->createMock(OrderAddressInterface::class);
        $shippingAddress->expects($this->once())->method('getFirstname')->willReturn('John');
        $shippingAddress->expects($this->once())->method('getLastname')->willReturn('Doe');
        $shippingAddress->expects($this->once())->method('getCompany')->willReturn('Acme Corp');
        $shippingAddress->expects($this->once())->method('getEmail')->willReturn('john.doe@example.com');
        $shippingAddress->expects($this->once())->method('getTelephone')->willReturn('1234567890');

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrderItemId'])
            ->onlyMethods(['getSku', 'getId', 'getQtyOrdered'])
            ->getMock();
        $quoteItemsInstanceId = ['1' => 'instance1'];

        $order->expects($this->once())->method('getShipmentsCollection')->willReturn([]);
        $order->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddress);
        $item->expects($this->once())->method('getId')->willReturn(1);
        $item->expects($this->once())->method('getQtyOrdered')->willReturn(2);

        $this->orderSearchRequestHelper
            ->method('getItemsWithoutShipment')
            ->willReturn(['1p' => [$item]]);

        $result = $this->recipientSummariesData->getData($quoteItemsInstanceId, $order);

        $expectedResult = [
            [
                'reference' => null,
                'contact' => [
                    'personName' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe'
                    ],
                    'company' => [
                        'name' => 'Acme Corp'
                    ],
                    'emailDetail' => [
                        'emailAddress' => 'john.doe@example.com'
                    ],
                    'phoneNumberDetails' => [[
                        'phoneNumber' => [
                            'number' => '1234567890'
                        ],
                        'usage' => 'PRIMARY'
                    ]]
                ],
                'productAssociations' => [
                    [
                        'id' => 'instance1',
                        'quantity' => 2
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedResult, $result);
    }
}
