<?php

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\SendOrderQueueToMirakl;
use Fedex\MarketplaceRates\Helper\Data;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Mirakl\Connector\Helper\Order as MiraklOrder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;

class SendOrderQueueToMiraklTest extends TestCase
{
    /**
     * @var MiraklOrder
     */
    private $miraklOrderMock;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepositoryMock;

    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var SendOrderQueueToMirakl
     */
    private $sendOrderQueueToMirakl;

    /**
     * @var Data
     */
    private $helperMock;

    /**
     * @var MarketplaceHelper
     */
    private $marketplaceHelperMock;

    /**
     * @var ShopRepositoryInterface
     */
    private $shopRepositoryInterface;

    /**
     * @var QuoteItemCollectionFactory
     */
    private $quoteItemCollectionFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->miraklOrderMock = $this->createMock(MiraklOrder::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->marketplaceHelperMock = $this->createMock(MarketplaceHelper::class);
        $this->shopRepositoryInterface = $this->createMock(ShopRepositoryInterface::class);
        $this->quoteItemCollectionFactory = $this->createMock(QuoteItemCollectionFactory::class);

        $this->sendOrderQueueToMirakl = new SendOrderQueueToMirakl(
            $this->miraklOrderMock,
            $this->orderRepositoryMock,
            $this->shopRepositoryInterface,
            $this->loggerMock,
            $this->helperMock,
            $this->marketplaceHelperMock,
            $this->quoteItemCollectionFactory
        );
    }

    /**
     * Tests execute method
     */
    public function testExecute()
    {
        $message = json_encode(['order_id' => 123]);
        $orderMock = $this->createMock(Order::class);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($orderMock);

        $orderMock->expects($this->any())
            ->method('getIncrementId')
            ->willReturn('100000123');

        $orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('pending');

        $this->loggerMock->expects($this->exactly(1))
            ->method('info');

        $this->miraklOrderMock->expects($this->any())
            ->method('autoCreateMiraklOrder')
            ->with($orderMock);

        $result = $this->sendOrderQueueToMirakl->execute($message);
        $this->assertNull($result, 'execute() should return null');
    }

    /**
     * Tests execute method with exception
     */
    public function testExecuteWithException()
    {
        $message = json_encode(['order_id' => 123]);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willThrowException(new \Exception('An error occurred.'));

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(" ERROR SENDING ORDER TO MIRAKL ORDER ID 123: " . 'An error occurred.');

        $result = $this->sendOrderQueueToMirakl->execute($message);
        $this->assertNull($result, 'execute() should return null even on exception');
    }

