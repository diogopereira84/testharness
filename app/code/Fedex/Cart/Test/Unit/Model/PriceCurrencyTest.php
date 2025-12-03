<?php

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Cart\Model\PriceCurrency;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PriceCurrencyTest extends TestCase
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManager;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currencyFactory;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $toggleConfig;

    /**
     * @var \Fedex\Cart\Model\PriceCurrency
     */
    private $priceCurrency;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->currencyFactory = $this->createMock(CurrencyFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->priceCurrency = new PriceCurrency(
            $this->storeManager,
            $this->currencyFactory,
            $this->logger,
            $this->toggleConfig
        );
    }

    public function testRoundWhenToggleOff()
    {
        $price = 123.456789;
        $expected = round($price, 6);
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(false);
        $result = $this->priceCurrency->round($price);
        $this->assertEquals($expected, $result);
    }

    public function testRoundWhenToggleOn()
    {
        $price = 123.456789;
        $this->toggleConfig->method('getToggleConfigValue')
            ->with('explores_remove_adobe_commerce_override')
            ->willReturn(true);
        $result = $this->priceCurrency->round($price);
        $this->assertEquals($price, $result);
    }
}
