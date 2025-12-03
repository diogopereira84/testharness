<?php

namespace Fedex\TrackOrder\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\TrackOrder\Model\CurlRequest;
use Fedex\TrackOrder\Model\Config;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\Punchout\Helper\Data as GateTokenHelper;
use Fedex\Header\Helper\Data as HeaderHelper;

class CurlRequestTest extends TestCase
{
    private $configMock;
    private $curlMock;
    private $loggerMock;
    private $gateTokenHelperMock;
    private $headerHelperMock;
    private $curlRequest;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->curlMock = $this->createMock(Curl::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->gateTokenHelperMock = $this->createMock(GateTokenHelper::class);
        $this->headerHelperMock = $this->createMock(HeaderHelper::class);

        $this->curlRequest = new CurlRequest(
            $this->configMock,
            $this->curlMock,
            $this->loggerMock,
            $this->gateTokenHelperMock,
            $this->headerHelperMock
        );
    }

    public function testSendRequestWithValidOrder()
    {
        $orderId = 12345;
        $apiUrl = 'http://api.example.com/order';
        $accessToken = 'access_token';
        $gateWayToken = 'gateway_token';
        $authHeaderVal = 'auth_header_value';
        $responseBody = json_encode([
            'output' => [
                'order' => [
                    'orderNumber' => '12345',
                    'status' => 'shipped',
                    'totalAmount' => 100.00,
                    'submissionTime' => '2023-01-01T00:00:00Z',
                    'productionDueTime' => '2023-01-02T00:00:00Z',
                    'expectedReleaseTime' => '2023-01-03T00:00:00Z',
                    "productDetails" => [
                        [
                            "product" => [
                                "id" => "1724781532082",
                                "version" => 1,
                                "name" => "One Sheet",
                                "qty" => 1,
                            ]
                        ],
                        [
                            "product" => [
                                "id" => "1724781532083",
                                "version" => 1,
                                "name" => "Sell Sheet",
                                "qty" => 2,
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->configMock->method('getOrderDetailXapiUrl')->willReturn($apiUrl);
        $this->gateTokenHelperMock->method('getTazToken')->willReturn($accessToken);
        $this->gateTokenHelperMock->method('getAuthGatewayToken')->willReturn($gateWayToken);
        $this->headerHelperMock->method('getAuthHeaderValue')->willReturn($authHeaderVal);
        $this->curlMock->method('getBody')->willReturn($responseBody);

        $expectedResult = [
            'order_id' => $orderId,
            'isValid' => true,
            'order_details' => [
                'orderNumber' => '12345',
                'status' => 'shipped',
                'totalAmount' => 100.00,
                'submissionTime' => '2023-01-01T00:00:00Z',
                'productionDueTime' => '2023-01-02T00:00:00Z',
                'expectedReleaseTime' => '2023-01-03T00:00:00Z',
                "productDetails" => [
                    [
                        "product" => [
                            "id" => "1724781532082",
                            "version" => 1,
                            "name" => "One Sheet",
                            "qty" => 1,
                        ]
                    ],
                    [
                        "product" => [
                            "id" => "1724781532083",
                            "version" => 1,
                            "name" => "Sell Sheet",
                            "qty" => 2,
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->curlRequest->sendRequest($orderId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testSendRequestWithInvalidOrder()
    {
        $orderId = 12345;
        $apiUrl = 'http://api.example.com/order';
        $accessToken = 'access_token';
        $gateWayToken = 'gateway_token';
        $authHeaderVal = 'auth_header_value';
        $responseBody = json_encode([
            'output' => [
                'order' => []
            ]
        ]);

        $this->configMock->method('getOrderDetailXapiUrl')->willReturn($apiUrl);
        $this->gateTokenHelperMock->method('getTazToken')->willReturn($accessToken);
        $this->gateTokenHelperMock->method('getAuthGatewayToken')->willReturn($gateWayToken);
        $this->headerHelperMock->method('getAuthHeaderValue')->willReturn($authHeaderVal);
        $this->curlMock->method('getBody')->willReturn($responseBody);
        $this->configMock->method('getLegacyTrackOrderUrl')->willReturn('http://legacy-tracking-url.com/');

        $expectedResult = [
            'order_id' => $orderId,
            'isValid' => false,
            'error_message' => 'We couldn\'t find your order. Please review and retry. If the problem persists, you may try <a href="http://legacy-tracking-url.com/12345" target="_blank">legacy tracking</a>.'
        ];

        $result = $this->curlRequest->sendRequest($orderId);

        $this->assertEquals($expectedResult, $result);
    }
}