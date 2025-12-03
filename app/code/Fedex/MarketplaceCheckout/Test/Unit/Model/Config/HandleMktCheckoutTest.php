<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Config;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HandleMktCheckoutTest extends TestCase
{
    /**
     * @var HandleMktCheckout
     */
    private HandleMktCheckout $model;

    protected function setUp(): void
    {
        $toggleConfig = $this->createMock(ToggleConfig::class);

        $this->model = new HandleMktCheckout($toggleConfig);
    }

    /**
     * Calls a protected method of the class under test.
     * @param string $methodName The name of the protected method to be invoked.
     *
     * @return mixed The result returned by the invoked protected method.
     */
    private function callProtected(string $methodName)
    {
        $ref = new ReflectionMethod(HandleMktCheckout::class, $methodName);
        $ref->setAccessible(true);
        return $ref->invoke($this->model);
    }

    /**
     * Tests the retrieval of the configuration path for enabling the marketplace minicart.
     * @return void
     */
    public function testGetPathEnableMarketplaceMinicart(): void
    {
        $expected = 'environment_toggle_configuration/environment_toggle/enable_marketplace_minicart';
        $this->assertSame($expected, $this->callProtected('getPathEnableMarketplaceMinicart'));
    }

    /**
     * Test for verifying that the getPathCheckoutShippingAccountMessage method returns
     * the correct configuration path for checkout shipping account messaging.
     * @return void
     */
    public function testGetPathCheckoutShippingAccountMessage(): void
    {
        $expected = 'fedex/marketplace_configuration_toast/shipping_account_message';
        $this->assertSame($expected, $this->callProtected('getPathCheckoutShippingAccountMessage'));
    }

    /**
     * Tests the retrieval of the configuration path for enabling the marketplace cart.
     * @return void
     */
    public function testGetPathEnableMarketplaceCart(): void
    {
        $expected = 'environment_toggle_configuration/environment_toggle/enable_marketplace_cart';
        $this->assertSame($expected, $this->callProtected('getPathEnableMarketplaceCart'));
    }

    /**
     * Tests the retrieval of the configuration path for enabling the marketplace checkout.
     * @return void
     */
    public function testGetCheckoutDeliveryMethodsTooltipText(): void
    {
        $expected = 'fedex/marketplace_configuration/delivery_methods_tooltip';
        $this->assertSame($expected, $this->callProtected('getCheckoutDeliveryMethodsTooltipText'));
    }

    /**
     * Tests the retrieval of the configuration path for enabling the marketplace checkout.
     * @return void
     */
    public function testGetPathTigerTeamD180031Fix(): void
    {
        $expected = 'environment_toggle_configuration/environment_toggle/tigerTeam_D180031_fix';
        $this->assertSame($expected, $this->model->getPathTigerTeamD180031Fix());
    }

    /**
     * Tests the retrieval of the configuration path for enabling webhook payload logs.
     * @return void
     */
    public function testGetPathToggleForEnableWebhookPayloadLogs(): void
    {
        $expected = 'fedex/marketplacewebhook/enable_webhook_payload_logs';
        $this->assertSame(
            $expected,
            $this->model->getPathToggleForEnableWebhookPayloadLogs()
        );
    }

    /**
     * Tests the retrieval of the configuration path for TTL block seconds for webhooks.
     * @return void
     */
    public function testGetPathToggleForTtlBlockSeconds(): void
    {
        $expected = 'fedex/marketplacewebhook/webhook_duplicate_block_ttl';
        $this->assertSame(
            $expected,
            $this->model->getPathToggleForTtlBlockSeconds()
        );
    }
}
