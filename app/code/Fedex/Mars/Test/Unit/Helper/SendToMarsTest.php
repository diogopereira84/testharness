<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 FedEx
 * @author      ManoKarthick <mano.karthick.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Test\Unit\Helper;

use Fedex\Mars\Model\JSONBuilder;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Mars\Helper\SendToMars;
use Fedex\Mars\Model\Client;
use Fedex\Mars\Model\ClientFactory;
use Fedex\Mars\Model\Config;
use Fedex\Mars\Model\OrderProcess;
use Fedex\Mars\Model\OrderProcessFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class SendToMarsTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $loggerMock;
    protected $JSONBuilderMock;
    protected $clientMock;
    protected $clientFactoryMock;
    /**
     * @var (\Fedex\Mars\Model\OrderProcess & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderProcessMock;
    /**
     * @var (\Fedex\Mars\Model\OrderProcessFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderProcessFactoryMock;
    protected $jsonMock;
    /**
     * @var (\Magento\Framework\MessageQueue\EnvelopeInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageMock;
    protected $configMock;
    protected $sendToMarsMock;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->JSONBuilderMock = $this->getMockBuilder(JSONBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderProcessMock = $this->getMockBuilder(OrderProcess::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderProcessFactoryMock = $this->getMockBuilder(OrderProcessFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageMock = $this->getMockBuilder(EnvelopeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->setMethods(['isEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $className = SendToMars::class;
        $this->sendToMarsMock = $objectManagerHelper->getObject(
            $className,
            [
                'context' => $this->contextMock,
                'clientFactory' => $this->clientFactoryMock,
                'JSONBuilder' => $this->JSONBuilderMock,
                'json' => $this->jsonMock,
                'logger' => $this->loggerMock,
                'moduleConfig' => $this->configMock
            ]
        );
    }

    public function testNoId(): void
    {
        $this->assertNull($this->sendToMarsMock->send('message'));;
    }

    public function testWithId(): void
    {
        $this->setupData();
        $this->sendToMarsMock->send('message');
    }

    public function testSendError(): void
    {
        $this->setupData();
        $this->clientMock
            ->expects($this->once())
            ->method('sendJson')->willThrowException(new \Exception());
        $this->loggerMock->expects($this->once())
            ->method('critical');
        $this->sendToMarsMock->send('message');
    }

    private function setupData(): void
    {
        $orderData = ['id' => 1, 'type' => 'order'];
        $this->jsonMock->expects($this->any())->method('unserialize')->willReturn($orderData);
        $this->JSONBuilderMock->expects($this->any())->method('prepareJson')->willReturn([123]);
        $this->clientFactoryMock->expects($this->any())->method('create')->willReturn($this->clientMock);
        $this->configMock->expects($this->any())->method('isEnabled')->willReturn(true);
    }
}
