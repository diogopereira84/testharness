<?php

declare(strict_types=1);

namespace Fedex\ExpiredItems\Test\Unit\Model;

use Fedex\ExpiredItems\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Config
     */
    private $configModel;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->configModel = new Config($this->scopeConfigMock);
    }

    public function testIsIncorrectCartExpiryMassageToggleEnabledReturnsTrue(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CART_EXPIRY_MESSAGE_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(true);

        $this->assertTrue($this->configModel->isIncorrectCartExpiryMassageToggleEnabled());
    }

    public function testIsIncorrectCartExpiryMassageToggleEnabledReturnsFalse(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CART_EXPIRY_MESSAGE_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(false);

        $this->assertFalse($this->configModel->isIncorrectCartExpiryMassageToggleEnabled());
    }

    public function testIsIncorrectCartExpiryMassageToggleEnabledWithWebsiteScope(): void
    {
        $websiteScope = ScopeInterface::SCOPE_WEBSITE;

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CART_EXPIRY_MESSAGE_TOGGLE,
                $websiteScope
            )
            ->willReturn(true);

        $this->assertTrue($this->configModel->isIncorrectCartExpiryMassageToggleEnabled($websiteScope));
    }
}