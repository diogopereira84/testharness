<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Address\CollectRates;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Fedex\B2b\Model\Quote\Address as FedexAddress;
use Magento\Quote\Model\Quote;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;

class ShippingRateTest extends TestCase
{
    protected $rateFactoryMock;
    protected $jsonSerializer;
    protected $shippingRate;
    const DELIVERY_DATA_JSON = '{"shipping_account_number":"12345","shipping_estimated_delivery_local_time":"2024-02-28","shipping_location_city":"Fort Lauderdale","shipping_location_country":"US","shipping_location_state":"FL","shipping_location_street":"1501 SE 17TH ST","shipping_location_zipcode":"33316","shipping_method":"FEDEX_HOME_DELIVERY","shipping_price":11.98,"shipping_reference_id":"ABCDE","shipping_title":"FedEx Home Delivery","is_delivery":true}';

    protected function setUp(): void
    {
        $this->rateFactoryMock = $this->createMock(RateFactory::class);
        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->shippingRate = new ShippingRate(
            $this->rateFactoryMock,
            $this->jsonSerializer
        );
    }

    public function testCollectAddsShippingRateToAddress()
    {
        $shippingAddressMock = $this->getMockBuilder(FedexAddress::class)
            ->onlyMethods(['getQuote', 'addShippingRate', 'removeAllShippingRates'])
            ->addMethods(['setShippingMethod', 'setCollectShippingRates'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock = $this->createMock(Quote::class);
        $shippingQuoteRateMock = $this->getMockBuilder(Rate::class)
            ->addMethods([
                'setCarrier',
                'setCarrierTitle',
                'setCode',
                'setMethod',
                'setMethodTitle',
                'setPrice'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rateFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($shippingQuoteRateMock);

        $shippingAddressMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setCarrier')
            ->with(ShippingRate::SHIPPING_RATE_CARRIER);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setCarrierTitle')
            ->with('FedEx Home Delivery');

        $shippingQuoteRateMock->expects($this->once())
            ->method('setCode')
            ->with('fedexshipping_FEDEX_HOME_DELIVERY');

        $shippingQuoteRateMock->expects($this->once())
            ->method('setMethod')
            ->with('FEDEX_HOME_DELIVERY');

        $shippingQuoteRateMock->expects($this->once())
            ->method('setMethodTitle')
            ->with('FEDEX_HOME_DELIVERY');

        $shippingQuoteRateMock->expects($this->once())
            ->method('setPrice')
            ->with(11.98);

        $shippingAddressMock->expects($this->once())
            ->method('setCollectShippingRates')
            ->with(false)
            ->willReturnSelf();

        $shippingAddressMock->expects($this->once())
            ->method('setShippingMethod')
            ->with('fedexshipping_FEDEX_HOME_DELIVERY')
            ->willReturnSelf();

        $shippingAddressMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $shippingAddressMock->expects($this->once())
            ->method('removeAllShippingRates');

        $shippingAddressMock->expects($this->once())
            ->method('addShippingRate')
            ->with($shippingQuoteRateMock);

        $cartIntegrationInterfaceMock = $this->createMock(CartIntegrationInterface::class);

        $cartIntegrationInterfaceMock->expects($this->any())->method('getDeliveryData')
            ->willReturn(self::DELIVERY_DATA_JSON);

        $this->jsonSerializer->expects($this->once())->method('unserialize')
            ->willReturn(json_decode(self::DELIVERY_DATA_JSON, true));


        $this->shippingRate->collect($shippingAddressMock, $cartIntegrationInterfaceMock);
    }
}
