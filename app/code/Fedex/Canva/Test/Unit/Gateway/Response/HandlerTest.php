<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Gateway\Response;

use Fedex\Canva\Gateway\Response\Handler;
use Fedex\Canva\Gateway\Response\UserTokenFactory;
use Fedex\Canva\Gateway\Response\UserToken;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;

class HandlerTest extends TestCase
{
    /**
     * @var Handler
     */
    private Handler $handler;

    /**
     * @var UserTokenFactory|MockObject
     */
    private $userTokenFactoryMock;

    protected function setUp():void
    {
        $this->userTokenFactoryMock = $this->getMockBuilder(UserTokenFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new Handler(new Json(), $this->userTokenFactoryMock, new JsonValidator());
    }

    /**
     * @param Response $handlingSubject
     * @param bool $status
     * @param string $accessToken
     * @param string $clientId
     * @param string $expirationDateTime
     * @dataProvider handleDataProvider
     */
    public function testHandle(Response $handlingSubject, bool $status, string $accessToken, string $clientId, string $expirationDateTime)
    {
        $this->userTokenFactoryMock->expects($this->any())->method('create')->willReturn(new UserToken());
        $userToken = $this->handler->handle($handlingSubject);
        $this->assertEquals($status, $userToken->getStatus());
        $this->assertEquals($accessToken, $userToken->getAccessToken());
        $this->assertEquals($clientId, $userToken->getClientId());
        $this->assertEquals($expirationDateTime, $userToken->getExpirationDateTime());
    }

    /**
     * @codeCoverageIgnore
     * @return array[]
     */
    public function handleDataProvider(): array
    {
        return [
            [
                new Response(201, [], json_encode([
                    'transactionId' => 'SOME_TRANSACTION_ID',
                    'output' => [
                        'userTokenDetail' => [
                            'accessToken' => 'VALID_ACCESS_TOKEN',
                            'clientId' => 'VALID_CLIENT_ID',
                            'expirationDateTime' => '2022-03-04T12:41:04.885+0000',
                        ]
                    ]
                ])),
                true,
                'VALID_ACCESS_TOKEN',
                'VALID_CLIENT_ID',
                '2022-03-04T12:41:04.885+0000',
            ],
            [
                new Response(500),
                false,
                '',
                '',
                '',
            ],
            [
                new Response(401),
                false,
                '',
                '',
                '',
            ],
        ];
    }
}
