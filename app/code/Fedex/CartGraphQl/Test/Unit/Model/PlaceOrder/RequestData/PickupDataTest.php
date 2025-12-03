<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\PlaceOrder\RequestData;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\PlaceOrder\RequestData\PickupData;
use Magento\Framework\Serialize\Serializer\Json;

class PickupDataTest extends TestCase
{
    /**
     * @var CartIntegrationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockIntegration;

    /**
     * @var PickupData
     */
    protected $pickupData;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $mockJsonSerializer = $this->createMock(Json::class);
        $this->mockIntegration = $this->createMock(CartIntegrationInterface::class);

        $this->pickupData = new PickupData(
            $mockJsonSerializer
        );
    }

    /**
     * Test for the proceed method of the PickupData class
     *
     * @return void
     */
    public function testProceed()
    {
        $pickupLocationDate = "2023-12-31 00:00:00";

        $this->mockIntegration->expects(static::once())
            ->method('getPickupLocationDate')
            ->willReturn($pickupLocationDate);

        $result = $this->pickupData->proceed(
            $this->mockIntegration,
        );
        $expectedResult = json_encode([
            "addressInformation" => [
                "estimate_pickup_time" => "2023-12-31T00:00:00",
                "estimate_pickup_time_for_api" => "2023-12-31T00:00:00"
            ]
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pickupData', $result);
        $this->assertEquals($expectedResult, $result['pickupData']);
    }

    /**
     * Test getIdentifierKey method
     *
     * @return void
     */
    public function testProceedChecker()
    {
        $resultTrue = $this->pickupData->proceedChecker(
            ['is_pickup' => true]
        );
        $resultTrueEmpty = $this->pickupData->proceedChecker(
            []
        );
        $resultFalse = $this->pickupData->proceedChecker(
            ['is_shipping' => true]
        );

        $this->assertEquals(true, $resultTrue);
        $this->assertEquals(true, $resultTrueEmpty);
        $this->assertEquals(false, $resultFalse);
    }

    /**
     * Test getEstimatePickupTime method with invalid date format
     *
     * @return void
     */
    public function testGetEstimatePickupTimeWithInvalidDate(): void
    {
        $mockIntegration = $this->createMock(CartIntegrationInterface::class);

        $mockIntegration->expects($this->once())
            ->method('getPickupLocationDate')
            ->willReturn('invalid-date-format');

        $result = $this->pickupData->proceed($mockIntegration);

        $expectedResult = json_encode([
            "addressInformation" => [
                "estimate_pickup_time" => null,
                "estimate_pickup_time_for_api" => null
            ]
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pickupData', $result);

        $this->assertEquals($expectedResult, $result['pickupData']);
    }
}
