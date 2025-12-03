<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Constants;

use Fedex\MarketplaceCheckout\Model\Constants\ShippingMethod;
use PHPUnit\Framework\TestCase;

class ShippingMethodTest extends TestCase
{
    /**
     * @dataProvider provideNameToCase
     */
    public function testFromStringMatchesByCaseName(string $input, ShippingMethod $expected): void
    {
        $case = ShippingMethod::fromString($input);

        $this->assertInstanceOf(ShippingMethod::class, $case);
        $this->assertSame($expected, $case);
        $this->assertSame($expected->value, $case->value);
    }

    public function provideNameToCase(): array
    {
        return [
            ['TWO_DAY', ShippingMethod::TWO_DAY],
            ['two_day', ShippingMethod::TWO_DAY],
            ["  TWO_DAY\t", ShippingMethod::TWO_DAY],
            ['EXPRESS_SAVER', ShippingMethod::EXPRESS_SAVER],
            ['GROUND_US', ShippingMethod::GROUND_US],
            ['LOCAL_DELIVERY_AM', ShippingMethod::LOCAL_DELIVERY_AM],
            ['LOCAL_DELIVERY_PM', ShippingMethod::LOCAL_DELIVERY_PM],
        ];
    }

    /**
     * @dataProvider provideValueToCase
     */
    public function testFromStringMatchesByValue(string $input, ShippingMethod $expected): void
    {
        $case = ShippingMethod::fromString($input);

        $this->assertInstanceOf(ShippingMethod::class, $case);
        $this->assertSame($expected, $case);
    }

    public function provideValueToCase(): array
    {
        return [
            ['FEDEX_2_DAY', ShippingMethod::TWO_DAY],
            ['FEDEX_EXPRESS_SAVER', ShippingMethod::EXPRESS_SAVER],
            ['FEDEX_GROUND', ShippingMethod::GROUND_US],
            ['FEDEX_2_DAY_AM', ShippingMethod::LOCAL_DELIVERY_AM],
            ['FEDEX_2_DAY_PM', ShippingMethod::LOCAL_DELIVERY_PM],
            [' fedex_2_day ', ShippingMethod::TWO_DAY],
        ];
    }

    /**
     * @dataProvider provideUnknownValues
     */
    public function testFromStringReturnsNullForUnknown(string $input): void
    {
        $this->assertNull(ShippingMethod::fromString($input));
    }

    public function provideUnknownValues(): array
    {
        return [
            [''],
            [" \t \n "],
            ['OVERNIGHT'],
            ['FEDEX_OVERNIGHT'],
        ];
    }
}
