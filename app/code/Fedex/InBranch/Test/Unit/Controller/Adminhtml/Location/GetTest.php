<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Test\Unit\Controller\Adminhtml\Location;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\InBranch\Controller\Adminhtml\Location\Get;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Punchout\Helper\Data;
use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\Controller\Result\Json;

class GetTest extends TestCase
{
    protected $get;
    protected ObjectManager $objectManager;
    protected MockObject|RequestInterface $requestMock;
    protected LoggerInterface|MockObject $loggerMock;
    protected MockObject|Curl $curlMock;
    protected MockObject|JsonFactory $jsonFactoryMock;
    protected MockObject|Json $jsonMock;
    protected MockObject|ScopeConfigInterface $configInterfaceMock;
    protected Data|MockObject $gateTokenHelperMock;
    protected MockObject|HeaderData $headerDataMock;

    const TOKEN_KEY = '4f5e303d-c10f-40bf-a586-16e177faaec3';

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->addMethods(['setData'])
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->getMockForAbstractClass();
        $this->gateTokenHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerDataMock = $this->getMockBuilder(HeaderData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->get = $this->objectManager->getObject(
            Get::class,
            [
                'configInterface' => $this->configInterfaceMock,
                'logger' => $this->loggerMock,
                'request' => $this->requestMock,
                'curl' => $this->curlMock,
                'resultJsonFactory' => $this->jsonFactoryMock,
                'gateTokenHelper' => $this->gateTokenHelperMock,
                'headerData' => $this->headerDataMock
            ]
        );
    }

    public function testExecute()
    {
        $this->gateTokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(static::TOKEN_KEY);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->with('locationId')
            ->willReturn('1234');

        $this->configInterfaceMock->expects($this->any())
            ->method('getValue')
            ->with('fedex/general/location_details_api_url')
            ->willReturn('https://apidrt.idev.fedex.com/location/fedexoffice/v2/locations');

        $this->curlMock->expects($this->once())
            ->method('setOptions');
        $this->curlMock->expects($this->once())
            ->method('getBody')
            ->willreturn('{"output":{"location":{"Id":"1579"}}}');

        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->headerDataMock->expects($this->once())
            ->method('getAuthHeaderValue')
            ->willReturn('client_id');

        $result = $this->get->execute();
        $this->assertInstanceOf(Json::class, $result);
    }

    public function testExecuteBadResponse()
    {
        $this->gateTokenHelperMock->expects($this->any())
            ->method('getAuthGatewayToken')
            ->willReturn(static::TOKEN_KEY);

        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->with('locationId')
            ->willReturn('1234');

        $this->configInterfaceMock->expects($this->any())
            ->method('getValue')
            ->with('fedex/general/location_details_api_url')
            ->willReturn('https://apidrt.idev.fedex.com/location/fedexoffice/v2/locations');

        $this->curlMock->expects($this->once())
            ->method('setOptions');
        $this->curlMock->expects($this->once())
            ->method('getBody')
            ->willreturn('');

        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $this->jsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->headerDataMock->expects($this->once())
            ->method('getAuthHeaderValue')
            ->willReturn('client_id');

        $result = $this->get->execute();
        $this->assertInstanceOf(Json::class, $result);
    }
}
