<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Test\Unit\Model;

use Fedex\LiveSearch\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected $config;
    private const XML_PATH_SERVICE_URL = 'storefront_features/website_configuration/service_url';
    private const SERVICE_URL = 'https://catalog-service-sandbox.adobe.io/graphql';
    private const ENABLE_ELLIPSIS_CONTROL = 'storefront_features/product_name_ellipsis_control/enabled';
    private const ENABLE_ELLIPSIS_CONTROL_VALUE = false;
    private const ELLIPSIS_CONTROL_TOTAL_CHARACTERS = 'storefront_features/product_name_ellipsis_control/total_characters';
    private const ELLIPSIS_CONTROL_TOTAL_CHARACTERS_VALUE = 10;
    private const ELLIPSIS_CONTROL_START_CHARACTERS = 'storefront_features/product_name_ellipsis_control/start_characters';
    private const ELLIPSIS_CONTROL_START_CHARACTERS_VALUE = 10;
    private const ELLIPSIS_CONTROL_END_CHARACTERS = 'storefront_features/product_name_ellipsis_control/end_characters';
    private const ELLIPSIS_CONTROL_END_CHARACTERS_VALUE = 10;



    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfigMock);
    }

    /**
     * @test getServiceUrl
     */
    public function testGetServiceUrl(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::XML_PATH_SERVICE_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn(self::SERVICE_URL);
        $this->assertEquals(self::SERVICE_URL, $this->config->getServiceUrl());
        $this->scopeConfigMock->method('getValue')
            ->with(self::XML_PATH_SERVICE_URL, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->assertIsString($this->config->getServiceUrl());
    }

    /**
     * @test isEllipsisControlEnabled
     */
    public function testIsEllipsisControlEnabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')
            ->with(self::ENABLE_ELLIPSIS_CONTROL, ScopeInterface::SCOPE_STORE)
            ->willReturn(self::ENABLE_ELLIPSIS_CONTROL_VALUE);
        $this->assertEquals(self::ENABLE_ELLIPSIS_CONTROL_VALUE, $this->config->isEllipsisControlEnabled());
        $this->scopeConfigMock->method('isSetFlag')
            ->with(self::ENABLE_ELLIPSIS_CONTROL, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->assertIsBool($this->config->isEllipsisControlEnabled());
    }

    /**
     * @test getEllipsisControlTotalCharacters
     */
    public function testGetEllipsisControlTotalCharacters(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::ELLIPSIS_CONTROL_TOTAL_CHARACTERS, ScopeInterface::SCOPE_STORE)
            ->willReturn(self::ELLIPSIS_CONTROL_TOTAL_CHARACTERS_VALUE);
        $this->assertEquals(self::ELLIPSIS_CONTROL_TOTAL_CHARACTERS_VALUE, $this->config->getEllipsisControlTotalCharacters());
        $this->scopeConfigMock->method('getValue')
            ->with(self::ELLIPSIS_CONTROL_TOTAL_CHARACTERS, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->assertIsInt($this->config->getEllipsisControlTotalCharacters());
    }

    /**
     * @test getEllipsisControlTotalCharacters
     */
    public function testGetEllipsisControlStartCharacters(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::ELLIPSIS_CONTROL_START_CHARACTERS, ScopeInterface::SCOPE_STORE)
            ->willReturn(self::ELLIPSIS_CONTROL_START_CHARACTERS_VALUE);
        $this->assertEquals(self::ELLIPSIS_CONTROL_START_CHARACTERS_VALUE, $this->config->getEllipsisControlStartCharacters());
        $this->scopeConfigMock->method('getValue')
            ->with(self::ELLIPSIS_CONTROL_START_CHARACTERS, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->assertIsInt($this->config->getEllipsisControlStartCharacters());
    }

    /**
     * @test getEllipsisControlTotalCharacters
     */
    public function testGetEllipsisControlEndCharacters(): void
    {
        $this->scopeConfigMock->method('getValue')
            ->with(self::ELLIPSIS_CONTROL_END_CHARACTERS, ScopeInterface::SCOPE_STORE)
            ->willReturn(self::ELLIPSIS_CONTROL_END_CHARACTERS_VALUE);
        $this->assertEquals(self::ELLIPSIS_CONTROL_END_CHARACTERS_VALUE, $this->config->getEllipsisControlEndCharacters());
        $this->scopeConfigMock->method('getValue')
            ->with(self::ELLIPSIS_CONTROL_END_CHARACTERS, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->assertIsInt($this->config->getEllipsisControlEndCharacters());
    }

}
