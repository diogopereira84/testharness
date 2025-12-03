<?php

declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Unit\Model;

use Fedex\MarketplacePunchout\Api\ToggleConfigInterface;
use Fedex\MarketplacePunchout\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private ScopeConfigInterface $scopeConfig;
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testUsesStoreScopeByDefault(): void
    {
        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with(
                ToggleConfigInterface::XML_PATH_MARKETPLACE_PUNCHOUT_ADD_SELLERID,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(true);

        $this->assertTrue($this->config->isAddingSellerIdInPunchoutEnabled());
    }
}
