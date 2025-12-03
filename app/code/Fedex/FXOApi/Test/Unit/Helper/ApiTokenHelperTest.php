<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 FedEx
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Test\Unit\Helper;

use Fedex\FXOApi\Helper\ApiTokenHelper;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ApiTokenHelperTest extends TestCase
{
    protected $punchoutHelperMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $headerDataMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $apiTokenHelperMock;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->punchoutHelperMock = $this->getMockBuilder(PunchoutHelper::class)
            ->onlyMethods(['getTazToken', 'getAuthGatewayToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->headerDataMock = $this->getMockBuilder(HeaderData::class)
            ->onlyMethods(['getAuthHeaderValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->apiTokenHelperMock = $this->objectManager->getObject(
            ApiTokenHelper::class,
            [
                'punchoutHelper' => $this->punchoutHelperMock,
                'logger' => $this->loggerMock,
                'headerData' => $this->headerDataMock
            ]
        );
    }

    /**
     * Test getTazToken function
     *
     * @return void
     */
    public function testGetTazToken()
    {
        $getTazTokenReturnValue = 'Cookie: Bearer=taz_token';

        $this->punchoutHelperMock->method('getTazToken')->willReturn('taz_token');

        $this->assertEquals($getTazTokenReturnValue, $this->apiTokenHelperMock->getTazToken());
    }

    /**
     * Test getGatewayToken function
     *
     * @return void
     */
    public function testGetGatewayToken()
    {
        $gatewayTokenReturnVal = 'client_id: gateway_token';

        $this->testGetRetailGatewayToken();

        $this->assertEquals($gatewayTokenReturnVal, $this->apiTokenHelperMock->getGatewayToken('fxo-RETAIL-GATEWAY'));
    }

    /**
     * Test getRetailGatewayToken function
     *
     * @return void
     */
    public function testGetRetailGatewayToken()
    {
        $retailGatewayTokenReturnVal = 'client_id: gateway_token';

        $this->punchoutHelperMock->method('getAuthGatewayToken')->willReturn('gateway_token');
        $this->headerDataMock->method('getAuthHeaderValue')->willReturn('client_id: ');

        $this->assertEquals($retailGatewayTokenReturnVal, $this->apiTokenHelperMock->getRetailGatewayToken());
    }
}
