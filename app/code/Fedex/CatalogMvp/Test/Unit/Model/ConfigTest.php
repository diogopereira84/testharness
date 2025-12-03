<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Test\Unit\Model;

use Fedex\CatalogMvp\Model\Config;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CatalogMvp\Api\ConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ConfigTest extends TestCase
{

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfig;

    /**
     * @var Config
     */
    private $config;

     /**
      * Setup method
      *
      * @return void
      */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->config = $objectManagerHelper->getObject(
            Config::class,
            [
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    public function testIsD206810ToggleEnabledTrue()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(Config::XML_PATH_D206810_TOGGLE)
            ->willReturn(true);
        $this->assertTrue($this->config->isD206810ToggleEnabled());
    }

    public function testIsD206810ToggleEnabledFalse()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(Config::XML_PATH_D206810_TOGGLE)
            ->willReturn(false);
        $this->assertFalse($this->config->isD206810ToggleEnabled());
    }

    public function testIsB2371268ToggleEnabledTrue()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(Config::XML_PATH_B2371268_TOGGLE)
            ->willReturn(true);
        $this->assertTrue($this->config->isB2371268ToggleEnabled());
    }

    public function testIsB2371268ToggleEnabledFalse()
    {
        $this->toggleConfig->method('getToggleConfigValue')
            ->with(Config::XML_PATH_B2371268_TOGGLE)
            ->willReturn(false);
        $this->assertFalse($this->config->isB2371268ToggleEnabled());
    }
}
