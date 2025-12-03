<?php

declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Rate\Test\Unit\Helper;

use Fedex\Rate\Helper\ApiRequest;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\Delivery\Helper\Data as DeliveryData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Rate\Api\Data\ConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Fedex\Header\Helper\Data;

class ApiRequestTest extends TestCase
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ConfigInterface
     */
    protected $configInterface;

    /**
     * @var DeliveryData
     */
    protected $deliveryData;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var PunchoutHelper
     */
    protected $punchoutHelper;

    /**
     * @var LoggerInterface
     */
    protected $loggerInterface;

    /**
     * @var AbstractApiClient
     */
    protected $abstractApiClient;
    
    /**
     * @var Json
     */
    protected $json;

    /**
     * @var JsonValidator
     */
    protected $jsonValidator;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var ApiRequest
     */
    protected $apiRequestHelper;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterface = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->deliveryData = $this->getMockBuilder(DeliveryData::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractApiClient = $this->getMockBuilder(AbstractApiClient::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->json = $this->getMockBuilder(Json::class)
            ->setMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->jsonValidator = $this->getMockBuilder(JsonValidator::class)
            ->setMethods(['isValid'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['post', 'getBody'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->data = $this->getMockBuilder(Data::class)
            ->setMethods(['getAuthHeaderValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->apiRequestHelper = $objectManagerHelper->getObject(
            ApiRequest::class,
            [
                'context' => $this->context,
                'configInterface' => $this->configInterface,
                'helper' => $this->deliveryData,
                'customerSession' => $this->customerSession,
                'punchoutHelper' => $this->punchoutHelper,
                'logger' => $this->loggerInterface,
                'apiClient' => $this->abstractApiClient,
                'json' => $this->json,
                'jsonValidator' => $this->jsonValidator,
                'curl' => $this->curl,
                'data' => $this->data
            ]
        );
    }

    public function testPriceProductApiSuccessfulResponse()
    {
        $payload = '{"some": "payload"}';
        $apiResponse = '{"rates": [{"rate": 10}]}';
        $authHeaderValue = 'Bearer abcdef123456';

        $expectedResponse = [
            'response' => [
                'rates' => [
                    ['rate' => 10]
                ]
            ],
            'status' => true
        ];
        $this->data->method('getAuthHeaderValue')->willReturn($authHeaderValue);
        $this->curl->method('post')->willReturn($apiResponse);
        $this->curl->method('getBody')->willReturn($apiResponse);
        $this->jsonValidator->method('isValid')->willReturn(true);
        $this->json->method('unserialize')->willReturn(json_decode($apiResponse, true));
        $response = $this->apiRequestHelper->priceProductApi($payload);

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPriceProductApiNoDataReturned()
    {
        $payload = '{"some": "payload"}';
        $apiResponse = '';

        $expectedResponse = [
            'errors' => ['Error found no data'],
            'status' => false
        ];

        $this->data->method('getAuthHeaderValue')->willReturn('Bearer abcdef123456');
        $this->curl->method('post')->willReturn($apiResponse);
        $this->curl->method('getBody')->willReturn($apiResponse);
        $this->jsonValidator->method('isValid')->willReturn(false);

        $response = $this->apiRequestHelper->priceProductApi($payload);

        $this->assertEquals($expectedResponse, $response);
    }

    public function testPriceProductApiInvalidJson()
    {
        $payload = '{"some": "payload"}';
        $apiResponse = '{"rates": [{"rate": 10}}';

        $expectedResponse = [
            'errors' => ['Error found no data'],
            'status' => false
        ];

        $this->data->method('getAuthHeaderValue')->willReturn('Bearer abcdef123456');
        $this->curl->method('post')->willReturn($apiResponse);
        $this->curl->method('getBody')->willReturn($apiResponse);
        $this->jsonValidator->method('isValid')->willReturn(false);

        $response = $this->apiRequestHelper->priceProductApi($payload);

        $this->assertEquals($expectedResponse, $response);
    }

    public function testSetHeaders()
    {
        $payload = '{"test":"value"}';
        $authHeader = 'Authorization: Bearer xyz';
        $token = '123456token';
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'json',
            'Content-Length' => strlen($payload),
            $authHeader . $token,
        ];

        $this->data->method('getAuthHeaderValue')->willReturn($authHeader);
        $this->punchoutHelper->method('getAuthGatewayToken')->willReturn($token);

        $reflection = new \ReflectionClass(ApiRequest::class);
        $method = $reflection->getMethod('setHeaders');
        $method->setAccessible(true);
        $method->invokeArgs($this->apiRequestHelper, [$payload]);

        $apiClientProperty = $reflection->getProperty('apiClient');
        $apiClientProperty->setAccessible(true);
        $apiClient = $apiClientProperty->getValue($this->apiRequestHelper);
        $this->assertEquals($expectedHeaders, $apiClient->headers);
    }
}
