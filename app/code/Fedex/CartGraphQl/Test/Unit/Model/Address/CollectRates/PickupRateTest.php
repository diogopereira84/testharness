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
use Fedex\CartGraphQl\Model\Address\CollectRates\PickupRate;

class PickupRateTest extends TestCase
{
    protected $rateFactoryMock;
    protected $jsonSerializer;
    protected $pickupRate;
    const DELIVERY_DATA_JSON = '{"pickup_location_city":"Frisco","pickup_location_country":"US","pickup_location_date":"2024-01-23T03:00:00","pickup_location_id":"0798","pickup_location_name":"FedEx Office Print & Ship Center","pickup_location_state":"TX","pickup_location_street":"Frisco","pickup_location_zipcode":"75034","pickup_store_id":"0798","is_pickup":true}';

    protected function setUp(): void
    {
        $this->rateFactoryMock = $this->createMock(RateFactory::class);
        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->pickupRate = new PickupRate(
            $this->rateFactoryMock,
            $this->jsonSerializer
        );
    }

    public function testCollectAddsShippingRateToAddress()
    {
        $shippingAddressMock = $this->getMockBuilder(FedexAddress::class)
            ->onlyMethods(['getQuote', 'addShippingRate'])
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
            ->with(PickupRate::SHIPPING_RATE_CARRIER);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setCarrierTitle')
            ->with(PickupRate::SHIPPING_RATE_CARRIER_TITLE);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setCode')
            ->with(PickupRate::SHIPPING_RATE_CODE);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setMethod')
            ->with(PickupRate::SHIPPING_RATE_METHOD);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setMethodTitle')
            ->with(PickupRate::SHIPPING_RATE_METHOD);

        $shippingQuoteRateMock->expects($this->once())
            ->method('setPrice')
            ->with(0);

        $shippingAddressMock->expects($this->once())
            ->method('setCollectShippingRates')
            ->with(false)
            ->willReturnSelf();

        $shippingAddressMock->expects($this->once())
            ->method('setShippingMethod')
            ->with(PickupRate::SHIPPING_RATE_CODE)
            ->willReturnSelf();

        $shippingAddressMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $shippingAddressMock->expects($this->once())
            ->method('addShippingRate')
            ->with($shippingQuoteRateMock);

        $cartIntegrationInterfaceMock = $this->createMock(CartIntegrationInterface::class);
        $cartIntegrationInterfaceMock->expects($this->any())->method('getDeliveryData')
            ->willReturn(self::DELIVERY_DATA_JSON);

        $this->jsonSerializer->expects($this->once())->method('unserialize')
            ->willReturn(json_decode(self::DELIVERY_DATA_JSON, true));


        $this->pickupRate->collect($shippingAddressMock, $cartIntegrationInterfaceMock);
    }
}
