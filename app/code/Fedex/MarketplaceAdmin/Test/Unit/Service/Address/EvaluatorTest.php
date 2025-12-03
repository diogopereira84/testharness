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
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressEvaluator;
use Fedex\MarketplaceAdmin\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;

class EvaluatorTest extends TestCase
{
    /**
     * @return void
     */
    public function testShouldOverrideFalseWhenDisabled()
    {
        $config = $this->createMock(Config::class);
        $config->method('isD226848Enabled')->willReturn(false);

        $order = $this->createMock(Order::class);
        $address = $this->createMock(Address::class);

        $evaluator = new MiraklShippingAddressEvaluator($config);
        $this->assertFalse($evaluator->shouldOverride($order, $address));
    }

    /**
     * @return void
     */
    public function testShouldOverrideTrue()
    {
        $config = $this->createMock(Config::class);
        $config->method('isD226848Enabled')->willReturn(true);

        $order = $this->createMock(Order::class);
        $shippingAddress = $this->createMock(Address::class);
        $shippingAddress->method('getId')->willReturn(123);
        $order->method('getShippingAddress')->willReturn($shippingAddress);
        $order->method('getShippingMethod')->willReturn('fedexshipping_PICKUP');

        $address = $this->createMock(Address::class);
        $address->method('getId')->willReturn(123);

        $evaluator = new MiraklShippingAddressEvaluator($config);
        $this->assertTrue($evaluator->shouldOverride($order, $address));
    }
}
