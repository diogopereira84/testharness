<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Plugin\PriceCurrencyPlugin;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\PriceCurrency;

class PriceCurrencyPluginTest extends TestCase
{
    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfig;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var \Fedex\Cart\Plugin\PriceCurrencyPlugin
     */
    private $plugin;

    /**
     * @var \Magento\Directory\Model\PriceCurrency|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceCurrency;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new PriceCurrencyPlugin(
            $this->toggleConfig,
            $this->logger
        );
        $this->priceCurrency = $this->createMock(PriceCurrency::class);
    }

    public function testAfterRoundWithToggleOn()
    {
        $price = 123.456789;
        $expected = round($price, 6);

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(true);
        $result = $this->plugin->afterRound($this->priceCurrency, $price, $price);
        $this->assertEquals($expected, $result);
    }

    public function testAfterRoundWithToggleOff()
    {
        $price = 123.456789;
        $expected = $price;

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(false);
        $this->logger->expects($this->never())
            ->method('info');
        $result = $this->plugin->afterRound($this->priceCurrency, $price, $price);
        $this->assertEquals($expected, $result);
    }
}
