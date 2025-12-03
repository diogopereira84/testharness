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
use Fedex\HttpRequestTimeout\Plugin\LaminasClientPlugin;
use Laminas\Http\Client;
use Laminas\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LaminasClientPluginTest extends TestCase
{
    /** @var MockObject|ConfigManagementInterface */
    private MockObject $configManagementMock;

    /** @var MockObject|LoggerInterface */
    private MockObject $loggerMock;

    /** @var LaminasClientPlugin */
    private LaminasClientPlugin $plugin;

    /** @var TimeoutValidator|MockObject */
    private TimeoutValidator $timeoutValidator;

    /** @var Client|MockObject */
    private Client $subjectMock;

    /** @var Request|MockObject */
    private Request $requestMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(Client::class);
        $this->configManagementMock = $this->createMock(ConfigManagementInterface::class);
        $this->timeoutValidator = $this->createMock(TimeoutValidator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->requestMock = $this->createMock(Request::class);

        $this->plugin = new LaminasClientPlugin(
            $this->configManagementMock,
            $this->timeoutValidator,
            $this->loggerMock
        );
    }

    /**
     * @return void
     */
    public function testBeforeSendWithFeatureDisabled()
    {
        // Mock isFeatureEnabled to return false
        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willReturn(false);


        // Call the method
        $result = $this->plugin->beforeSend($this->subjectMock, $this->requestMock);

        // Assert the returned values are unchanged
        $this->assertSame([$this->requestMock], $result);
    }

    /**
     * @return void
     */
    public function testBeforeSendWithSpecificTimeout()
    {
        $uri = 'https://example.com/api';
        $timeout = 10;

        // Mock ConfigManagement behavior
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
        $this->subjectMock
            ->method('getUri')
            ->willReturn($this->createConfiguredMock(\Laminas\Uri\Http::class, ['toString' => $uri]));

        // Expect logger to log the timeout setting
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('HTTP Request Timeout for ' . $uri . ' set to ' . $timeout));

        // Call the method
        $result = $this->plugin->beforeSend($this->subjectMock, $this->requestMock);

        // Assert the returned values are unchanged
        $this->assertSame([$this->requestMock], $result);
    }

    /**
     * @return void
     */
    public function testBeforeSendWithDefaultTimeout()
    {
        $uri = 'https://example.com/api';
        $defaultTimeout = 15;

        // Mock ConfigManagement behavior
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
        $this->subjectMock
            ->method('getUri')
            ->willReturn($this->createConfiguredMock(\Laminas\Uri\Http::class, ['toString' => $uri]));

        // Expect logger to log the default timeout setting
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('HTTP Request Timeout for ' . $uri . ' set to default value ' . $defaultTimeout));

        // Call the method
        $result = $this->plugin->beforeSend($this->subjectMock, $this->requestMock);

        // Assert the returned values are unchanged
        $this->assertSame([$this->requestMock], $result);
    }

    /**
     * @return void
     */
    public function testBeforeSendHandlesException()
    {
        $uri = 'https://example.com/api';

        // Mock ConfigManagement to throw an exception
        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willThrowException(new Exception('Test exception'));
        $this->subjectMock
            ->method('getUri')
            ->willReturn($this->createConfiguredMock(\Laminas\Uri\Http::class, ['toString' => $uri]));

        // Expect logger to log the error
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to handle HTTP Timeout Request feature: Test exception'));

        // Call the method
        $result = $this->plugin->beforeSend($this->subjectMock, $this->requestMock);

        // Assert the returned values are unchanged
        $this->assertSame([$this->requestMock], $result);
    }
}
