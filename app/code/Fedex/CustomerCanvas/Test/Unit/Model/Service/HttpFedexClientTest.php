<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Service\HttpFedexClient;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;

class HttpFedexClientTest extends TestCase
{
    /** @var Curl|MockObject */
    private $curlMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var HttpFedexClient */
    private $client;
    private $configProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->client = new HttpFedexClient(
            $this->curlMock,
            $this->loggerMock,
            $this->configProvider
        );
    }

    public function testPutWithRetryReturnsTrueOnSuccess(): void
    {
        $endpoint = 'https://api.example.com/document/123/vendorownerid';
        $payload = '{"id":"123"}';
        $clientId = 'testClientId';

        // Arrange Curl mock
        $this->curlMock->expects($this->once())
            ->method('setOptions')
            ->with($this->arrayHasKey(CURLOPT_CUSTOMREQUEST));

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with($endpoint, $payload);

        $this->curlMock->method('getStatus')
            ->willReturn(200);

        $this->curlMock->method('getBody')
            ->willReturn('{"success":true}');

        // Logger should log an info message
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info');

        $result = $this->client->putWithRetry($endpoint, $payload, $clientId);

        $this->assertTrue($result, 'Expected true when status is 200');
    }

    public function testPutWithRetryRetriesAndFailsAfterMaxAttempts(): void
    {
        $endpoint = 'https://api.example.com/document/123/vendorownerid';
        $payload = '{"id":"123"}';
        $clientId = 'testClientId';

        // Expect the Curl client to fail all attempts (500 status)
        $this->curlMock->expects($this->exactly(3))
            ->method('post')
            ->with($endpoint, $payload);

        $this->curlMock->method('getStatus')
            ->willReturn(500);

        $this->curlMock->method('getBody')
            ->willReturn('{"error":"Server error"}');

        // Logger should record error or critical logs
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error');

        $result = $this->client->putWithRetry($endpoint, $payload, $clientId);

        $this->assertFalse($result, 'Expected false after all retry attempts fail');
    }

    public function testPutWithRetryHandlesExceptionGracefully(): void
    {
        $endpoint = 'https://api.example.com/document/123/vendorownerid';
        $payload = '{"id":"123"}';
        $clientId = 'testClientId';

        // Simulate exception on first call
        $this->curlMock->expects($this->exactly(3))
            ->method('post')
            ->willThrowException(new \RuntimeException('Network error'));

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('critical');

        $result = $this->client->putWithRetry($endpoint, $payload, $clientId);

        $this->assertFalse($result, 'Expected false when exceptions occur during all retries');
    }

    public function testBuildHeadersPrivateMethod(): void
    {
        $reflection = new \ReflectionClass(HttpFedexClient::class);
        $method = $reflection->getMethod('buildHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->client, 'myClientId');

        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains('Accept: application/json', $headers);
        $this->assertContains('client_id: myClientId', $headers);
    }

    public function testExtractTransactionIdPrivateMethod(): void
    {
        $reflection = new \ReflectionClass(HttpFedexClient::class);
        $method = $reflection->getMethod('extractTransactionId');
        $method->setAccessible(true);

        $response = '{"transactionId":"abc123"}';
        $transactionId = $method->invoke($this->client, $response);

        $this->assertEquals('abc123', $transactionId);
    }
}
