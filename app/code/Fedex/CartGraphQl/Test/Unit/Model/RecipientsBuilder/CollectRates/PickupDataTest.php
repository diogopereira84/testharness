<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RecipientsBuilder\CollectRates;

use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\PickupData;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;

class PickupDataTest extends TestCase
{
    /**
     * Mock object for integration, used for testing purposes.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockIntegration;

    /**
     * @var (\Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $shippingDelivery;

    /**
     * @var mixed
     *
     * Stores the pickup data used in the test cases.
     */
    protected $pickupData;

    /**
     * Sets up the environment before each test.
     */
    protected function setUp(): void
    {
        $mockRegion = $this->createMock(Region::class);
        $mockIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $mockDateTime = $this->createMock(DateTime::class);
        $mockCartRepository = $this->createMock(CartRepositoryInterface::class);
        $mockInstoreConfig = $this->createMock(InstoreConfig::class);
        $mockJsonSerializer = $this->createMock(Json::class);
        $this->mockIntegration = $this->createMock(CartIntegrationInterface::class);
        $this->shippingDelivery = $this->createMock(ShippingDelivery::class);

        $this->pickupData = new PickupData(
            $mockRegion,
            $mockIntegrationRepository,
            $mockDateTime,
            $mockCartRepository,
            $mockInstoreConfig,
            $mockJsonSerializer,
            $this->shippingDelivery
        );
    }

    /**
     * Tests that the getIdentifierKey method returns the expected constant value.
     *
     * @return void
     */
    public function testGetIdentifierKeyReturnsConstant()
    {
        $expected = PickupData::IDENTIFIER_KEY;
        $this->assertEquals($expected, $this->pickupData->getIdentifierKey());
    }

    /**
     * Tests the proceed functionality of the RecipientsBuilder's CollectRates for PickupData.
     *
     * @return void
     */
    public function testProceed()
    {
        $referenceId = '123';
        $productAssociations = ['productA', 'productB'];
        $requestedPickupLocalTime = '2024-04-19T12:00:00';

        $result = $this->pickupData->proceed(
            $referenceId,
            $this->mockIntegration,
            $productAssociations,
            $requestedPickupLocalTime
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('arrRecipients', $result);
        $this->assertCount(1, $result['arrRecipients']);
        $this->assertEquals(
            $requestedPickupLocalTime,
            $result['arrRecipients'][0]['pickUpDelivery']['requestedPickupLocalTime']
        );
        $this->assertEquals($referenceId, $result['arrRecipients'][0]['reference']);
        $this->assertEquals($productAssociations, $result['arrRecipients'][0]['productAssociations']);
    }

    /**
     * Tests that the proceed method sets the delivery dates fields when the feature is enabled.
     *
     * @return void
     */
    public function testProceedSetsDeliveryDatesFieldsWhenEnabled()
    {
        $referenceId = '123';
        $productAssociations = ['productA', 'productB'];
        $requestedPickupLocalTime = '2024-04-19T12:00:00';
        $requestedDeliveryLocalTime = '2024-04-20T15:00:00';
        $holdUntilDate = '2024-04-21';

        $this->mockIntegration->method('getStoreId')->willReturn('store-1');

        $instoreConfig = (new \ReflectionClass($this->pickupData))->getProperty('instoreConfig');
        $instoreConfig->setAccessible(true);
        $mockInstoreConfig = $this->createMock(\Fedex\InStoreConfigurations\Api\ConfigInterface::class);
        $mockInstoreConfig->method('isDeliveryDatesFieldsEnabled')->willReturn(true);
        $instoreConfig->setValue($this->pickupData, $mockInstoreConfig);

        $result = $this->pickupData->proceed(
            $referenceId,
            $this->mockIntegration,
            $productAssociations,
            $requestedPickupLocalTime,
            $requestedDeliveryLocalTime,
            null,
            $holdUntilDate
        );

        $this->assertEquals($requestedDeliveryLocalTime, $result['arrRecipients'][0]['requestedDeliveryLocalTime']);
        $this->assertEquals($holdUntilDate, $result['arrRecipients'][0]['pickUpDelivery']['holdUntilDate']);
    }
}
