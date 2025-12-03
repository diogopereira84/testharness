<?php

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model\Shipping;

use Fedex\Delivery\Model\Shipping\CheapestFastestSelector;
use Fedex\Delivery\Model\Shipping\ShippingMethod;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheapestFastestSelectorTest extends TestCase
{
    private CheapestFastestSelector $selector;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->selector = new CheapestFastestSelector($this->loggerMock);
    }

    public function testApplyCheapestAndFastest(): void
    {
        $method1 = $this->createMock(ShippingMethod::class);
        $method2 = $this->createMock(ShippingMethod::class);
        $method3 = $this->createMock(ShippingMethod::class);

        $method1->method('getIdentityGroup')->willReturn('group1');
        $method1->method('getAmount')->willReturn(10.0);
        $method1->method('getDeliveryDate')->willReturn(100);

        $method2->method('getIdentityGroup')->willReturn('group1');
        $method2->method('getAmount')->willReturn(5.0);
        $method2->method('getDeliveryDate')->willReturn(50);

        $method3->method('getIdentityGroup')->willReturn('group1');
        $method3->method('getAmount')->willReturn(5.0);
        $method3->method('getDeliveryDate')->willReturn(30);

        $method3->expects($this->once())->method('setCheapest')->with(true);
        $method3->expects($this->once())->method('setFastest')->with(true);

        $result = $this->selector->applyCheapestAndFastest([$method1, $method2, $method3]);

        $this->assertCount(3, $result);
    }

    public function testApplyCheapestAndFastestWithInvalidDeliveryDate(): void
    {
        $method = $this->createMock(ShippingMethod::class);
        $method->method('getIdentityGroup')->willReturn('group1');
        $method->method('getAmount')->willReturn(10.0);
        $method->method('getDeliveryDate')->willReturn(null);

        $this->loggerMock->expects($this->once())->method('info');

        $result = $this->selector->applyCheapestAndFastest([$method]);

        $this->assertCount(1, $result);
    }
}
