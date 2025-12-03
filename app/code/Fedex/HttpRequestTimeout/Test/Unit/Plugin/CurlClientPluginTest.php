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
use Fedex\HttpRequestTimeout\Plugin\CurlClientPlugin;
use Magento\Framework\HTTP\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CurlClientPluginTest extends TestCase
{
    /** @var MockObject|ConfigManagementInterface|(ConfigManagementInterface&object&MockObject)|(ConfigManagementInterface&MockObject)|(object&MockObject)  */
    private MockObject $configManagementMock;

    /** @var MockObject|(object&MockObject)|LoggerInterface|(LoggerInterface&object&MockObject)|(LoggerInterface&MockObject)  */
    private MockObject $loggerMock;

    /** @var CurlClientPlugin  */
    private CurlClientPlugin $plugin;

    /** @var TimeoutValidator|(TimeoutValidator&object&MockObject)|(TimeoutValidator&MockObject)|(object&MockObject)|MockObject  */
    private TimeoutValidator $timeoutValidator;

    /** @var ClientInterface|(ClientInterface&object&MockObject)|(ClientInterface&MockObject)|(object&MockObject)|MockObject  */
    private ClientInterface $subjectMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->subjectMock = $this->createMock(ClientInterface::class);
        $this->configManagementMock = $this->createMock(ConfigManagementInterface::class);
        $this->timeoutValidator = $this->createMock(TimeoutValidator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->plugin = new CurlClientPlugin(
            $this->configManagementMock,
            $this->timeoutValidator,
            $this->loggerMock
        );
    }

    /**
     * @return void
     */
    public function testBeforePostWithFeatureDisabled()
    {
        $uri = 'https://example.com/api';
        $params = ['param1' => 'value1'];

        // Mock isFeatureEnabled to return false
        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willReturn(false);


        // Call the method
        $result = $this->plugin->beforePost($this->subjectMock, $uri, $params);

        // Assert the returned values are unchanged
        $this->assertSame([$uri, $params], $result);
    }

    /**
     * @return void
     */
    public function testBeforePostWithSpecificTimeout()
    {
        $uri = 'https://example.com/api';
        $params = ['param1' => 'value1'];
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

        $this->subjectMock->expects($this->once())
            ->method('setOption')
            ->with(CURLOPT_TIMEOUT, $timeout);

        // Expect logger to log the timeout setting
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('HTTP Request Timeout for ' . $uri . ' set to ' . $timeout));

        // Call the method
        $result = $this->plugin->beforePost($this->subjectMock, $uri, $params);

        // Assert the returned values are unchanged
        $this->assertSame([$uri, $params], $result);
    }

    /**
     * @return void
     */
    public function testBeforeGetWithDefaultTimeout()
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

        $this->subjectMock->expects($this->once())
            ->method('setOption')
            ->with(CURLOPT_TIMEOUT, $defaultTimeout);

        // Expect logger to log the default timeout setting
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('HTTP Request Timeout for ' . $uri . ' set to default value ' . $defaultTimeout));

        // Call the method
        $result = $this->plugin->beforeGet($this->subjectMock, $uri);

        // Assert the returned value is unchanged
        $this->assertSame([$uri], $result);
    }

    /**
     * @return void
     */
    public function testBeforeGetHandlesException()
    {
        $uri = 'https://example.com/api';

        // Mock ConfigManagement to throw an exception
        $this->configManagementMock
            ->method('isFeatureEnabled')
            ->willThrowException(new Exception('Test exception'));

        // Expect logger to log the error
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to handle HTTP Timeout request Feature: Test exception'));

        // Call the method
        $result = $this->plugin->beforeGet($this->subjectMock, $uri);

        // Assert the returned value is unchanged
        $this->assertSame([$uri], $result);
    }
}
