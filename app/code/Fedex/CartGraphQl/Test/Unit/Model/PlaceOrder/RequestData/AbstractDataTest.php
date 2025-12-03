<?php

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\PlaceOrder\RequestData;

use Fedex\CartGraphQl\Model\PlaceOrder\RequestData\AbstractData;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use PHPUnit\Framework\TestCase;

class AbstractDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|PHPUnit\Framework\MockObject\MockObject
     * Mock object for JSON serializer used in unit tests.
     */
    private $jsonSerializerMock;

    /**
     * @var \Fedex\Cart\Api\Data\CartIntegrationInterface|PHPUnit\Framework\MockObject\MockObject
     * Mock object for CartIntegrationInterface used in unit tests.
     */
    private $integrationMock;

    /**
     * Set up the test environment by initializing mock objects.
     */
    protected function setUp(): void
    {
        $this->jsonSerializerMock = $this->createMock(JsonSerializer::class);
        $this->integrationMock = $this->createMock(CartIntegrationInterface::class);
    }

    /**
     * Tests that the getDataProceedChecker method returns the expected proceed result when the checker is true.
     *
     * @return void
     */
    public function testGetDataProceedCheckerTrueReturnsProceedResult()
    {
        $deliveryData = ['foo' => 'bar'];
        $expectedResult = ['result' => 'proceed'];
        $this->integrationMock->method('getDeliveryData')->willReturn(json_encode($deliveryData));
        $this->jsonSerializerMock->method('unserialize')->willReturn($deliveryData);

        $stub = new class($this->jsonSerializerMock) extends AbstractData {
            public function getIdentifierKey(): string
            {
                return 'test_key';
            }
            public function proceedChecker(?array $deliveryData): bool
            {
                return true;
            }
            public function proceed(CartIntegrationInterface $integration): array
            {
                return ['result' => 'proceed'];
            }
        };

        $result = $stub->getData($this->integrationMock);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests the getData method when the proceed checker returns false.
     */
    public function testGetDataProceedCheckerFalseReturnsNull()
    {
        $deliveryData = ['foo' => 'bar'];
        $this->integrationMock->method('getDeliveryData')->willReturn(json_encode($deliveryData));
        $this->jsonSerializerMock->method('unserialize')->willReturn($deliveryData);

        $stub = new class($this->jsonSerializerMock) extends AbstractData {
            public function getIdentifierKey(): string
            {
                return 'test_key';
            }
            public function proceedChecker(?array $deliveryData): bool
            {
                return false;
            }
            public function proceed(CartIntegrationInterface $integration): array
            {
                return ['result' => 'proceed'];
            }
        };

        $result = $stub->getData($this->integrationMock);
        $this->assertNull($result);
    }

    /**
     * Tests that the getData method returns an empty array when delivery data is null.
     */
    public function testGetDataWithNullDeliveryDataDefaultsToEmptyArray()
    {
        $this->integrationMock->method('getDeliveryData')->willReturn(null);
        $this->jsonSerializerMock->method('unserialize')->with('{}')->willReturn([]);

        $stub = new class($this->jsonSerializerMock) extends AbstractData {
            public function getIdentifierKey(): string
            {
                return 'test_key';
            }
            public function proceedChecker(?array $deliveryData): bool
            {
                return true;
            }
            public function proceed(CartIntegrationInterface $integration): array
            {
                return ['result' => 'proceed'];
            }
        };

        $result = $stub->getData($this->integrationMock);
        $this->assertSame(['result' => 'proceed'], $result);
    }
}
