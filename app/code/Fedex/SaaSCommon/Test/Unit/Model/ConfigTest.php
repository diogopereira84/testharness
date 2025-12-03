<?php

namespace Fedex\SaaSCommon\Test\Unit\Model;

use Fedex\SaaSCommon\Model\Config;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testIsTigerD200529EnabledReturnsTrue()
    {
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Config::TIGER_D200529)
            ->willReturn(true);

        $config = new Config($toggleConfig);
        $this->assertTrue($config->isTigerD200529Enabled());
    }

    public function testIsTigerD200529EnabledReturnsFalse()
    {
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Config::TIGER_D200529)
            ->willReturn(false);

        $config = new Config($toggleConfig);
        $this->assertFalse($config->isTigerD200529Enabled());
    }
}

