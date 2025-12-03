<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Config;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Model\Config\ToggleBase;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Test\Unit\Model\Config\TestableToggleBase;

/**
 * @covers \Fedex\MarketplaceCheckout\Model\Config\ToggleBase
 */
class ToggleBaseTest extends TestCase
{
    /**
     * @var ToggleConfig
     */
    private ToggleConfig $toggleConfig;
    /**
     * @var TestableToggleBase
     */
    private TestableToggleBase $model;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->model = new TestableToggleBase($this->toggleConfig);
    }

    /**
     * @dataProvider stringConfigProvider
     */
    public function testStringConfigMethods(string $method, string $path, string $returnValue): void
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with($path)
            ->willReturn($returnValue);

        $this->assertSame(
            $returnValue,
            $this->model->{$method}()
        );
    }

    /**
     * @return array
     */
    public function stringConfigProvider(): array
    {
        return [
            'checkout_delivery_methods_tooltip' => [
                'getCheckoutDeliveryMethodsTooltip',
                'tooltip.path',
                'Sample tooltip'
            ],
            'checkout_shipping_account_message' => [
                'getCheckoutShippingAccountMessage',
                'shipping.message',
                'Account message'
            ],
        ];
    }

    /**
     * @dataProvider booleanConfigProvider
     */
    public function testBooleanConfigMethods(string $method, string $path, $configValue, bool $expected): void
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with($path)
            ->willReturn($configValue);

        $this->assertSame(
            $expected,
            $this->model->{$method}()
        );
    }

    /**
     * @return array
     */
    public function booleanConfigProvider(): array
    {
        return [
            'tiger_team_fix_true' => [
                'getTigerTeamD180031Fix',
                'tiger.fix',
                true,
                true
            ],
            'tiger_team_fix_false' => [
                'getTigerTeamD180031Fix',
                'tiger.fix',
                false,
                false
            ]
        ];
    }

    /**
     * Tests the retrieval of the duplicate webhook blocker toggle configuration.
     * @return void
     */
    public function testGetDuplicateWebhookBlockerToggle(): void
    {
        $expectedPath = 'tiger_team_tk_4410123_duplicated_shipment_webhook_blocker';

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with($expectedPath)
            ->willReturn(true);

        $this->assertTrue(
            $this->model->getDuplicateWebhookBlockerToggle()
        );
    }

    /**
     * @dataProvider togglePayloadWebhookLogsProvider
     */
    public function testGetTogglePayloadWebhookLogs($configValue, bool $expected): void
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('webhook.payload.logs')
            ->willReturn($configValue);

        $this->assertSame(
            $expected,
            $this->model->getTogglePayloadWebhookLogs()
        );
    }

    /**
     * @return array
     */
    public function togglePayloadWebhookLogsProvider(): array
    {
        return [
            'payload_logs_true' => [
                true,
                true
            ],
            'payload_logs_false' => [
                false,
                false
            ],
            'payload_logs_1' => [
                1,
                true
            ],
            'payload_logs_0' => [
                0,
                false
            ],
            'payload_logs_null' => [
                null,
                false
            ],
        ];
    }

    /**
     * Tests the retrieval of the TTL block webhook in seconds configuration.
     * @return void
     */
    public function testGetTtlBlockWebhookInSeconds(): void
    {
        $expectedValue = 3600;

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfig')
            ->with('ttl.block.seconds')
            ->willReturn($expectedValue);

        $this->assertSame(
            $expectedValue,
            $this->model->getTtlBlockWebhookInSeconds()
        );
    }
}
