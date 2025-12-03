<?php
/**
 * Browser version check configuration
 *
 * @category Fedex
 * @package  Fedex_BrowserVersionCheck
 */

declare(strict_types=1);

namespace Fedex\BrowserVersionCheck\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Fedex\BrowserVersionCheck\ViewModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    /**
     * @return void
     */
    public function testGetConfigReturnsExpectedKeys(): void
    {
        $expectedKeys = [
            'enable',
            'heading',
            'subheading',
            'chrome_minimum_version',
            'edge_minimum_version',
            'firefox_minimum_version',
            'safari_minimum_version'
        ];

        $this->scopeConfig->method('isSetFlag')->willReturn(true);
        $this->scopeConfig->method('getValue')->willReturn('test_value');

        $config = $this->config->getConfig();

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $config);
        }
    }
}
