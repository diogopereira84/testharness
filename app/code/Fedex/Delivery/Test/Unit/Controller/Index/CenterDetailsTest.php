<?php
namespace Fedex\Delivery\Test\Unit\Controller\Index;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Controller\Index\CenterDetails;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Delivery\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

class CenterDetailsTest extends TestCase
{
    protected $punchoutHelperMock;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|MockObject
     */
    protected $configInterface;

    /**
     * @var \Magento\Framework\App\RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var \Fedex\Delivery\Helper\Data|MockObject
     */
    protected $helper;

    /**
     * @var \Psr\Log\LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var CenterDetails|MockObject
     */
    private $centerDetails;

    protected \Magento\Framework\HTTP\Client\Curl $curl;

    protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactoryMock;

    /**
     * Mock token key
     */
    public const TOKEN_KEY = '4f5e303d-c10f-40bf-a586-16e177faaec3';

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configInterface=$this->getMockBuilder(ScopeConfigInterface::class)->getMockForAbstractClass();
        $this->request=$this->getMockBuilder(RequestInterface::class)
        ->setMethods(['getPostValue'])
        ->getMockForAbstractClass();
        $this->helper=$this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger=$this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->punchoutHelperMock  = $this->getMockBuilder(\Fedex\Punchout\Helper\Data::class)
            ->setMethods(['getTazToken', 'getGatewayToken','getAuthGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->curl=$this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->centerDetails = $this->objectManagerHelper->getObject(
            CenterDetails::class,
            [
                    'configInterface' => $this->configInterface,
                    'request' => $this->request,
                    'helper' => $this->helper,
                    'logger'=>$this->logger,
                    'curl'=>$this->curl,
                    'resultJsonFactory'=>$this->resultJsonFactoryMock,
                    'gateTokenHelper' => $this->punchoutHelperMock
                ]
        );
    }

    /**
     * Test execute.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecute()
    {
        $responseData=['output'=>['location'=>'123']];
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        $this->request->expects($this->any())->method('getPostValue')->with('locationId')->willReturn('1234');
        $this->configInterface->expects($this->any())->method('getValue')->with('fedex/general/location_details_api_url')->willReturn('https://apidrt.idev.fedex.com/location/fedexoffice/v2/locations');
        $this->curl->expects($this->any())->method('getBody')->willreturn('{"output":{"location":{"Id":"1579"}}}');
        $jsonMock = $this->getMockBuilder(Json::class)
                ->disableOriginalConstructor()
                ->getMock();
        $jsonMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($jsonMock);
        $response = $this->centerDetails->execute();
        $this->assertInstanceOf(Json::class, $response);
    }

    public function testExecuteWithErrors()
    {
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        $this->request->expects($this->any())->method('getPostValue')->with('locationId')->willReturn('1234');
        $this->configInterface->expects($this->any())->method('getValue')->with('fedex/general/location_details_api_url')->willReturn('https://apidrt.idev.fedex.com/location/fedexoffice/v2/locations');
        $this->curl->expects($this->any())->method('getBody')->willreturn('{"errors":{"location":{"Id":"1579"}}}');
        $jsonMock = $this->getMockBuilder(Json::class)
                ->disableOriginalConstructor()
                ->getMock();
        $jsonMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($jsonMock);
        $response = $this->centerDetails->execute();
        $this->assertInstanceOf(Json::class, $response);
    }

    public function testExecuteWithNoData()
    {
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->punchoutHelperMock->expects($this->any())->method('getAuthGatewayToken')->willReturn(static::TOKEN_KEY);
        //$this->helper->expects($this->any())->method('getGateToken')->willReturn('1234567890');
        $this->request->expects($this->any())->method('getPostValue')->with('locationId')->willReturn('1234');
        $this->configInterface->expects($this->any())->method('getValue')->with('fedex/general/location_details_api_url')->willReturn('https://apidrt.idev.fedex.com/location/fedexoffice/v2/locations');
        $this->curl->expects($this->any())->method('getBody')->willreturn('{}');
        $response = $this->centerDetails->execute();
        $this->assertEquals('Error found no data', $response);
    }
}
