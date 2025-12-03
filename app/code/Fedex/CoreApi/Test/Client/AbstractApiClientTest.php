<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CoreApi\Test\Client;

use Fedex\CoreApi\Client\AbstractApiClient;
use Laminas\Http\Client\Exception\RuntimeException as ClientRuntimeException;
use Laminas\Http\Response;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Framework\HTTP\LaminasClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\CoreApi\Model\Config\Backend as CoreApiConfig;

class AbstractApiClientTest extends TestCase
{
    private const POST = 'POST';

    /**
     * @var array
     */
    public array $headers = [
        'Content-Type' => 'application/json',
    ];

    /**
     * @var AbstractApiClient
     */
    protected AbstractApiClient $abstractApiClientMock;

    /**
     * @var LaminasClientFactory|MockObject
     */
    protected LaminasClientFactory $httpClientFactoryMock;

    /**
     * @var LaminasClient|MockObject
     */
    protected LaminasClient $httpClientMock;

    /**
     * @var Response|MockObject
     */
    protected Response $responseClientMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected LoggerInterface $loggerMock;

    /**
     * @var CoreApiConfig|MockObject
     */
    protected CoreApiConfig $configHelperMock;

    public function setUp(): void
    {
        $this->httpClientFactoryMock = $this->createMock(LaminasClientFactory::class);
        $this->httpClientMock = $this->createMock(LaminasClient::class);
        $this->responseClientMock = $this->createMock(Response::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configHelperMock = $this->createMock(CoreApiConfig::class);

        $this->abstractApiClientMock = new AbstractApiClient(
            $this->httpClientFactoryMock,
            $this->loggerMock,
            $this->configHelperMock
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $timeOut = 10;
        $params = [
            'param1' => 1,
            'param2' => 2
        ];
        $rawData = '{"rateRequest":{"products":[{"id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1649535729529,"validateContent":false}]}}';

        $this->responseClientMock->expects($this->once())->method('getStatusCode')->willReturn(200);
        $this->responseClientMock->expects($this->once())->method('getBody')->willReturn('{"request":true}');

        $this->httpClientMock->expects($this->once())->method('setHeaders')
            ->with($this->headers)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setUri')
            ->with('https://test.fedex.com/fedex/url/key')->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setMethod')
            ->with('POST')->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setParameterPost')
            ->with($params)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setOptions')
            ->with(['timeout' => $timeOut, 'allow_unwise' => true, 'adapter' => 'Laminas\Http\Client\Adapter\Curl'])->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setRawBody')
            ->with($rawData)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('send')->willReturn($this->responseClientMock);

        $this->httpClientFactoryMock->expects($this->once())->method('create')->willReturn($this->httpClientMock);

        $this->configHelperMock->expects($this->once())->method('getApiTimeOut')->willReturn($timeOut);

        $return = $this->abstractApiClientMock->execute('fedex/url/key', 'POST', $rawData, $params);
        $this->assertIsString($return);
        $this->assertEquals('{"request":true}', $return);
    }

    /**
     * @return void
     */
    public function testExecute404(): void
    {
        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $timeOut = 10;
        $params = [
            'param1' => 1,
            'param2' => 2
        ];
        $rawData = '{"rateRequest":{"products":[{"id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1649535729529,"validateContent":false}]}}';

        $this->responseClientMock->expects($this->once())->method('getStatusCode')->willReturn(404);
        $this->responseClientMock->expects($this->once())->method('getBody')->willReturn('404 NOT FOUND');

        $this->httpClientMock->expects($this->once())->method('setHeaders')
            ->with($this->headers)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setUri')
            ->with('https://test.fedex.com/fedex/url/key')->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setMethod')
            ->with(self::POST)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setParameterPost')
            ->with($params)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setOptions')
            ->with(['timeout' => $timeOut, 'allow_unwise' => true, 'adapter' => 'Laminas\Http\Client\Adapter\Curl'])->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setRawBody')
            ->with($rawData)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('send')->willReturn($this->responseClientMock);

        $this->httpClientFactoryMock->expects($this->once())->method('create')->willReturn($this->httpClientMock);

        $this->configHelperMock->expects($this->once())->method('getApiTimeOut')->willReturn($timeOut);

        $return = $this->abstractApiClientMock->execute('fedex/url/key', 'POST', $rawData, $params);
        $this->assertIsString($return);
        $this->assertEquals('404 NOT FOUND', $return);
    }

    /**
     * @return void
     */
    public function testExecute500(): void
    {
        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $timeOut = 10;
        $params = [
            'param1' => 1,
            'param2' => 2
        ];
        $rawData = '{"rateRequest":{"products":[{"id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1649535729529,"validateContent":false}]}}';

        $this->responseClientMock->expects($this->once())->method('getStatusCode')->willReturn(500);
        $this->responseClientMock->expects($this->once())->method('getBody')->willReturn('500');

        $this->httpClientMock->expects($this->once())->method('setHeaders')
            ->with($this->headers)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setUri')
            ->with('https://test.fedex.com/fedex/url/key')->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setMethod')
            ->with(self::POST)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setParameterPost')
            ->with($params)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setOptions')
            ->with(['timeout' => $timeOut, 'allow_unwise' => true, 'adapter' => 'Laminas\Http\Client\Adapter\Curl'])->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setRawBody')
            ->with($rawData)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('send')->willReturn($this->responseClientMock);

        $this->httpClientFactoryMock->expects($this->once())->method('create')->willReturn($this->httpClientMock);

        $this->configHelperMock->expects($this->once())->method('getApiTimeOut')->willReturn($timeOut);

        $return = $this->abstractApiClientMock->execute('fedex/url/key', 'POST', $rawData, $params);
        $this->assertIsString($return);
        $this->assertEquals('500', $return);
    }

    /**
     * @return void
     */
    public function testExecuteException(): void
    {
        $this->abstractApiClientMock->domain = 'https://test.fedex.com/';
        $timeOut = 10;
        $params = [
            'param1' => 1,
            'param2' => 2
        ];
        $rawData = '{"rateRequest":{"products":[{"id":"1466693799380","version":2,"name":"Posters","qty":1,"priceable":true,"instanceId":1649535729529,"validateContent":false}]}}';

        $this->httpClientMock->expects($this->once())->method('setHeaders')
            ->with($this->headers)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setUri')
            ->with('https://test.fedex.com/fedex/url/key')->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setMethod')
            ->with(self::POST)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setParameterPost')
            ->with($params)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setOptions')
            ->with(['timeout' => $timeOut, 'allow_unwise' => true, 'adapter' => 'Laminas\Http\Client\Adapter\Curl'])->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('setRawBody')
            ->with($rawData)->willReturnSelf();
        $this->httpClientMock->expects($this->once())->method('send')
            ->willThrowException(new ClientRuntimeException('Exception error message'));

        $this->httpClientFactoryMock->expects($this->once())->method('create')->willReturn($this->httpClientMock);

        $this->configHelperMock->expects($this->once())->method('getApiTimeOut')->willReturn($timeOut);

        $this->assertFalse($this->abstractApiClientMock->execute('fedex/url/key', 'POST', $rawData, $params));
    }

    /**
     * @return void
     */
    public function testExecuteDomainNull(): void
    {
        $this->assertFalse($this->abstractApiClientMock->execute('', ''));
    }
}
