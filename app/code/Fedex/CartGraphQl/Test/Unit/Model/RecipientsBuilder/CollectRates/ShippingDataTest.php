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
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\ShippingData;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ShippingDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     *
     * Mock instance of the JSON serializer used for testing.
     */
    protected $mockJsonSerializer;

    /**
     * Stores the shipping delivery information used in the test cases.
     *
     * @var (\Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery
     *      & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingDelivery;

    /**
     * @var mixed $shippingData
     *
     * Holds the shipping data used for testing purposes in the ShippingDataTest class.
     */
    protected $shippingData;

    /**
     * Mock object for the Integration class used for testing purposes.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockIntegration;

    /**
     * Sets up the environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $mockRegion = $this->createMock(Region::class);
        $mockIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $mockDateTime = $this->createMock(DateTime::class);
        $mockCartRepository = $this->createMock(CartRepositoryInterface::class);
        $mockInstoreConfig = $this->createMock(InstoreConfig::class);
        $this->mockJsonSerializer = $this->createMock(Json::class);
        $this->shippingDelivery = $this->createMock(ShippingDelivery::class);

        $this->shippingData = new ShippingData(
            $mockRegion,
            $mockIntegrationRepository,
            $mockDateTime,
            $mockCartRepository,
            $mockInstoreConfig,
            $this->mockJsonSerializer,
            $this->shippingDelivery
        );

        $this->mockIntegration = $this->createMock(CartIntegrationInterface::class);
    }

    /**
     * Tests that the getIdentifierKey method returns the expected constant value.
     *
     * @return void
     */
    public function testGetIdentifierKeyReturnsConstant()
    {
        $expected = ShippingData::IDENTIFIER_KEY;
        $this->assertEquals($expected, $this->shippingData->getIdentifierKey());
    }

    /**
     * Tests the proceed functionality of the ShippingData class.
     *
     * @return void
     */
    public function testProceed()
    {
        $referenceId = '123';
        $productAssociations = ['productA', 'productB'];
        $requestedPickupLocalTime = '2024-04-19T12:00:00';

        $deliveryData = [
            'shipping_location_street' => '123 Shipping St',
            'shipping_location_city' => 'Shipping City',
            'shipping_location_state' => 'CA',
            'shipping_location_zipcode' => '90210',
            'shipping_location_country' => 'US',
            'shipping_method' => 'Ground',
            'shipping_account_number' => '123456789',
        ];
        $this->mockIntegration->expects($this->once())
            ->method('getDeliveryData')
            ->willReturn(json_encode($deliveryData));
        $this->mockJsonSerializer->expects($this->once())
            ->method('unserialize')->willReturn($deliveryData);

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->mockIntegration,
            $productAssociations,
            $requestedPickupLocalTime
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertCount(1, $result['arrRecipients']);
        $result['arrRecipients'][0]['shipmentDelivery'] = [
            'address' => [
                'streetLines' => ['123 Shipping St', ''],
                'city' => 'Shipping City',
                'stateOrProvinceCode' => 'CA',
                'postalCode' => '90210',
                'countryCode' => 'US',
                'addressClassification' => 'HOME',
            ],
            'holdUntilDate' => null,
            'serviceType' => 'Ground',
            'productionLocationId' => null,
            'fedExAccountNumber' => '123456789',
            'deliveryInstructions' => null,
        ];

        $expectedResult = [
            'arrRecipients' => [
                [
                    'contact' => null,
                    'reference' => $referenceId,
                    'shipmentDelivery' => [
                        'address' => [
                            'streetLines' => ['123 Shipping St', ''],
                            'city' => 'Shipping City',
                            'stateOrProvinceCode' => 'CA',
                            'postalCode' => '90210',
                            'countryCode' => 'US',
                            'addressClassification' => 'HOME',
                        ],
                        'holdUntilDate' => null,
                        'serviceType' => 'Ground',
                        'productionLocationId' => null,
                        'fedExAccountNumber' => '123456789',
                        'deliveryInstructions' => null,
                    ],
                    'productAssociations' => $productAssociations,
                ],
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests the proceedWithLocalDelivery method to ensure it correctly handles
     * the scenario where local delivery is selected as the shipping option.
     *
     * @return void
     */
    public function testProceedWithLocalDelivery()
    {
        $referenceId = 'ref-local';
        $productAssociations = ['product1'];
        $shippingMethod = 'LocalMethod';
        $shippingEstimatedDeliveryLocalTime = '2024-07-10T15:00:00';

        $deliveryData = [
            'shipping_method' => $shippingMethod,
            'shipping_location_street' => '456 Local St',
            'shipping_location_city' => 'Local City',
            'shipping_location_state' => 'TX',
            'shipping_location_zipcode' => '75001',
            'shipping_location_country' => 'US',
        ];

        $this->mockIntegration->expects($this->once())
            ->method('getDeliveryData')
            ->willReturn(json_encode($deliveryData));

        $this->mockJsonSerializer->expects($this->once())
            ->method('unserialize')
            ->willReturn($deliveryData);

        $this->shippingDelivery->expects($this->once())
            ->method('validateIfLocalDelivery')
            ->with($shippingMethod)
            ->willReturn(true);

        $expectedLocalDeliveryData = ['someLocalKey' => 'someLocalValue'];

        $instoreConfig = (new \ReflectionClass($this->shippingData))->getProperty('instoreConfig');
        $instoreConfig->setAccessible(true);
        $mockInstoreConfig = $this->createMock(\Fedex\InStoreConfigurations\Api\ConfigInterface::class);
        $mockInstoreConfig->method('isEnableServiceTypeForRAQ')->willReturn(true);
        $mockInstoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(false);
        $instoreConfig->setValue($this->shippingData, $mockInstoreConfig);

        $this->shippingDelivery->expects($this->once())
            ->method('setLocalDelivery')
            ->with($deliveryData)
            ->willReturn($expectedLocalDeliveryData);

        $this->shippingDelivery->expects($this->never())
            ->method('setExternalDelivery');

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->mockIntegration,
            $productAssociations,
            null,
            null,
            $shippingEstimatedDeliveryLocalTime
        );

        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertCount(1, $result['arrRecipients']);
        $recipient = $result['arrRecipients'][0];

        $this->assertSame($referenceId, $recipient['reference']);
        $this->assertArrayHasKey(ShippingDelivery::LOCAL_DELIVERY, $recipient);
        $this->assertSame($expectedLocalDeliveryData, $recipient[ShippingDelivery::LOCAL_DELIVERY]);
        $this->assertSame($productAssociations, $recipient['productAssociations']);
    }

    /**
     * Tests the proceedWithExternalDelivery method to ensure it handles
     * external delivery scenarios correctly during the shipping data collection process.
     *
     * @return void
     */
    public function testProceedWithExternalDelivery()
    {
        $referenceId = 'ref-external';
        $productAssociations = ['product2'];
        $shippingMethod = 'ExternalMethod';
        $shippingEstimatedDeliveryLocalTime = '2024-07-11T10:00:00';

        $deliveryData = [
            'shipping_method' => $shippingMethod,
            'shipping_location_street' => '789 External St',
            'shipping_location_city' => 'External City',
            'shipping_location_state' => 'NY',
            'shipping_location_zipcode' => '10001',
            'shipping_location_country' => 'US',
        ];

        $this->mockIntegration->expects($this->once())
            ->method('getDeliveryData')
            ->willReturn(json_encode($deliveryData));

        $this->mockJsonSerializer->expects($this->once())
            ->method('unserialize')
            ->willReturn($deliveryData);

        $this->shippingDelivery->expects($this->once())
            ->method('validateIfLocalDelivery')
            ->with($shippingMethod)
            ->willReturn(false);

        $instoreConfig = (new \ReflectionClass($this->shippingData))->getProperty('instoreConfig');
        $instoreConfig->setAccessible(true);
        $mockInstoreConfig = $this->createMock(\Fedex\InStoreConfigurations\Api\ConfigInterface::class);
        $mockInstoreConfig->method('isEnableServiceTypeForRAQ')->willReturn(true);
        $mockInstoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(false);
        $instoreConfig->setValue($this->shippingData, $mockInstoreConfig);

        $expectedExternalDeliveryData = ['someExternalKey' => 'someExternalValue'];

        $this->shippingDelivery->expects($this->once())
            ->method('setExternalDelivery')
            ->with($deliveryData, null, $shippingEstimatedDeliveryLocalTime)
            ->willReturn($expectedExternalDeliveryData);

        $this->shippingDelivery->expects($this->never())
            ->method('setLocalDelivery');

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->mockIntegration,
            $productAssociations,
            null,
            null,
            $shippingEstimatedDeliveryLocalTime
        );

        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertCount(1, $result['arrRecipients']);
        $recipient = $result['arrRecipients'][0];

        $this->assertSame($referenceId, $recipient['reference']);
        $this->assertArrayHasKey(ShippingDelivery::EXTERNAL_DELIVERY, $recipient);
        $this->assertSame($expectedExternalDeliveryData, $recipient[ShippingDelivery::EXTERNAL_DELIVERY]);
        $this->assertSame($productAssociations, $recipient['productAssociations']);
    }

    /**
     * Tests that the proceed method sets the requested delivery local time
     * when the corresponding feature is enabled.
     *
     * @return void
     */
    public function testProceedSetsRequestedDeliveryLocalTimeWhenEnabled()
    {
        $referenceId = 'ref-delivery-dates';
        $productAssociations = ['product3'];
        $shippingMethod = 'SomeMethod';
        $shippingEstimatedDeliveryLocalTime = '2024-07-12T10:00:00';
        $requestedDeliveryLocalTime = '2024-07-13T15:00:00';

        $deliveryData = [
            'shipping_method' => $shippingMethod,
            'shipping_location_street' => '123 Test St',
            'shipping_location_city' => 'Test City',
            'shipping_location_state' => 'CA',
            'shipping_location_zipcode' => '90001',
            'shipping_location_country' => 'US',
        ];

        $this->mockIntegration->expects($this->once())
            ->method('getDeliveryData')
            ->willReturn(json_encode($deliveryData));

        $this->mockJsonSerializer->expects($this->once())
            ->method('unserialize')
            ->willReturn($deliveryData);

        $this->shippingDelivery->expects($this->once())
            ->method('validateIfLocalDelivery')
            ->with($shippingMethod)
            ->willReturn(true);

        $expectedLocalDeliveryData = ['someLocalKey' => 'someLocalValue'];

        $instoreConfig = (new \ReflectionClass($this->shippingData))->getProperty('instoreConfig');
        $instoreConfig->setAccessible(true);
        $mockInstoreConfig = $this->createMock(\Fedex\InStoreConfigurations\Api\ConfigInterface::class);
        $mockInstoreConfig->method('isEnableServiceTypeForRAQ')->willReturn(true);
        $mockInstoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $instoreConfig->setValue($this->shippingData, $mockInstoreConfig);

        $this->shippingDelivery->expects($this->once())
            ->method('setLocalDelivery')
            ->with($deliveryData)
            ->willReturn($expectedLocalDeliveryData);

        $this->shippingDelivery->expects($this->never())
            ->method('setExternalDelivery');

        $result = $this->shippingData->proceed(
            $referenceId,
            $this->mockIntegration,
            $productAssociations,
            null,
            $requestedDeliveryLocalTime,
            $shippingEstimatedDeliveryLocalTime
        );

        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertCount(1, $result['arrRecipients']);
        $recipient = $result['arrRecipients'][0];

        $this->assertSame($referenceId, $recipient['reference']);
        $this->assertArrayHasKey(ShippingDelivery::LOCAL_DELIVERY, $recipient);
        $this->assertArrayHasKey('requestedDeliveryLocalTime', $recipient[ShippingDelivery::LOCAL_DELIVERY]);
        $this->assertSame(
            $requestedDeliveryLocalTime,
            $recipient[ShippingDelivery::LOCAL_DELIVERY]['requestedDeliveryLocalTime']
        );
        $this->assertSame($productAssociations, $recipient['productAssociations']);
    }
}
