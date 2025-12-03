<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\RateQuote\RecipientsBuilder;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\RateQuote\RecipientsBuilder\AbstractData;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Directory\Model\Region;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CartGraphQl\Model\RateQuote\ShippingDelivery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractDataTest extends TestCase
{
    /** @var Region|MockObject */
    private $region;

    /** @var CartIntegrationRepositoryInterface|MockObject */
    private $cartIntegrationRepository;

    /** @var DateTime|MockObject */
    private $dateTime;

    /** @var CartRepositoryInterface|MockObject */
    private $cartRepository;

    /** @var InstoreConfig|MockObject */
    private $instoreConfig;

    /** @var JsonSerializer|MockObject */
    private $jsonSerializer;

    /** @var ShippingDelivery|MockObject */
    private $shippingDelivery;

    /** @var AbstractData|MockObject */
    private $abstractData;

    protected function setUp(): void
    {
        $this->region = $this->createMock(Region::class);
        $this->cartIntegrationRepository = $this->createMock(CartIntegrationRepositoryInterface::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->shippingDelivery = $this->createMock(ShippingDelivery::class);

        $this->abstractData = $this->getMockForAbstractClass(
            AbstractData::class,
            [
                $this->region,
                $this->cartIntegrationRepository,
                $this->dateTime,
                $this->cartRepository,
                $this->instoreConfig,
                $this->jsonSerializer,
                $this->shippingDelivery
            ]
        );
    }

    /**
     * Tests that getData() returns null when the delivery data is not an array.
     *
     */
    public function testGetDataReturnsNullIfDeliveryDataIsNotArray(): void
    {
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getDeliveryData')->willReturn('not-an-array');

        $this->jsonSerializer->method('unserialize')->willReturn('not-an-array');

        $this->abstractData->method('getIdentifierKey')->willReturn('test_key');

        $result = $this->abstractData->getData(
            'ref1',
            $integration,
            [],
            null,
            null,
            null,
            null
        );

        $this->assertNull($result);
    }

    public function testGetDataReturnsNullIfIdentifierKeyNotInDeliveryData(): void
    {
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getDeliveryData')->willReturn(json_encode(['other_key' => 'value']));

        $this->jsonSerializer->method('unserialize')->willReturn(['other_key' => 'value']);

        $this->abstractData->method('getIdentifierKey')->willReturn('test_key');

        $result = $this->abstractData->getData(
            'ref2',
            $integration,
            [],
            null,
            null,
            null,
            null
        );

        $this->assertNull($result);
    }

    /**
     * Tests that the getData method calls the proceed method with the correct arguments
     * and returns the expected result.
     */
    public function testGetDataCallsProceedAndReturnsResult(): void
    {
        $integration = $this->createMock(CartIntegrationInterface::class);
        $integration->method('getDeliveryData')->willReturn(json_encode(['test_key' => 'value']));

        $this->jsonSerializer->method('unserialize')->willReturn(['test_key' => 'value']);

        $this->abstractData->method('getIdentifierKey')->willReturn('test_key');

        $expectedResult = ['some' => 'data'];

        $this->abstractData->expects($this->any())
            ->method('proceed')
            ->with(
                'ref3',
                $integration,
                ['a' => 1],
                'pickup',
                'delivery',
                'estimated',
                'hold'
            )
            ->willReturn($expectedResult);

        $result = $this->abstractData->getData(
            'ref3',
            $integration,
            ['a' => 1],
            'pickup',
            'delivery',
            'estimated',
            'hold'
        );

        $this->assertSame($expectedResult, $result);
    }
}
