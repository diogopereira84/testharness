<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Test\Unit\Model;

use Fedex\Mars\Model\Client;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $tokenMock;
    protected $curlMock;
    protected $moduleConfigMock;
    protected $serializerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $clientMock;
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenMock = $this->getMockBuilder(\Fedex\Mars\Model\Token::class)
            ->setMethods(['getToken'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->curlMock = $this->getMockBuilder(\Magento\Framework\HTTP\Client\Curl::class)
            ->setMethods(['getBody', 'post', 'getStatus'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleConfigMock = $this->getMockBuilder(\Fedex\Mars\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->clientMock = $this->objectManager->getObject(
            Client::class,
            [
                'logger' => $this->loggerMock,
                'curl' => $this->curlMock,
                'moduleConfig' => $this->moduleConfigMock,
                'token' => $this->tokenMock,
                'serializer' => $this->serializerMock,
            ]
        );
    }

    public function testSendJsonSuccess()
    {
        $this->getTokenCredentials();
        $this->curlMock->expects($this->any())->method('getStatus')->willReturn(100);
        $this->tokenMock
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(['access_token' => '123', 'expires_in' => 123]);
        $this->serializerMock->expects($this->any())->method('serialize')->willReturn('123');
        $this->assertEmpty($this->clientMock->sendJson(['abc'], 1));
    }

    private function getTokenCredentials(): void
    {
        $this->moduleConfigMock->expects($this->any())->method('getClientId')->willReturn('88');
        $this->moduleConfigMock->expects($this->any())->method('getSecret')->willReturn('secret');
        $this->moduleConfigMock->expects($this->any())->method('getResource')->willReturn('resource');
        $this->moduleConfigMock->expects($this->any())->method('getGrantType')->willReturn('admin');
        $this->moduleConfigMock->expects($this->any())->method('getTokenApiUrl')->willReturn('https://secure.com');
        $this->moduleConfigMock->expects($this->any())->method('getMaxRetries')->willReturn(1);
        $this->moduleConfigMock->expects($this->any())->method('isLoggingEnabled')->willReturn(true);
    }
}
