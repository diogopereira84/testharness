<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Plugin\AddressPlugin;
use Fedex\B2b\Model\Quote\Address;
use Fedex\CartGraphQl\Model\Address\CollectRates as GraphQlAddressBuilder;

class AddressPluginTest extends TestCase
{
    public function testBeforeCollectShippingRates()
    {
        $mockGraphQlAddressBuilder = $this->createMock(GraphQlAddressBuilder::class);
        $mockAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCountryId'])
            ->getMock();

        $addressPlugin = new AddressPlugin($mockGraphQlAddressBuilder);

        $mockAddress->expects($this->once())
            ->method('getCountryId')
            ->willReturn('US');

        $mockGraphQlAddressBuilder->expects($this->once())
            ->method('execute')
            ->with($mockAddress);

        $result = $addressPlugin->beforeCollectShippingRates($mockAddress);

        $this->assertFalse($mockAddress->getCollectShippingRates());
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
