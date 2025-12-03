<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver\UpdateOrderDelivery\Data;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Magento\Quote\Model\Quote;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData;

class PickupDataTest extends TestCase
{
    /**
     * @var (\Magento\Directory\Model\Region & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $region;

    /**
     * @var (\Fedex\Cart\Api\CartIntegrationRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartIntegrationRepository;

    /**
     * @var (\Magento\Framework\Stdlib\DateTime & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dateTime;

    /**
     * @var (\Magento\Quote\Api\CartRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartRepository;

    /**
     * @var (\Fedex\InStoreConfigurations\Api\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $instoreConfig;

    /**
     * @var (\Fedex\Cart\Api\Data\CartIntegrationInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $cartIntegrationMock;

    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonSerializer;

    /**
     * @var \Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\PickupData
     */
    protected $pickupData;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        $this->region = $this->getMockBuilder(Region::class)
            ->onlyMethods(['loadByCode', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->pickupData = new PickupData(
            $this->region,
            $this->cartIntegrationRepository,
            $this->dateTime,
            $this->cartRepository,
            $this->instoreConfig,
            $this->jsonSerializer
        );
    }

    /**
     * Tests the setData method with valid parameters.
     *
     * @return void
     */
    public function testSetDataWithValidParams(): void
    {
        $this->cartIntegrationRepository->expects($this->any())->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $pickupData['pickup_data'] = [
            'pickup_location_id' => '0798',
            'pickup_store_id' => '0798',
            'pickup_location_name' => 'FedEx Office Print & Ship Center',
            'pickup_location_street' => 'Frisco',
            'pickup_location_city' => 'Frisco',
            'pickup_location_state' => 'TX',
            'pickup_location_zipcode' => '75034',
            'pickup_location_country' => 'US'
        ];

        $this->jsonSerializer->expects($this->any())->method('serialize')
            ->willReturn(json_encode($pickupData));

        $addressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods(['getId', 'collectShippingRates', 'save', 'addShippingRate'])
            ->addMethods(['getAddressType', 'setShippingMethod', 'setShippingDescription'])
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock->expects($this->exactly(2))->method('getId')->willReturn(100051);

        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(154);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->any())->method('getBillingAddress')->willReturn($addressMock);
        $quote->expects($this->any())->method('getShippingAddress')->willReturn($addressMock);

        $this->pickupData->setData($quote, $pickupData);
    }

    /**
     * Tests the functionality of setting the shipping description for a shipping address.
     */
    public function testSetShippingDescriptionForShippingAddress(): void
    {
        $billingAddressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods([
                'getId',
                'setStreet',
                'setCity',
                'setPostcode',
                'setCountryId',
                'setRegionId',
                'save'
            ])
            ->addMethods([
                'setShippingDescription',
                'getAddressType'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->method('getId')->willReturn(1);
        $billingAddressMock->method('getAddressType')->willReturn('billing');

        $shippingAddressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods([
                'getId',
                'setStreet',
                'setCity',
                'setPostcode',
                'setCountryId',
                'setRegionId',
                'save'
            ])
            ->addMethods([
                'setShippingDescription',
                'getAddressType'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $shippingAddressMock->method('getId')->willReturn(2);
        $shippingAddressMock->method('getAddressType')->willReturn('shipping');
        $shippingAddressMock->expects($this->once())
            ->method('setShippingDescription')
            ->with('1234');

        $quote = $this->createMock(Quote::class);
        $quote->method('getBillingAddress')->willReturn($billingAddressMock);
        $quote->method('getShippingAddress')->willReturn($shippingAddressMock);

        $pickupData = [
            'pickup_data' => [
                'pickup_location_id' => '1234',
                'pickup_location_street' => '123 Street',
                'pickup_location_city' => 'CityName',
                'pickup_location_state' => 'ST',
                'pickup_location_zipcode' => '12345',
                'pickup_location_country' => 'US',
            ]
        ];

        $this->cartIntegrationRepository->method('getByQuoteId')->willReturn($this->cartIntegrationMock);
        $this->region->method('loadByCode')->willReturnSelf();
        $this->region->method('getId')->willReturn(99);

        $this->pickupData = $this->getMockBuilder(PickupData::class)
            ->setConstructorArgs([
                $this->region,
                $this->cartIntegrationRepository,
                $this->dateTime,
                $this->cartRepository,
                $this->instoreConfig,
                $this->jsonSerializer
            ])
            ->onlyMethods(['getDeliveryDataFormatted'])
            ->getMock();

        $this->pickupData->method('getDeliveryDataFormatted')->willReturn('some_string');

        $this->pickupData->setData($quote, $pickupData);
    }

    /**
     * Tests that the hold until date is set correctly when a pickup location date is provided.
     *
     * @return void
     */
    public function testSetHoldUntilDateWhenPickupLocationDateIsSet(): void
    {
        $pickupDate = '2025-07-15';
        $formattedDate = '07/15/2025';

        $pickupData = [
            'pickup_data' => [
                'pickup_location_id' => '0798',
                'pickup_store_id' => '0798',
                'pickup_location_name' => 'FedEx Office Print & Ship Center',
                'pickup_location_street' => 'Main Street',
                'pickup_location_city' => 'Frisco',
                'pickup_location_state' => 'TX',
                'pickup_location_zipcode' => '75034',
                'pickup_location_country' => 'US',
                'pickup_location_date' => $pickupDate
            ]
        ];

        $this->instoreConfig
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(true);

        $this->dateTime->expects($this->once())
            ->method('formatDate')
            ->with($pickupDate, false)
            ->willReturn($formattedDate);

        $this->cartIntegrationRepository
            ->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);

        $this->region->method('loadByCode')->willReturnSelf();
        $this->region->method('getId')->willReturn(154);

        $addressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods([
                'getId',
                'setStreet',
                'setCity',
                'setPostcode',
                'setCountryId',
                'setRegionId',
                'save'
            ])
            ->addMethods([
                'getAddressType',
                'setShippingDescription'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock->method('getId')->willReturn(1);
        $addressMock->method('getAddressType')->willReturn('shipping');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->onlyMethods([
                'getBillingAddress',
                'getShippingAddress',
            ])
            ->addMethods(['setHoldUntilDate'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->method('getBillingAddress')->willReturn($addressMock);
        $quoteMock->method('getShippingAddress')->willReturn($addressMock);

        $quoteMock->expects($this->once())
            ->method('setHoldUntilDate')
            ->with($formattedDate);

        $this->pickupData = $this->getMockBuilder(PickupData::class)
            ->setConstructorArgs([
                $this->region,
                $this->cartIntegrationRepository,
                $this->dateTime,
                $this->cartRepository,
                $this->instoreConfig,
                $this->jsonSerializer
            ])
            ->onlyMethods(['getDeliveryDataFormatted'])
            ->getMock();

        $this->pickupData->method('getDeliveryDataFormatted')->willReturn('some_string');

        $this->pickupData->setData($quoteMock, $pickupData);
    }
}
