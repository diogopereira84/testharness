<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\ProductBundle\Model\Config;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private $scopeConfig;
    private $toggleConfig;
    private $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->config = new Config($this->scopeConfig, $this->toggleConfig);
    }

    public function testIsTigerE468338ToggleEnabledTrue()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Config::XML_PATH_TIGER_E468338_TOGGLE)
            ->willReturn(true);
        $this->assertTrue($this->config->isTigerE468338ToggleEnabled());
    }

    public function testIsTigerE468338ToggleEnabledFalse()
    {
        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(Config::XML_PATH_TIGER_E468338_TOGGLE)
            ->willReturn(false);
        $this->assertFalse($this->config->isTigerE468338ToggleEnabled());
    }

    public function testGetTitleStepOneDefaultScope()
    {
        $expected = 'Title 1';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_TITLE_STEP_ONE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expected);
        $result = $this->config->getTitleStepOne();
        $this->assertSame($expected, $result);
    }

    public function testGetTitleStepOneCustomScope()
    {
        $expected = 'Title 1 Custom';
        $scopeType = 'website';
        $scopeCode = 'code';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_TITLE_STEP_ONE, $scopeType, $scopeCode)
            ->willReturn($expected);
        $result = $this->config->getTitleStepOne($scopeType, $scopeCode);
        $this->assertSame($expected, $result);
    }

    public function testGetDescriptionStepOneDefaultScope()
    {
        $expected = 'Desc 1';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DESCRIPTION_STEP_ONE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expected);
        $result = $this->config->getDescriptionStepOne();
        $this->assertSame($expected, $result);
    }

    public function testGetDescriptionStepOneCustomScope()
    {
        $expected = 'Desc 1 Custom';
        $scopeType = 'website';
        $scopeCode = 'code';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DESCRIPTION_STEP_ONE, $scopeType, $scopeCode)
            ->willReturn($expected);
        $result = $this->config->getDescriptionStepOne($scopeType, $scopeCode);
        $this->assertSame($expected, $result);
    }

    public function testGetTitleStepTwoDefaultScope()
    {
        $expected = 'Title 2';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_TITLE_STEP_TWO, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expected);
        $result = $this->config->getTitleStepTwo();
        $this->assertSame($expected, $result);
    }

    public function testGetTitleStepTwoCustomScope()
    {
        $expected = 'Title 2 Custom';
        $scopeType = 'website';
        $scopeCode = 'code';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_TITLE_STEP_TWO, $scopeType, $scopeCode)
            ->willReturn($expected);
        $result = $this->config->getTitleStepTwo($scopeType, $scopeCode);
        $this->assertSame($expected, $result);
    }

    public function testGetDescriptionStepTwoDefaultScope()
    {
        $expected = 'Desc 2';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DESCRIPTION_STEP_TWO, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expected);
        $result = $this->config->getDescriptionStepTwo();
        $this->assertSame($expected, $result);
    }

    public function testGetDescriptionStepTwoCustomScope()
    {
        $expected = 'Desc 2 Custom';
        $scopeType = 'website';
        $scopeCode = 'code';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DESCRIPTION_STEP_TWO, $scopeType, $scopeCode)
            ->willReturn($expected);
        $result = $this->config->getDescriptionStepTwo($scopeType, $scopeCode);
        $this->assertSame($expected, $result);
    }

    public function testGetTitleStepThreeDefaultScope()
    {
        $expected = 'Title 3';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_TITLE_STEP_THREE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expected);
        $result = $this->config->getTitleStepThree();
        $this->assertSame($expected, $result);
    }

    public function testGetTitleStepThreeCustomScope()
    {
        $expected = 'Title 3 Custom';
        $scopeType = 'website';
        $scopeCode = 'code';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_TITLE_STEP_THREE, $scopeType, $scopeCode)
            ->willReturn($expected);
        $result = $this->config->getTitleStepThree($scopeType, $scopeCode);
        $this->assertSame($expected, $result);
    }

    public function testGetDescriptionStepThreeDefaultScope()
    {
        $expected = 'Desc 3';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DESCRIPTION_STEP_THREE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn($expected);
        $result = $this->config->getDescriptionStepThree();
        $this->assertSame($expected, $result);
    }

    public function testGetDescriptionStepThreeCustomScope()
    {
        $expected = 'Desc 3 Custom';
        $scopeType = 'website';
        $scopeCode = 'code';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_DESCRIPTION_STEP_THREE, $scopeType, $scopeCode)
            ->willReturn($expected);
        $result = $this->config->getDescriptionStepThree($scopeType, $scopeCode);
        $this->assertSame($expected, $result);
    }
}

