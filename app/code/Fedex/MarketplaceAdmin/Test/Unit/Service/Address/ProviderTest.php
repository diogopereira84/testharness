<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Service\Address;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressProvider;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

class ProviderTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetAddressReturnsCorrectData()
    {
        $miraklData = ['firstname'=>'Foo'];
        $item = $this->createMock(Item::class);
        $item->method('getAdditionalData')->willReturn(json_encode(['mirakl_shipping_data'=>['address'=>$miraklData]]));

        $order = $this->createMock(Order::class);
        $order->method('getAllItems')->willReturn([$item]);

        $provider = new MiraklShippingAddressProvider();
        $this->assertSame($miraklData, $provider->getAddress($order));
    }

    /**
     * @return void
     */
    public function testGetAddressReturnsNullIfNoData()
    {
        $item = $this->createMock(Item::class);
        $item->method('getAdditionalData')->willReturn(null);

        $order = $this->createMock(Order::class);
        $order->method('getAllItems')->willReturn([$item]);

        $provider = new MiraklShippingAddressProvider();
        $this->assertNull($provider->getAddress($order));
    }
}
