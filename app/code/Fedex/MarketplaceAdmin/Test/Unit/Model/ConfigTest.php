<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceAdmin
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Model\Config;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ConfigTest extends TestCase
{
    /**
     * Test isMktSelfregEnabled returning true.
     *
     * @return void
     */
    public function testIsMktSelfregEnabledTrue()
    {
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $toggleConfig->method('getToggleConfig')
            ->with(Config::XPATH_ENABLE_MKT_SELFREG_SITE)
            ->willReturn(true);
        $config = new Config($toggleConfig);
        $result = $config->isMktSelfregEnabled();
        $this->assertTrue($result);
    }

    /**
     * Test isMktSelfregEnabled returning false.
     *
     * @return void
     */
    public function testIsMktSelfregEnabledFalse()
    {
        $toggleConfig = $this->createMock(ToggleConfig::class);
        $toggleConfig->method('getToggleConfig')
            ->with(Config::XPATH_ENABLE_MKT_SELFREG_SITE)
            ->willReturn(false);
        $config = new Config($toggleConfig);
        $result = $config->isMktSelfregEnabled();
        $this->assertFalse($result);
    }
}