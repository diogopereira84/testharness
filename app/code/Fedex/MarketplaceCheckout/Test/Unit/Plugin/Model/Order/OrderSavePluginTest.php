<?php

namespace Fedex\MarketplaceCheckout\Test\Unit\Plugin\Model\Order;

use Fedex\MarketplaceCheckout\Helper\Data;
use Fedex\MarketplaceCheckout\Plugin\Model\Order\OrderSavePlugin;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Mirakl\Connector\Helper\Config;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\MarketplaceRates\Helper\Data as MarketPlaceHelper;
use Magento\Framework\App\CacheInterface;

class OrderSavePluginTest extends TestCase
{
    /**
     * @var (\Fedex\MarketplaceRates\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $marketPlaceHelper;

    /**
     * @var (\Magento\Framework\App\CacheInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cacheInterface;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var PublisherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $publisherMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var OrderSavePlugin
     */
    private $plugin;

    /**
     * @var Order
     */
    private $orderMock;

    /**
     * Setup mocks and test subject.
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->orderMock = $this->createMock(Order::class);
        $this->marketPlaceHelper = $this->createMock(MarketPlaceHelper::class);
        $this->cacheInterface = $this->createMock(CacheInterface::class);

        $this->plugin = new OrderSavePlugin(
            $this->loggerMock,
            $this->helperMock,
            $this->publisherMock,
            $this->configMock,
            $this->cacheInterface,
            $this->marketPlaceHelper
        );
    }

    /**
     * Test aroundSave method when order is new.
     */
    public function testAroundSaveWhenOrderIsNew(): void
    {
        $this->orderMock->method('isObjectNew')->willReturn(true);

        $closure = function ($order) {
            return $order;
        };

        $subjectMock = $this->createMock(OrderResourceInterface::class);
        $result = $this->plugin->aroundSave($subjectMock, $closure, $this->orderMock);

        $this->assertSame($this->orderMock, $result);
    }

    /**
     * Test aroundSave method when order is not new and queue sending is enabled.
     */
    public function testAroundSaveWhenOrderIsNotNewAndQueueSendingEnabled(): void
    {
        $orderId = 123;
        $orderStatus = 'processing';
        $orderIncrementId = '100000001';

        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->method('isObjectNew')->willReturn(false);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getIncrementId')->willReturn($orderIncrementId);
        $this->configMock->method('getCreateOrderStatuses')->willReturn([$orderStatus]);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('sendOrderToMiraklQueue', json_encode(['order_id' => $orderId]));

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->matchesRegularExpression("/" . preg_quote(OrderSavePlugin::class . '::aroundSave:') . "\d+ sendOrderToMiraklQueue Publisher - Order Increment ID " . preg_quote($orderIncrementId) . "/"));

        $closure = function ($order) {
            return $order;
        };

        $subjectMock = $this->createMock(OrderResourceInterface::class);
        $result = $this->plugin->aroundSave($subjectMock, $closure, $this->orderMock);

        $this->assertSame($this->orderMock, $result);
    }

    /**
     * Test aroundSave method when order is not new and queue sending is disabled.
     */
    public function testAroundSaveRemovesCacheWithCorrectKeyWhenFreightEnabled(): void
    {
        $orderId     = 999;
        $orderStatus = 'shipped';
        $quoteId     = 555;
        $incrementId = '200000999';

        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->orderMock->method('isObjectNew')->willReturn(false);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getIncrementId')->willReturn($incrementId);
        $this->orderMock->method('getQuoteId')->willReturn($quoteId);

        $this->configMock
            ->method('getCreateOrderStatuses')
            ->willReturn([$orderStatus]);

        $this->publisherMock
            ->expects($this->once())
            ->method('publish')
            ->with(
                'sendOrderToMiraklQueue',
                json_encode(['order_id' => $orderId])
            );

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with($this->matchesRegularExpression(
                "/" . preg_quote(OrderSavePlugin::class . '::aroundSave:') . "\d+ sendOrderToMiraklQueue Publisher - Order Increment ID " . preg_quote($incrementId) . "/"
            ));

        $this->marketPlaceHelper
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $expectedCacheKey = 'freight_packaging_response_' . $quoteId;
        $this->cacheInterface
            ->expects($this->once())
            ->method('remove')
            ->with($expectedCacheKey);

        $closure     = function ($order) {
            return $order;
        };
        $subjectMock = $this->createMock(\Magento\Sales\Model\Spi\OrderResourceInterface::class);

        $result = $this->plugin->aroundSave($subjectMock, $closure, $this->orderMock);

        $this->assertSame($this->orderMock, $result);
    }

    /**
     * Test aroundSave method when an exception occurs during queue publishing.
     */
    public function testAroundSaveCatchesExceptionAndLogsWarning(): void
    {
        $orderId     = 321;
        $orderStatus = 'processing';
        $quoteId     = 654;

        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->method('isObjectNew')->willReturn(false);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getIncrementId')->willReturn('100000123');
        $this->orderMock->method('getQuoteId')->willReturn($quoteId);

        $this->configMock
            ->method('getCreateOrderStatuses')
            ->willReturn([$orderStatus]);

        $this->publisherMock
            ->method('publish')
            ->willThrowException(new \Exception('simulated failure'));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with('simulated failure');

        $this->marketPlaceHelper
            ->method('isFreightShippingEnabled')
            ->willReturn(true);

        $this->cacheInterface
            ->expects($this->never())
            ->method('remove');

        $closure     = function ($order) {
            return $order;
        };
        $subjectMock = $this->createMock(OrderResourceInterface::class);

        $result = $this->plugin->aroundSave($subjectMock, $closure, $this->orderMock);

        $this->assertSame($this->orderMock, $result);
    }
}
