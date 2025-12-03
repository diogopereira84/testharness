<?php

namespace Fedex\HttpRequestLogger\Test\Unit\Plugin;

use Fedex\HttpRequestLogger\Api\ConfigInterface;
use Fedex\HttpRequestLogger\Model\Logger;
use Fedex\HttpRequestLogger\Plugin\GuzzleHttpClientPlugin;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GuzzleHttpClientPluginTest extends TestCase
{
    private $logger;
    private $config;
    private $client;
    private $plugin;

    public $response;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->plugin = new GuzzleHttpClientPlugin($this->logger, $this->config);
    }

    public function testAroundRequest()
    {
        $method = 'GET';
        $uri = 'http://example.com';
        $options = [];
        $result = $this->response;
        $this->config->method('isLoggerEnabled')->willReturn(true);
        $this->client->method('request')->with($method, $uri, $options)->willReturn($result);

        $proceed = function ($method, $uri, $options) use ($result) {
            return $result;
        };

        $this->logger->expects($this->once())->method('log');

        $this->assertEquals($result, $this->plugin->aroundRequest($this->client, $proceed, $method, $uri, $options));
    }

    public function testAroundRequestLoggerDisabled()
    {
        $method = 'GET';
        $uri = 'http://example.com';
        $options = [];
        $result = $this->response;
        $this->config->method('isLoggerEnabled')->willReturn(false);
        $this->client->method('request')->with($method, $uri, $options)->willReturn($result);

        $proceed = function ($method, $uri, $options) use ($result) {
            return $result;
        };

        $this->assertEquals($result, $this->plugin->aroundRequest($this->client, $proceed, $method, $uri, $options));
    }
}
