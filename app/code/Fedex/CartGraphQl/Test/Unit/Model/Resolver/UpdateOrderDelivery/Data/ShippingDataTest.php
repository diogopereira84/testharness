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
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ShippingData;

class ShippingDataTest extends TestCase
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
     * @var \Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\Data\ShippingData
     */
    protected $shippingData;

    /**
     * Set up the test environment
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
        $this->shippingData = new ShippingData(
            $this->region,
            $this->cartIntegrationRepository,
            $this->dateTime,
            $this->cartRepository,
            $this->instoreConfig,
            $this->jsonSerializer
        );
    }

    /**
     * Tests the setData method with valid parameters to ensure correct behavior.
     *
     * @return void
     */
    public function testSetDataWithValidParams(): void
    {
        $this->cartIntegrationRepository->expects($this->once())->method('getByQuoteId')
            ->willReturn($this->cartIntegrationMock);
        $shippingData['shipping_data'] = [
            "shipping_firstname" => "Athira",
            "shipping_lastname" => "Indrakumar",
            "shipping_company" => "Mcf",
            "shipping_location_street1" => "123 Main Street",
            "shipping_location_street2" => "Apartment 2B",
            "shipping_location_street3" => "Anytown, CA 12345",
            "shipping_location_city" => "California",
            "shipping_location_state" => "CA",
            "shipping_location_zipcode" => "92605",
            "shipping_location_country" => "US",
            "shipping_phone_number" => "8877665544",
            "shipping_phone_ext" => "+1",
            "shipping_email" => "aindrakumar@mcfadyen.com",
            "shipping_address_classification" => "BUSINESS",
            'shipping_account_number' => '12345',
            'shipping_estimated_delivery_local_time' => '2024-02-28',
            'shipping_method' => 'FEDEX_HOME_DELIVERY',
            'shipping_price' => 11.98,
            'shipping_reference_id' => 'ABCDE',
            'shipping_title' => 'FedEx Home Delivery',
            'is_delivery' => true
        ];

        $this->jsonSerializer->expects($this->once())->method('serialize')
            ->willReturn(json_encode($shippingData));

        $addressMock = $this->getMockBuilder(Address::class)
            ->onlyMethods(['getId', 'collectShippingRates', 'save', 'addShippingRate'])
            ->addMethods(['getAddressType', 'setShippingMethod', 'setShippingDescription'])
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock->expects($this->exactly(2))->method('getId')->willReturn(100051);

        $this->region->expects($this->any())->method('loadByCode')->willReturnSelf();
        $this->region->expects($this->any())->method('getId')->willReturn(154);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())->method('getBillingAddress')->willReturn($addressMock);
        $quote->expects($this->once())->method('getShippingAddress')->willReturn($addressMock);

        $this->shippingData->setData($quote, $shippingData);
    }

    /**
     * Tests the setDeliveryDatesData method with a valid estimated delivery time.
     */
    public function testSetDeliveryDatesDataWithValidEstimatedDeliveryTime(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['setShippingEstimatedDeliveryLocalTime'])
            ->disableOriginalConstructor()
            ->getMock();

        $shippingDate = '2025-08-01';
        $formattedDate = 'Aug 1, 2025';

        $shippingData = [
            ShippingData::SHIPPING_ESTIMATED_DELIVERY_LOCAL_TIME => $shippingDate
        ];

        $this->instoreConfig
            ->expects($this->once())
            ->method('isDeliveryDatesFieldsEnabled')
            ->willReturn(true);

        $this->dateTime
            ->expects($this->once())
            ->method('formatDate')
            ->with($shippingDate, false)
            ->willReturn($formattedDate);

        $quote
            ->expects($this->once())
            ->method('setShippingEstimatedDeliveryLocalTime')
            ->with($formattedDate);

        $reflection = new \ReflectionClass(ShippingData::class);
        $method = $reflection->getMethod('setDeliveryDatesData');
        $method->setAccessible(true);

        $method->invokeArgs($this->shippingData, [$quote, $shippingData]);
    }
}
