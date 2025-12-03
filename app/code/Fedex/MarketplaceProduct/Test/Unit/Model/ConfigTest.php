<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Fedex\MarketplaceProduct\Model\Config;
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

    /**
     * @covers \Fedex\MarketplaceProduct\Model\Config::isConfigurableMinMaxWrongQtyToggleEnabled
     */
    public function testIsConfigurableMinMaxWrongQtyToggleEnabledReturnsTrueWhenConfigIsEnabled(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CONFIGURABLE_WRONG_MAX_MIN_QUANTITY_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(true);

        $this->assertTrue($this->configModel->isConfigurableMinMaxWrongQtyToggleEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceProduct\Model\Config::isConfigurableMinMaxWrongQtyToggleEnabled
     */
    public function testIsConfigurableMinMaxWrongQtyToggleEnabledReturnsFalseWhenConfigIsDisabled(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CONFIGURABLE_WRONG_MAX_MIN_QUANTITY_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(false);

        $this->assertFalse($this->configModel->isConfigurableMinMaxWrongQtyToggleEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceProduct\Model\Config::isConfigurableMinMaxWrongQtyToggleEnabled
     */
    public function testIsConfigurableMinMaxWrongQtyToggleEnabledReturnsFalseWhenConfigIsNull(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CONFIGURABLE_WRONG_MAX_MIN_QUANTITY_TOGGLE,
                ScopeInterface::SCOPE_STORE
            )
            ->willReturn(null);

        $this->assertFalse($this->configModel->isConfigurableMinMaxWrongQtyToggleEnabled());
    }

    /**
     * @covers \Fedex\MarketplaceProduct\Model\Config::isConfigurableMinMaxWrongQtyToggleEnabled
     */
    public function testIsConfigurableMinMaxWrongQtyToggleEnabledUsesCorrectScope(): void
    {
        $customScope = 'websites';
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Config::XML_PATH_CONFIGURABLE_WRONG_MAX_MIN_QUANTITY_TOGGLE,
                $customScope
            )
            ->willReturn(true);

        $this->assertTrue($this->configModel->isConfigurableMinMaxWrongQtyToggleEnabled($customScope));
    }

    /**
     * @covers \Fedex\MarketplaceProduct\Model\Config::__construct
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(Config::class, $this->configModel);
    }
}