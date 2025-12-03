<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
declare(strict_types=1);

namespace Fedex\HttpRequestTimeout\Test\Unit\Plugin;

use Exception;
use Fedex\HttpRequestTimeout\Api\ConfigManagementInterface;
use Fedex\HttpRequestTimeout\Model\TimeoutValidator;
use Fedex\HttpRequestTimeout\Plugin\GuzzleClientPlugin;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GuzzleClientPluginTest extends TestCase
{
    /** @var MockObject|ConfigManagementInterface */
    private MockObject $configManagementMock;

    /** @var MockObject|LoggerInterface */
    private MockObject $loggerMock;

    /** @var GuzzleClientPlugin */
    private GuzzleClientPlugin $plugin;

    /** @var MockObject|TimeoutValidator */
    private MockObject $timeoutValidator;

    /** @var MockObject|Client */
    private MockObject $subjectMock;

    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Client::class);
        $this->configManagementMock = $this->createMock(ConfigManagementInterface::class);
        $this->timeoutValidator = $this->createMock(TimeoutValidator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->plugin = new GuzzleClientPlugin(
            $this->configManagementMock,
            $this->timeoutValidator,
            $this->loggerMock
        );
    }

    public function testBeforeRequestWithFeatureDisabled()
    {
        $method = 'GET';
        $uri = 'https://example.com/api';
        $options = [];

        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willReturn(false);


        $result = $this->plugin->beforeRequest($this->subjectMock, $method, $uri, $options);

        $this->assertSame([$method, $uri, $options], $result);
    }

    public function testBeforeRequestWithSpecificTimeout()
    {
        $method = 'GET';
        $uri = 'https://example.com/api';
        $options = [];
        $timeout = 10;

        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->configManagementMock
            ->method('getCurrentEntriesValueUnserialized')
            ->willReturn([
                $uri => ['timeout' => $timeout]
            ]);
        $this->timeoutValidator
            ->method('isSuitableForDefinedTimeout')
            ->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('HTTP Request Timeout for ' . $uri . ' set to ' . $timeout));

        $result = $this->plugin->beforeRequest($this->subjectMock, $method, $uri, $options);

        $this->assertSame([$method, $uri, array_merge($options, ['timeout' => $timeout])], $result);
    }

    public function testBeforeRequestWithDefaultTimeout()
    {
        $method = 'GET';
        $uri = 'https://example.com/api';
        $options = [];
        $defaultTimeout = 15;

        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willReturn(true);
        $this->configManagementMock
            ->method('isDefaultTimeoutEnabled')
            ->willReturn(true);
        $this->configManagementMock
            ->method('getDefaultTimeout')
            ->willReturn($defaultTimeout);
        $this->configManagementMock
            ->method('getCurrentEntriesValueUnserialized')
            ->willReturn([]);
        $this->timeoutValidator
            ->method('isSuitableForDefinedTimeout')
            ->willReturn(false);
        $this->timeoutValidator
            ->method('isSuitableForDefaultTimeout')
            ->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('HTTP Request Timeout for ' . $uri . ' set to default value ' . $defaultTimeout));

        $result = $this->plugin->beforeRequest($this->subjectMock, $method, $uri, $options);

        $this->assertSame([$method, $uri, array_merge($options, ['timeout' => $defaultTimeout])], $result);
    }

    public function testBeforeRequestHandlesException()
    {
        $method = 'GET';
        $uri = 'https://example.com/api';
        $options = [];

        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willThrowException(new Exception('Test exception'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to handle HTTP Timeout Request feature: Test exception'));

        $result = $this->plugin->beforeRequest($this->subjectMock, $method, $uri, $options);

        $this->assertSame([$method, $uri, $options], $result);
    }
}
