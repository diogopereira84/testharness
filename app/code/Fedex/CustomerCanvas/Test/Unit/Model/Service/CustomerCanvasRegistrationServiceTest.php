<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\Model\Service;

use Fedex\CustomerCanvas\Model\Config\CanvasConfig;
use Fedex\CustomerCanvas\Model\Service\CustomerCanvasRegistrationService;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;

class CustomerCanvasRegistrationServiceTest extends TestCase
{
    private Curl $curlMock;
    private LoggerInterface $loggerMock;
    private CanvasConfig $canvasConfigMock;
    private CustomerCanvasRegistrationService $service;
    private $sessionMock;

    protected function setUp(): void
    {
        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->canvasConfigMock = $this->createMock(CanvasConfig::class);
        $this->sessionMock = $this->createMock(Session::class);

        $this->canvasConfigMock->method('getApiUrl')
            ->willReturn('https://canvas.example.com/api');
        $this->canvasConfigMock->method('getCanvasStoreId')
            ->willReturn('store-123');
        $this->canvasConfigMock->method('getAccessToken')
            ->willReturn('access-token');

        $this->service = new CustomerCanvasRegistrationService(
            $this->curlMock,
            $this->loggerMock,
            $this->canvasConfigMock,
            $this->sessionMock
        );
    }

    public function testRegisterUserReturnsUserIdOnSuccess(): void
    {
        $storefrontUserId = 'user-456';
        $expectedUserId = 'canvas-user-789';

        $this->curlMock->expects($this->any())
            ->method('setHeaders')
            ->with([
                'Authorization' => 'Bearer access-token',
                'Content-Type' => 'application/json'
            ]);

        $this->curlMock->expects($this->once())
            ->method('post')
            ->with(
                'https://canvas.example.com/api/storefront-users?storefrontId=store-123',
                json_encode([
                    'storefrontUserId' => $storefrontUserId,
                    'isAnonymous' => true
                ])
            );

        $this->curlMock->method('getStatus')->willReturn(201);
        $this->curlMock->method('getBody')->willReturn(json_encode(['userId' => $expectedUserId]));

        $result = $this->service->registerUser($storefrontUserId);
        $this->assertSame($expectedUserId, $result);
    }

    public function testRegisterUserReturnsNullOnNon201Status(): void
    {
        $this->curlMock->method('getStatus')->willReturn(400);
        $this->curlMock->method('getBody')->willReturn(json_encode([]));

        $this->curlMock->expects($this->once())->method('post');
        $result = $this->service->registerUser('user-xyz');

        $this->assertNull($result);
    }

    public function testRegisterUserReturnsNullAndLogsErrorOnException(): void
    {
        $this->curlMock->method('post')->willThrowException(new \Exception('Curl error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Curl error'));

        $result = $this->service->registerUser('user-err');
        $this->assertNull($result);
    }
}
