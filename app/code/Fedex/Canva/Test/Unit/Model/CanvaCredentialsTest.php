<?php

declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model;

use Fedex\CoreApi\Gateway\Http\Transfer;
use Fedex\CoreApi\Gateway\Http\TransferInterface;
use Fedex\Canva\Gateway\Response\UserToken;
use Fedex\Canva\Model\CanvaCredentials;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\CoreApi\Gateway\Http\Client;
use Fedex\Canva\Gateway\Response\HandlerInterface;
use Fedex\Canva\Gateway\Request\BuilderInterface;
use Magento\Customer\Model\Session;
use Fedex\CoreApi\Gateway\Http\TransferFactory;
use Fedex\Canva\Api\Data\ConfigInterface as ModuleConfig;

class CanvaCredentialsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected CanvaCredentials $contentReaderMock;
    protected BuilderInterface|MockObject $builderMock;
    protected TransferFactory|MockObject $transferFactoryMock;
    protected Transfer|MockObject $transferMock;
    protected ModuleConfig|MockObject $moduleConfigMock;
    protected Client|MockObject $clientMock;
    protected HandlerInterface|MockObject $handlerMock;
    protected Session|MockObject $sessionMock;
    protected UserToken|MockObject $userTokenMock;
    protected Response $responseMock;

    protected function setUp(): void
    {
        $this->builderMock = $this->getMockBuilder(BuilderInterface::class)
            ->onlyMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transferFactoryMock = $this->getMockBuilder(TransferFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->transferMock = $this->getMockBuilder(Transfer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleConfigMock = $this->getMockBuilder(ModuleConfig::class)
            ->onlyMethods(['getUserTokenApiUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->clientMock = $this->getMockBuilder(Client::class)
            ->onlyMethods(['request'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->handlerMock = $this->getMockBuilder(HandlerInterface::class)
            ->onlyMethods(['handle'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->userTokenMock = $this->getMockBuilder(UserToken::class)
            ->onlyMethods(['getStatus', 'getAccessToken', 'getClientId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['getCanvaAccessToken', 'getClientId', 'setCanvaAccessToken', 'setClientId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->contentReaderMock = $this->objectManager->getObject(
            CanvaCredentials::class,
            [
                'builder' => $this->builderMock,
                'transferFactory' => $this->transferFactoryMock,
                'moduleConfig' => $this->moduleConfigMock,
                'client' => $this->clientMock,
                'handler' => $this->handlerMock,
                'session' => $this->sessionMock
            ]
        );
    }

    public function testFetchSectionData()
    {
        $this->sessionMock->expects($this->once())->method('getCanvaAccessToken')->willReturn(false);
        $this->sessionMock->expects($this->once())->method('getClientId')->willReturn(false);
        $this->moduleConfigMock->expects($this->once())->method('getUserTokenApiUrl')->willReturn('url.fedex.com');

        $builderData = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ];
        $this->builderMock->expects($this->once())->method('build')->willReturn($builderData);

        $transferData = [
            'data' => [
                TransferInterface::METHOD => 'POST',
                TransferInterface::URI => 'url.fedex.com',
                TransferInterface::PARAMS => $builderData
            ]
        ];
        $this->transferFactoryMock->expects($this->once())->method('create')->with($transferData)->willReturn($this->transferMock);

        $this->clientMock->expects($this->once())->method('request')->with($this->transferMock)->willReturn($this->responseMock);

        $this->handlerMock->expects($this->once())->method('handle')->with($this->responseMock)->willReturn($this->userTokenMock);

        $this->userTokenMock->expects($this->once())->method('getStatus')->willReturn(true);
        $this->userTokenMock->expects($this->once())->method('getAccessToken')->willReturn('asd123');
        $this->userTokenMock->expects($this->once())->method('getClientId')->willReturn('123');

        $this->sessionMock->expects($this->once())->method('setCanvaAccessToken')->with('asd123')->willReturnSelf();
        $this->sessionMock->expects($this->once())->method('setClientId')->with(123)->willReturnSelf();

        $this->contentReaderMock->fetchSectionData();
    }

    public function testFetchSectionDataStatusFalse()
    {
        $this->sessionMock->expects($this->once())->method('getCanvaAccessToken')->willReturn(false);
        $this->sessionMock->expects($this->once())->method('getClientId')->willReturn(false);
        $this->moduleConfigMock->expects($this->once())->method('getUserTokenApiUrl')->willReturn('url.fedex.com');

        $builderData = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ];
        $this->builderMock->expects($this->once())->method('build')->willReturn($builderData);

        $transferData = [
            'data' => [
                TransferInterface::METHOD => 'POST',
                TransferInterface::URI => 'url.fedex.com',
                TransferInterface::PARAMS => $builderData
            ]
        ];
        $this->transferFactoryMock->expects($this->once())->method('create')->with($transferData)->willReturn($this->transferMock);

        $this->clientMock->expects($this->once())->method('request')->with($this->transferMock)->willReturn($this->responseMock);

        $this->handlerMock->expects($this->once())->method('handle')->with($this->responseMock)->willReturn($this->userTokenMock);

        $this->userTokenMock->expects($this->once())->method('getStatus')->willReturn(false);

        $this->contentReaderMock->fetchSectionData();
    }

    public function testFetchSectionDataTrue()
    {
        $this->sessionMock->expects($this->atMost(2))->method('getCanvaAccessToken')->willReturn(true);
        $this->sessionMock->expects($this->atMost(2))->method('getClientId')->willReturn(true);

        $this->contentReaderMock->fetchSectionData();
        $this->assertTrue($this->sessionMock->getCanvaAccessToken() && $this->sessionMock->getClientId());
    }
}
