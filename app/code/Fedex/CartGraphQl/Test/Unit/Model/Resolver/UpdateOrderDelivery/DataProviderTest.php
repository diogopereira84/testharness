<?php

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver\UpdateOrderDelivery;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Exception\GraphQlParamNullException;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataProvider;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * string MESSAGE
     */
    private const MESSAGE = 'Some message';

    /**
     * datetime ESTIMATED_DELIVERY_TIME
     */
    private const ESTIMATED_DELIVERY_TIME = '2024-01-23T03:00:00';

    /** @var CartIntegrationRepositoryInterface */
    private CartIntegrationRepositoryInterface $cartIntegrationRepository;

    /** @var Quote  */
    private Quote $quote;

    /** @var CartIntegrationInterface  */
    private CartIntegrationInterface $integration;

    /** @var CustomerInterface */
    private CustomerInterface $customer;

    /** @var Address  */
    private Address $address;

    /** @var DataProvider  */
    private DataProvider $object;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->integration = $this->createMock(CartIntegrationInterface::class);
        $this->customer = $this->createMock(CustomerInterface::class);
        $this->address = $this->createMock(Address::class);
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getId',
                    'getBillingAddress',
                    'getShippingAddress',
                    'getData'
                ]
            )
            ->addMethods(
                [
                    'getCustomerId',
                    'getCustomerFirstname',
                    'getCustomerLastname',
                    'getCustomerEmail',
                    'getGtn',
                    'getDeliveryLines'
                ]
            )->getMock();

        $toggleConfigMock = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = new DataProvider($this->cartIntegrationRepository, $toggleConfigMock);
    }

    /**
     * @return void
     */
    public function testGetFormattedData(): void
    {
        $this->quote->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        $this->cartIntegrationRepository->expects($this->atLeastOnce())
            ->method('getByQuoteId')
            ->willReturn($this->integration);

        $this->integration->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn(1);

        $this->integration->expects($this->atLeastOnce())
            ->method('getLocationId')
            ->willReturn(1);

        $this->quote->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($this->address);

        $this->quote->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->address);

        $this->quote->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn(true);

        $this->address->expects($this->any())
            ->method('getFirstname')
            ->willReturn('testFirstName');

        $this->address->expects($this->any())
            ->method('getLastname')
            ->willReturn('testLastName');

        $this->address->expects($this->any())
            ->method('getEmail')
            ->willReturn('test@email.com');

        $this->address->expects($this->any())
            ->method('getTelephone')
            ->willReturn('123456789');

        $this->quote->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);

        $this->quote->expects($this->any())
            ->method('getCustomerFirstname')
            ->willReturn('testFirstName');

        $this->quote->expects($this->any())
            ->method('getCustomerLastname')
            ->willReturn('testFirstName');

        $this->quote->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('testFirstName');

        $this->quote->expects($this->any())
            ->method('getGtn')
            ->willReturn(null);

        $exception = new GraphQlParamNullException(__(self::MESSAGE));
        $this->quote->expects($this->any())
            ->method('getDeliveryLines')
            ->willReturn(
                [
                    "recipientReference" => null,
                    "linePrice" => null,
                    "estimatedDeliveryLocalTime" => self::ESTIMATED_DELIVERY_TIME,
                    "deliveryLinePrice" => null,
                    "priceable" => false,
                    "deliveryRetailPrice" => null,
                    "deliveryDiscountAmount" => null
                ])
            ->willThrowException($exception);
        if (!$this->quote->getItemsCount()) {
            $this->assertEquals(
                $this->getExpectedResultForEmptyQuote(),
                $this->object->getFormattedData(
                    $this->quote,
                    []
                )
            );
        } else {
            $this->assertEquals(
                $this->getExpectedResult(),
                $this->object->getFormattedData(
                    $this->quote,
                    $this->getRateQuoteResponse()
                )
            );
        }
    }

    /**
     * @return array
     */
    private function getExpectedResult(): array
    {
        return json_decode(
            '{"store_id":1,"location_id":1,"currency":"USD","contact_information":{"retailcustomerid":1,"firstname":"testFirstName","lastname":"testFirstName","email":"testFirstName","telephone":"123456789","ext":true,"has_alternate_person":true,"alternate_contact":{"firstname":"testFirstName","lastname":"testLastName","email":"test@email.com","telephone":"123456789","ext":true}},"deliveryLines":[{"recipientReference":null,"linePrice":null,"estimatedDeliveryLocalTime":"2024-01-23T03:00:00","deliveryLinePrice":null,"priceable":false,"deliveryRetailPrice":null,"deliveryDiscountAmount":null}],"gtn":null}',
            true
        );
    }

    private function getExpectedResultForEmptyQuote(): array
    {
        return json_decode(
            '{"store_id":1,"location_id":1,"currency":"USD","contact_information":{"retailcustomerid":1,"firstname":"testFirstName","lastname":"testFirstName","email":"testFirstName","telephone":"123456789","ext":true,"has_alternate_person":true,"alternate_contact":{"firstname":"testFirstName","lastname":"testLastName","email":"test@email.com","telephone":"123456789","ext":true}},"recipient_information":{"shipping_firstname": "testFirstName","shipping_lastname": "testLastName","shipping_company": null,"shipping_location_city": null,"shipping_location_state": null,"shipping_location_zipcode": null,"shipping_location_country": null,"shipping_phone_number": "123456789","shipping_phone_ext": null,"shipping_email": "test@email.com","shipping_address_classification": null, "shipping_location_street": null},"deliveryLines":[{"recipientReference":null,"linePrice":null,"estimatedDeliveryLocalTime":null,"deliveryLinePrice":null,"priceable":null,"deliveryRetailPrice":null,"deliveryDiscountAmount":null}],"gtn":null}',
            true
        );
    }

    /**
     * @return array
     */
    private function getRateQuoteResponse(): array
    {
        return json_decode(
            '{"errors":[],"output":{"alerts":[],"rateQuote":{"currency":"USD","rateQuoteDetails":[{"deliveriesTotalAmount":0,"deliveryLines":[{"deliveryLineId":"5756","estimatedDeliveryLocalTime":"2024-01-23T03:00:00","pickupDetails":{"locationName":"0264"},"priceable":false}],"discounts":[{"amount":12.01,"type":"QUANTITY"}],"estimatedVsActual":"ACTUAL","grossAmount":40.78,"netAmount":28.77,"productLines":[{"instanceId":"136319","name":"Flyer","priceable":true,"productDiscountAmount":0,"productId":"1447174746733","productLineDetails":[{"description":"CLR 1S on 32# Wht","detailCategory":"PRINTING","detailCode":"0224","detailDiscountedUnitPrice":0,"detailDiscountPrice":0,"detailDiscounts":[],"detailPrice":0.78,"detailUnitPrice":0.78,"priceOverridable":false,"priceRequired":false,"quantity":1,"unitQuantity":1}],"productLineDiscounts":[],"productLinePrice":0.78,"productRetailPrice":0.78,"productTaxAmount":0.06,"type":"PRINT_ORDER","unitOfMeasurement":"EACH","unitQuantity":1,"userProductName":"Flyers"},{"instanceId":"136320","name":"Postcards","priceable":true,"productDiscountAmount":12.01,"productId":"1559886500133","productLineDetails":[{"description":"PC 4x6 SS 100lb Mt","detailCategory":"PRINTING","detailCode":"52815","detailDiscountedUnitPrice":0.2402,"detailDiscountPrice":12.01,"detailDiscounts":[{"amount":12.01,"type":"QUANTITY"}],"detailPrice":27.99,"detailUnitPrice":0.8,"priceOverridable":false,"priceRequired":false,"quantity":50,"unitQuantity":50}],"productLineDiscounts":[{"amount":12.01,"type":"QUANTITY"}],"productLinePrice":27.99,"productRetailPrice":40,"productTaxAmount":2.49,"type":"PRINT_ORDER","unitOfMeasurement":"EACH","unitQuantity":50,"userProductName":"Postcards"}],"productsTotalAmount":40.78,"rateQuoteId":"eyJxdW90ZUlkIjoiZTg1MDViNWMtYWZkYi00MTA2LThlMmItMGM0NThkNjVjZTE0IiwiY2FydElkIjoiZjE0NDFhNTItM2RjNy00MTE3LTkzNDctZmVlMjE3OWJjMGJjIn0=","responsibleLocationId":"NYCKK","supportContact":{"address":{"city":"New York","countryCode":"US","postalCode":"10011","stateOrProvinceCode":"NY","streetLines":["111 W 11th St",null]},"email":"test123@fedex.com","phoneNumberDetails":{"phoneNumber":{"number":"111.222.3333"},"usage":"PRIMARY"}},"taxableAmount":28.77,"taxAmount":2.55,"totalAmount":31.32,"totalDiscountAmount":12.01}]}},"transactionId":"b098ccd1-ac2e-4ee5-9073-52d50aa225b6"}',
            true
        );
    }
}
