<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\ProductBundle\Model\ConfigProvider;
use Fedex\ProductBundle\Api\ConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private $configMock;
    private $provider;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->provider = new ConfigProvider($this->configMock);
    }

    public function testGetConfigReturnsToggleEnabled()
    {
        $this->configMock->method('isTigerE468338ToggleEnabled')->willReturn(true);
        $result = $this->provider->getConfig();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('tiger_e468338', $result);
        $this->assertTrue($result['tiger_e468338']);
    }

    public function testGetConfigReturnsToggleDisabled()
    {
        $this->configMock->method('isTigerE468338ToggleEnabled')->willReturn(false);
        $result = $this->provider->getConfig();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('tiger_e468338', $result);
        $this->assertFalse($result['tiger_e468338']);
    }
}

