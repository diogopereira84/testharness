<?php
/**
 * @category    Fedex
 * @package     Fedex_CoreApi
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Test\Gateway\Http;

use Fedex\CoreApi\Gateway\Http\Client;
use Fedex\CoreApi\Gateway\Http\TransferInterface;
use Fedex\CoreApi\Gateway\Http\Transfer;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    private const HTTPS_STATUS_CREATED = 201;
    private const HTTPS_STATUS_SERVER_ERROR = 500;
    private const HTTPS_STATUS_UNAUTHORIZED_ERROR = 401;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var ResponseFactory|MockObject
     */
    private $responseFactoryMock;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var Transfer
     */
    private Transfer $transfer;

    protected function setUp(): void
    {
        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseFactoryMock = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->client = new Client($this->clientFactoryMock, $this->responseFactoryMock, $this->loggerMock);
        $this->transfer = new Transfer([
            TransferInterface::METHOD => 'POST',
            TransferInterface::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            TransferInterface::BODY => [
                'userTokensRequest' => [
                    'CoreApiUserId' => 'CoreApi-id'
                ]
            ],
            TransferInterface::URI => '/',
            TransferInterface::PARAMS => [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => '{"userTokensRequest":{"CoreApiUserId":"CoreApi-id"}}'
            ]
        ]);
    }

    public function testRequestSuccess()
    {
        $responseMock = new Response(self::HTTPS_STATUS_CREATED);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn(new GuzzleHttpClient(['handler' => HandlerStack::create(new MockHandler([$responseMock]))]));

        $response = $this->client->request($this->transfer);

        $this->assertEquals($responseMock->getStatusCode(), $response->getStatusCode());
    }

    public function testRequestUnAuthorized()
    {
        $responseMock = new Response(self::HTTPS_STATUS_UNAUTHORIZED_ERROR);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn(new GuzzleHttpClient(['handler' => HandlerStack::create(new MockHandler([$responseMock]))]));
        $this->responseFactoryMock->expects($this->once())->method('create')
            ->willReturn(new Response($responseMock->getStatusCode()));

        $response = $this->client->request($this->transfer);

        $this->assertEquals($responseMock->getStatusCode(), $response->getStatusCode());
    }

    public function testRequestError()
    {
        $responseMock = new Response(self::HTTPS_STATUS_SERVER_ERROR);
        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn(new GuzzleHttpClient(['handler' => HandlerStack::create(new MockHandler([$responseMock]))]));
        $this->responseFactoryMock->expects($this->once())->method('create')
            ->willReturn(new Response($responseMock->getStatusCode()));

        $response = $this->client->request($this->transfer);

        $this->assertEquals($responseMock->getStatusCode(), $response->getStatusCode());
    }
}
