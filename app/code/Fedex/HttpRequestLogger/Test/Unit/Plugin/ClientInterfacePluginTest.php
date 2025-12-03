<?php

namespace Fedex\HttpRequestLogger\Test\Unit\Plugin;

use Fedex\HttpRequestLogger\Api\ConfigInterface;
use Fedex\HttpRequestLogger\Model\Logger;
use Fedex\HttpRequestLogger\Plugin\ClientInterfacePlugin;
use Magento\Framework\HTTP\ClientInterface;
use PHPUnit\Framework\TestCase;

class ClientInterfacePluginTest extends TestCase
{
    private $logger;
    private $config;
    private $clientInterface;
    private $plugin;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->clientInterface = $this->createMock(ClientInterface::class);
        $this->plugin = new ClientInterfacePlugin($this->logger, $this->config);
    }

    public function testAroundGet()
    {
        $uri = 'http://example.com';
        $result = 'response';
        $this->config->method('isLoggerEnabled')->willReturn(true);
        $this->clientInterface->method('get')->with($uri)->willReturn($result);

        $proceed = function ($uri) use ($result) {
            return $result;
        };

        $this->logger->expects($this->once())->method('log');

        $this->assertEquals($result, $this->plugin->aroundGet($this->clientInterface, $proceed, $uri));
    }

    public function testAroundPost()
    {
        $uri = 'http://example.com';
        $params = ['param1' => 'value1'];
        $result = 'response';
        $this->config->method('isLoggerEnabled')->willReturn(true);
        $this->clientInterface->method('post')->with($uri, $params)->willReturn($result);

        $proceed = function ($uri, $params) use ($result) {
            return $result;
        };

        $this->logger->expects($this->once())->method('log');

        $this->assertEquals($result, $this->plugin->aroundPost($this->clientInterface, $proceed, $uri, $params));
    }

    public function testAroundGetLoggerDisabled()
    {
        $uri = 'http://example.com';
        $result = 'response';
        $this->config->method('isLoggerEnabled')->willReturn(false);
        $this->clientInterface->method('get')->with($uri)->willReturn($result);

        $proceed = function ($uri) use ($result) {
            return $result;
        };

        $this->logger->expects($this->never())->method('log');

        $this->assertEquals($result, $this->plugin->aroundGet($this->clientInterface, $proceed, $uri));
    }

    public function testAroundPostLoggerDisabled()
    {
        $uri = 'http://example.com';
        $params = ['param1' => 'value1'];
        $result = 'response';
        $this->config->method('isLoggerEnabled')->willReturn(false);
        $this->clientInterface->method('post')->with($uri, $params)->willReturn($result);

        $proceed = function ($uri, $params) use ($result) {
            return $result;
        };

        $this->logger->expects($this->never())->method('log');

        $this->assertEquals($result, $this->plugin->aroundPost($this->clientInterface, $proceed, $uri, $params));
    }
}
