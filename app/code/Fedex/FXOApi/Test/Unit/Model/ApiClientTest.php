<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Test\Unit\Model;

use Fedex\FXOApi\Model\ApiClient;
use Fedex\FXOApi\Model\GetApiCallBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ApiClientTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $getApiCallBuilderMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $apiClientMock;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->getApiCallBuilderMock = $this->getMockBuilder(GetApiCallBuilder::class)
            ->onlyMethods(['buildGetApiCall'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->apiClientMock = $this->objectManager->getObject(
            ApiClient::class,
            [
                'logger' => $this->loggerMock,
                'getApiCallBuilder' => $this->getApiCallBuilderMock
            ]
        );
    }

    /**
     * Test fxoApiCall function
     *
     * @return void
     */
    public function testfxoApiCall()
    {
        $apiCallReturnVal = ['test' => 'testing'];

        $this->getApiCallBuilderMock->method('buildGetApiCall')->willReturn(['test' => 'testing']);

        $this->assertEquals(
            $apiCallReturnVal,
            $this->apiClientMock->fxoApiCall('get', 'https://dev.office.fedex.com/', 'FXO-RETAIL-GATEWAY')
        );
    }
}
