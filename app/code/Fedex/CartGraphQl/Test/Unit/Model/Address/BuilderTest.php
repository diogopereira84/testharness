<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Address;

use Fedex\CartGraphQl\Model\Address\Builder;
use Magento\Directory\Model\Region;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class BuilderTest extends TestCase
{
    protected $rateMock;
    public const REQUEST_MOCK_DATA = [
        "cart_id" => "123",
        "contact_information" => [
            "retail_customer_id" => "1003809192",
            "firstname" => "John",
            "lastname" => "Doe",
            "email" => "john.doe@mail.com",
            "telephone" => "(123) 456-7890",
            "ext" => "1234",
            "alternate_contact" => [
                "firstname" => "Mary",
                "lastname" => "Doe",
                "email" => "mary.doe@mail.com",
                "telephone" => "(098) 765-4321",
                "ext" => "5678",
            ]
        ],
        "pickup_data" => [
            "pickup_location_id" => 1492,
            "pickup_store_id" => 1492,
            "pickup_location_name" => "Ft. Lauderdale FL SE 17th",
            "pickup_location_street" => "1501 SE 17TH ST",
            "pickup_location_city" => "Fort Lauderdale",
            "pickup_location_state" => "FL",
            "pickup_location_zipcode" => "33316",
            "pickup_location_country" => "US",
            "pickup_location_date" => "2021-12-22T10:30:00"
        ]
    ];

    /**
     * @var MockObject
     */
    private $getRegionMock;

    /**
     * @var MockObject
     */
    private $rateFactoryMock;

    /**
     * @var MockObject
     */
    private $cartMock;

    /**
     * @var MockObject
     */
    private $addressMock;

    /**
     * @var MockObject
     */
    private Builder $addressBuilder;

    protected function setUp(): void
    {
        $this->getRegionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getRegionMock->expects($this->any())
            ->method('loadByCode')
            ->willReturnSelf();
        $this->rateFactoryMock = $this->getMockBuilder(RateFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->rateMock = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->addMethods(['setCarrier'])
            ->getMockForAbstractClass();
        $this->cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods(['getId', 'save', 'addShippingRate'])
            ->addMethods(['getAddressType', 'setShippingMethod', 'setShippingDescription'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rateFactoryMock->method('create')->willReturn($this->rateMock);

        $this->addressBuilder = new Builder(
            $this->getRegionMock,
            $this->rateFactoryMock
        );
    }

    /**
     * Check that addresses updated
     *
     * @return void
     * @throws \Exception
     */
    public function testAddressesUpdated()
    {
        $this->addressMock->expects($this->exactly(2))->method('getId')->willReturn(1);
        $this->addressMock->expects($this->exactly(2))->method('getAddressType')
            ->willReturnOnConsecutiveCalls('shipping', 'billing');
        $this->addressMock->expects($this->exactly(2))->method('save');
        $this->cartMock->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($this->addressMock);
        $this->cartMock->expects($this->exactly(1))
            ->method('getShippingAddress')
            ->willReturn($this->addressMock);

        $shippingContact = self::REQUEST_MOCK_DATA['contact_information'];
        $pickupData = self::REQUEST_MOCK_DATA['pickup_data'];

        $this->addressBuilder->setAddressData(
            $this->cartMock,
            $shippingContact,
            $pickupData
        );
    }

    /**
     * @return void
     */
    public function testSetShippingData()
    {
        $this->addressMock->expects($this->once())->method('setShippingMethod')->willReturnSelf();
        $this->addressMock->expects($this->once())->method('addShippingRate')->willReturnSelf();
        $this->cartMock->expects($this->exactly(1))
            ->method('getShippingAddress')
            ->willReturn($this->addressMock);

        $this->rateMock->expects($this->once())
            ->method('setCarrier')
            ->willReturn(true);

        $this->addressBuilder->setShippingData(
            $this->cartMock,
            $this->addressMock
        );
    }
}
