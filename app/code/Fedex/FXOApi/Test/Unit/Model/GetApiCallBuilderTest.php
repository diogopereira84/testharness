<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Test\Unit\Model;

use Fedex\FXOApi\Helper\ApiTokenHelper;
use Fedex\FXOApi\Model\GetApiCallBuilder;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetApiCallBuilderTest extends TestCase
{
    protected $curlMock;
    protected $apiTokenHelperMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $getApiCallBuilderMock;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->curlMock = $this->getMockBuilder(Curl::class)
            ->onlyMethods(['get', 'getBody', 'setOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->apiTokenHelperMock = $this->getMockBuilder(ApiTokenHelper::class)
            ->onlyMethods(['getTazToken', 'getGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);
        $this->getApiCallBuilderMock = $this->objectManager->getObject(
            GetApiCallBuilder::class,
            [
                'curl' => $this->curlMock,
                'apiTokenHelper' => $this->apiTokenHelperMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test buildGetApiCall function
     *
     * @return void
     */
    public function testBuildGetApiCall()
    {
        $apiResponseData = [
            'testapi' => 'testapidata'
        ];

        $this->testBuildGetApiOptions();
        $this->curlMock->expects($this->once())->method('get')->willReturnSelf();
        $this->curlMock->expects($this->once())->method('getBody')->willReturn(json_encode($apiResponseData));
        
        $this->assertEquals(
            $apiResponseData,
            $this->getApiCallBuilderMock->buildGetApiCall('https://dev.office.fedex.com/', 'FXO-RETAIL-GATEWAY')
        );
    }

    /**
     * Test buildGetApiOptions function
     *
     * @return void
     */
    public function testBuildGetApiOptions()
    {
        $this->curlMock->expects($this->any())->method('setOptions')->willReturnSelf();

        $this->getApiCallBuilderMock->buildGetApiOptions('FXO-RETAIL-GATEWAY');
    }

    /**
     * Test buildGetApiHeader function
     *
     * @return void
     */
    public function testBuildGetApiHeader()
    {
        $getApiHeaderVal = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Accept-Language: json',
            'Cookie: Bearer=taz_token',
            'client_id: gateway_token'
        ];

        $this->apiTokenHelperMock->method('getTazToken')->willReturn('Cookie: Bearer=taz_token');
        $this->apiTokenHelperMock->method('getGatewayToken')->willReturn('client_id: gateway_token');

        $this->assertEquals($getApiHeaderVal, $this->getApiCallBuilderMock->buildGetApiHeader('FXO-RETAIL-GATEWAY'));
    }
}