    /**
     * Tests that shipping information is properly updated for Mirakl orders
     */
    public function testUpdateMiraklShippingInformation()
    {
        $orderId = 123;
        $message = json_encode(['order_id' => $orderId]);
        $orderMock = $this->createMock(Order::class);
        $orderItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getMiraklShopId',
                'getMiraklOfferId',
                'getAdditionalData',
                'setMiraklShippingType',
                'setMiraklShippingTypeLabel',
                'save'
            ])
            ->getMock();

        $shopMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getShippingRateOption'])
            ->getMockForAbstractClass();

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$orderItemMock]);

        $orderItemMock->expects($this->once())
            ->method('getMiraklShopId')
            ->willReturn('42');

        $orderItemMock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn('offer-123');

        $this->shopRepositoryInterface->expects($this->once())
            ->method('getById')
            ->with(42)
            ->willReturn($shopMock);

        $shopMock->expects($this->once())
            ->method('getShippingRateOption')
            ->willReturn(['shipping_rate_option' => 'mirakl-shipping-rates']);

        $additionalData = json_encode([
            'mirakl_shipping_data' => [
                'method_code' => 'express',
                'shipping_type_label' => 'Express Shipping'
            ]
        ]);

        $orderItemMock->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn($additionalData);

        $orderItemMock->expects($this->once())
            ->method('setMiraklShippingType')
            ->with('express');

        $orderItemMock->expects($this->once())
            ->method('setMiraklShippingTypeLabel')
            ->with('Express Shipping');

        $orderItemMock->expects($this->once())
            ->method('save');

        $this->miraklOrderMock->expects($this->once())
            ->method('autoCreateMiraklOrder')
            ->with($orderMock);

        $result = $this->sendOrderQueueToMirakl->execute($message);
        $this->assertNull($result, 'execute() should return null after updating shipping info');
    }

    /**
     * Tests when item has no Mirakl offer ID
     */
    public function testUpdateMiraklShippingInformationWithNoOfferId()
    {
        $orderId = 123;
        $message = json_encode(['order_id' => $orderId]);
        $orderMock = $this->createMock(Order::class);
        $orderItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getMiraklShopId',
                'getMiraklOfferId',
                'getAdditionalData',
                'setMiraklShippingType',
                'setMiraklShippingTypeLabel',
                'save'
            ])
            ->getMock();

        $shopMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getShippingRateOption'])
            ->getMockForAbstractClass();

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderItemMock->expects($this->once())
            ->method('getMiraklShopId')
            ->willReturn('42');

        $orderMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$orderItemMock]);

        $shopMock->expects($this->once())
            ->method('getShippingRateOption')
            ->willReturn(['shipping_rate_option' => 'mirakl-shipping-rates']);

        $this->shopRepositoryInterface->expects($this->once())
            ->method('getById')
            ->with(42)
            ->willReturn($shopMock);

        $orderItemMock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn(null);

        $orderItemMock->expects($this->never())
            ->method('setMiraklShippingType');

        $orderItemMock->expects($this->never())
            ->method('setMiraklShippingTypeLabel');

        $orderItemMock->expects($this->never())
            ->method('save');

        $this->miraklOrderMock->expects($this->once())
            ->method('autoCreateMiraklOrder')
            ->with($orderMock);

        $result = $this->sendOrderQueueToMirakl->execute($message);
        $this->assertNull($result, 'execute() should return null when there is no offer id');
    }

    /**
     * Tests when shop doesn't have Mirakl shipping rates
     */
    public function testUpdateMiraklShippingInformationWithNoMiraklShippingRates()
    {
        $orderId = 123;
        $message = json_encode(['order_id' => $orderId]);
        $orderMock = $this->createMock(Order::class);
        $orderItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getMiraklShopId',
                'getMiraklOfferId',
                'getAdditionalData',
                'setMiraklShippingType',
                'setMiraklShippingTypeLabel',
                'save'
            ])
            ->getMock();

        $shopMock = $this->getMockBuilder(\Fedex\MarketplaceProduct\Api\Data\ShopInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getShippingRateOption'])
            ->getMockForAbstractClass();

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$orderItemMock]);

        $orderItemMock->expects($this->once())
            ->method('getMiraklShopId')
            ->willReturn('42');

        $orderItemMock->expects($this->once())
            ->method('getMiraklOfferId')
            ->willReturn('offer-123');

        $this->shopRepositoryInterface->expects($this->once())
            ->method('getById')
            ->with(42)
            ->willReturn($shopMock);

        $shopMock->expects($this->once())
            ->method('getShippingRateOption')
            ->willReturn(['shipping_rate_option' => 'other-value']);

        $orderItemMock->expects($this->never())
            ->method('setMiraklShippingType');

        $orderItemMock->expects($this->never())
            ->method('setMiraklShippingTypeLabel');

        $orderItemMock->expects($this->never())
            ->method('save');

        $this->miraklOrderMock->expects($this->once())
            ->method('autoCreateMiraklOrder')
            ->with($orderMock);

        $result = $this->sendOrderQueueToMirakl->execute($message);
        $this->assertNull($result, 'execute() should return null when shop has no Mirakl rates');
    }

    /**
     * Tests that shippingOptionHasMiraklShippingRates() returns false when Mirakl shop ID is zero
     */
    public function testShippingOptionHasMiraklShippingRatesReturnsFalseWhenShopIdIsZero(): void
    {
        $refClass  = new \ReflectionClass($this->sendOrderQueueToMirakl);
        $refMethod = $refClass->getMethod('shippingOptionHasMiraklShippingRates');
        $refMethod->setAccessible(true);

        $result = $refMethod->invoke($this->sendOrderQueueToMirakl, 0);
        $this->assertFalse($result, 'Expected shippingOptionHasMiraklShippingRates(0) to return false');
    }
}
