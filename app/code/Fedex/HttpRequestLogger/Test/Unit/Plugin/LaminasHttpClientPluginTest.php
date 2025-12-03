<?php

namespace Fedex\HttpRequestLogger\Test\Unit\Plugin;

use Fedex\HttpRequestLogger\Api\ConfigInterface;
use Fedex\HttpRequestLogger\Model\Logger;
use Fedex\HttpRequestLogger\Plugin\LaminasHttpClientPlugin;
use Laminas\Http\Client;
use PHPUnit\Framework\TestCase;

class LaminasHttpClientPluginTest extends TestCase
{
    private $logger;
    private $config;
    private $client;
    private $plugin;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->plugin = new LaminasHttpClientPlugin($this->logger, $this->config);
    }

    public function testAroundSend()
    {
        $args = [];
        $result = 'response';
        $this->config->method('isLoggerEnabled')->willReturn(true);
        $this->client->method('send')->with($args)->willReturn($result);

        $proceed = function ($args) use ($result) {
            return $result;
        };

        $this->logger->expects($this->once())->method('log');

        $this->assertEquals($result, $this->plugin->aroundSend($this->client, $proceed, $args));
    }

    public function testAroundSendLoggerDisabled()
    {
        $args = [];
        $result = 'response';
        $this->config->method('isLoggerEnabled')->willReturn(false);
        $this->client->method('send')->with($args)->willReturn($result);

        $proceed = function ($args) use ($result) {
            return $result;
        };

        $this->logger->expects($this->never())->method('log');

        $this->assertEquals($result, $this->plugin->aroundSend($this->client, $proceed, $args));
    }
}
