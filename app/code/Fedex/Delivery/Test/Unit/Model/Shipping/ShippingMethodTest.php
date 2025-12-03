<?php

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model\Shipping;

use Fedex\Delivery\Model\Shipping\ShippingMethod;
use PHPUnit\Framework\TestCase;

class ShippingMethodTest extends TestCase
{
    public function testShippingMethod(): void
    {
        $method = new ShippingMethod('group1', 'method1', 10.0, 100);

        $this->assertSame('group1', $method->getIdentityGroup());
        $this->assertSame('method1', $method->getMethodCode());
        $this->assertSame(10.0, $method->getAmount());
        $this->assertSame(100, $method->getDeliveryDate());

        $method->setCheapest(true);
        $method->setFastest(false);

        $this->assertTrue($method->isCheapest());
        $this->assertFalse($method->isFastest());
    }

    public function testDefaultValues(): void
    {
        $method = new ShippingMethod('group1', 'method1', 0.0, null);

        $this->assertFalse($method->isCheapest());
        $this->assertFalse($method->isFastest());
    }
}
