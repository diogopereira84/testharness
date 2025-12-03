<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Plugin\UpdateOrderOriginPlugin;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Fedex\MarketplaceAdmin\Model\Config;
class UpdateOrderOriginPluginTest extends TestCase
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var UpdateOrderOriginPlugin
     */
    private $plugin;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
        $this->config = $this->createMock(Config::class);

        $this->plugin = new UpdateOrderOriginPlugin(
            $this->resourceConnection,
            $this->orderHelper,
            $this->config
        );
    }

    /**
     * Test afterCreateOrderBeforePayment method saving origin.
     *
     * @return void
     */
    public function testAfterSaveWithOrigin(): void
    {
        $submitOrderApi = $this->createMock(SubmitOrderApi::class);
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->config->expects($this->once())
            ->method('isMktSelfregEnabled')
            ->willReturn(true);

        $this->orderHelper->expects($this->once())
            ->method('isFullMiraklOrder')
            ->with($order)
            ->willReturn(true);

        $this->resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class));

        $this->resourceConnection->getConnection()->expects($this->any())
            ->method('getTableName')
            ->with('sales_order_grid')
            ->willReturn('sales_order_grid');

        $this->plugin->afterCreateOrderBeforePayment($submitOrderApi, $order);
    }

    /**
     * Test afterCreateOrderBeforePayment method when toggle disabled.
     *
     * @return void
     */
    public function testAfterCreateOrderBeforePaymentSelfregDisabled(): void
    {
        $submitOrderApi = $this->createMock(SubmitOrderApi::class);
        $order = $this->createMock(Order::class);

        $this->config->expects($this->once())
            ->method('isMktSelfregEnabled')
            ->willReturn(false);

        $this->resourceConnection->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class));

        $this->resourceConnection->getConnection()->expects($this->any())
            ->method('getTableName')
            ->with('sales_order_grid')
            ->willReturn('sales_order_grid');


        $this->plugin->afterCreateOrderBeforePayment($submitOrderApi, $order);
    }

    /**
     * Test getOrderOrigin method when order origin is marketplace.
     *
     * @return void
     */
    public function testGetOrderOriginMarketplace(): void
    {
        $order = $this->createMock(Order::class);

        $this->orderHelper->expects($this->once())
            ->method('isFullMiraklOrder')
            ->with($order)
            ->willReturn(true);
        $origin = $this->plugin->getOrderOrigin($order);

        $this->assertEquals('marketplace', $origin);
    }

    /**
     * Test getOrderOrigin method when order origin is mixed.
     *
     * @return void
     */
    public function testGetOrderOriginMixed(): void
    {
        $order = $this->createMock(Order::class);

        $this->orderHelper->expects($this->once())
            ->method('isFullMiraklOrder')
            ->with($order)
            ->willReturn(false);

        $this->orderHelper->expects($this->once())
            ->method('isMiraklOrder')
            ->with($order)
            ->willReturn(true);

        $origin = $this->plugin->getOrderOrigin($order);

        $this->assertEquals('mixed', $origin);
    }

    /**
     * Test getOrderOrigin method when order origin is operator.
     *
     * @return void
     */
    public function testGetOrderOriginOperator(): void
    {
        $order = $this->createMock(Order::class);

        $this->orderHelper->expects($this->once())
            ->method('isFullMiraklOrder')
            ->with($order)
            ->willReturn(false);

        $this->orderHelper->expects($this->once())
            ->method('isMiraklOrder')
            ->with($order)
            ->willReturn(false);

        $origin = $this->plugin->getOrderOrigin($order);

        $this->assertEquals('operator', $origin);
    }
}
