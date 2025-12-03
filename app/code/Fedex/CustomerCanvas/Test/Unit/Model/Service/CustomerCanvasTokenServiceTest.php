<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Config\CanvasConfig;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasRegistrationService;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasTokenService;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CustomerCanvasTokenServiceTest extends TestCase
{
    /** @var Curl|MockObject */
    private $curlMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var CanvasConfig|MockObject */
    private $canvasConfigMock;

    /** @var CustomerCanvasRegistrationService|MockObject */
    private $registrationServiceMock;

    /** @var CustomerCanvasTokenService */
    private $tokenService;

    protected function setUp(): void
    {
        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->canvasConfigMock = $this->createMock(CanvasConfig::class);
        $this->registrationServiceMock = $this->createMock(CustomerCanvasRegistrationService::class);

        $this->tokenService = new CustomerCanvasTokenService(
            $this->curlMock,
            $this->loggerMock,
            $this->canvasConfigMock,
            $this->registrationServiceMock
        );
    }

    public function testFetchToken_SuccessOnFirstTry(): void
    {
        $this->canvasConfigMock->method('getCanvasSToreId')->willReturn('store123');
        $this->canvasConfigMock->method('getAccessToken')->willReturn('access-token');
        $this->canvasConfigMock->method('getApiUrl')->willReturn('https://api.example.com/');

        $this->curlMock->expects($this->any())->method('setHeaders');
        $this->curlMock->expects($this->once())->method('get');
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn('test-token');

        $token = $this->tokenService->fetchToken('user123');
        $this->assertEquals('test-token', $token);
    }

    public function testFetchToken_SuccessAfterRegistration(): void
    {
        $this->canvasConfigMock->method('getCanvasSToreId')->willReturn('store123');
        $this->canvasConfigMock->method('getAccessToken')->willReturn('access-token');
        $this->canvasConfigMock->method('getApiUrl')->willReturn('https://api.example.com/');

        // First token request returns null
        $this->curlMock->expects($this->any())->method('setHeaders');
        $this->curlMock->expects($this->exactly(2))->method('get');
        $this->curlMock->method('getStatus')->willReturnOnConsecutiveCalls(200, 200);
        $this->curlMock->method('getBody')->willReturnOnConsecutiveCalls(null, 'new-token');

        $this->registrationServiceMock->method('registerUser')->with('user123')->willReturn('newuser456');

        $token = $this->tokenService->fetchToken('user123');
        $this->assertEquals('new-token', $token);
    }

    public function testFetchToken_ReturnsNullWhenAllFails(): void
    {
        $this->canvasConfigMock->method('getCanvasSToreId')->willReturn('store123');
        $this->canvasConfigMock->method('getAccessToken')->willReturn('access-token');
        $this->canvasConfigMock->method('getApiUrl')->willReturn('https://api.example.com/');

        $this->curlMock->expects($this->any())->method('get');
        $this->curlMock->method('getStatus')->willReturn(200);
        $this->curlMock->method('getBody')->willReturn(null);

        $this->registrationServiceMock->method('registerUser')->with('user123')->willReturn(null);

        $token = $this->tokenService->fetchToken('user123');
        $this->assertNull($token);
    }

    public function testFetchToken_LogsExceptionAndReturnsNull(): void
    {
        $this->canvasConfigMock->method('getCanvasSToreId')->willReturn('store123');
        $this->canvasConfigMock->method('getAccessToken')->willReturn('access-token');
        $this->canvasConfigMock->method('getApiUrl')->willReturn('https://api.example.com/');

        $this->curlMock->expects($this->any())->method('setHeaders');
        $this->curlMock->expects($this->once())->method('get')->willThrowException(new \Exception('Curl error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Token fetch error - Curl error'));

        $token = $this->tokenService->fetchToken('user123');
        $this->assertNull($token);
    }
}
