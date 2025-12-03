<?php
declare(strict_types=1);

namespace Fedex\Search\Test\Unit\Helper;

use Fedex\Search\Helper\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $configHelper;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);

        $this->configHelper = new Config($context);
    }

    public function testIsQueryTrackingDisabledReturnsTrue(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with(
                'performance/optimization/disable_query_tracking',
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(true);

        $this->assertTrue($this->configHelper->isQueryTrackingDisabled());
    }

    public function testIsQueryTrackingDisabledReturnsFalse(): void
    {
        $this->scopeConfig->method('isSetFlag')
            ->with(
                'performance/optimization/disable_query_tracking',
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(false);

        $this->assertFalse($this->configHelper->isQueryTrackingDisabled());
    }
}
