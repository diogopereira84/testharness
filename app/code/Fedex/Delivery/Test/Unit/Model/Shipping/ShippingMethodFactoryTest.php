<?php

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model\Shipping;

use Fedex\Delivery\Model\Shipping\ShippingMethod;
use Fedex\Delivery\Model\Shipping\ShippingMethodFactory;
use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use PHPUnit\Framework\TestCase;

class ShippingMethodFactoryTest extends TestCase
{
    private ShippingMethodFactory $factory;
    private $extensionFactoryMock;

    protected function setUp(): void
    {
        $this->extensionFactoryMock = $this->createMock(ShippingMethodExtensionFactory::class);
        $this->factory = new ShippingMethodFactory($this->extensionFactoryMock);
    }

    public function testCreateFromArray(): void
    {
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        $shippingMethodMock->method('getCarrierCode')->willReturn('carrier1');
        $shippingMethodMock->method('getMethodCode')->willReturn('method1');
        $shippingMethodMock->method('getAmount')->willReturn(10.0);
        $shippingMethodMock->method('getMethodTitle')->willReturn('End of Day');

        $shippingMethodArrayExample = [
            'carrier_code' => 'carrier1',
            'method_code' => 'method1',
            'amount' => 10.0,
            'deliveryDate' => 'End of Day',
        ];

        $result = $this->factory->createFromArray([$shippingMethodMock, $shippingMethodArrayExample]);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(ShippingMethod::class, $result[0]);
    }

    public function testCreateFromArrayWithEmptyArray(): void
    {
        $result = $this->factory->createFromArray([]);

        $this->assertEmpty($result);
    }

    public function testConvertToArray(): void
    {
        $shippingMethodExtensionMock = $this->getMockBuilder(\Magento\Quote\Api\Data\ShippingMethodExtension::class)
            ->onlyMethods(['setCheapest', 'setFastest'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->extensionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($shippingMethodExtensionMock);
        $shippingMethodMockArray = [
            $this->createMock(ShippingMethodInterface::class),
            [
                'carrier_code' => 'carrier1',
                'method_code' => 'method1',
                'amount' => 10.0,
                'deliveryDate' => 'End of Day',
            ]
        ];
        $updatedMethod = new ShippingMethod('group1', 'method1', 10.0, 100);
        $updatedMethodArray = new ShippingMethod('carrier1', 'method1', 10.0, 50);

        $result = $this->factory->convertToArray($shippingMethodMockArray, [$updatedMethod, $updatedMethodArray]);

        $this->assertNotEmpty($result);
    }
}
