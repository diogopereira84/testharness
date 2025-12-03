<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Test\Unit\Model;

use Fedex\Mars\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var Config
     */
    protected $configMock;
    public const MARS_TOKEN_CLIENT_SECRET = 'mars/token/client_secret';

    protected function setUp(): void
    {

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->onlyMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->configMock = $this->objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    public function testGetConfigValue()
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::MARS_TOKEN_CLIENT_SECRET, ScopeInterface::SCOPE_STORE)
            ->willReturn('secret');

        $this->assertIsString($this->configMock->getConfigValue(self::MARS_TOKEN_CLIENT_SECRET));
    }

    public function testIsMazeGeeksB2743693Enabled()
    {
        $configPath = 'environment_toggle_configuration/environment_toggle/mazegeeks_mars_b2743693';

        $this->scopeConfigMock->expects($this->atLeastOnce())
            ->method('getValue')
            ->with($configPath, ScopeInterface::SCOPE_STORE)
            ->willReturn('1');

        $this->assertTrue($this->configMock->isMazeGeeksB2743693Enabled());
    }
}
