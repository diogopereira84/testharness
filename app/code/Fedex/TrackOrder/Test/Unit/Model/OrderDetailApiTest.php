<?php

namespace Fedex\TrackOrder\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\TrackOrder\Model\OrderDetailApi;
use Fedex\TrackOrder\Api\CurlRequestInterface;
use Psr\Log\LoggerInterface;

class OrderDetailApiTest extends TestCase
{
    protected $curlRequestMock;
    protected $loggerMock;
    protected $orderDetailApi;

    protected function setUp(): void
    {
        $this->curlRequestMock = $this->createMock(CurlRequestInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->orderDetailApi = new OrderDetailApi(
            $this->curlRequestMock,
            $this->loggerMock
        );
    }

    public function testFetchOrderDetailFromApiSuccess()
    {
        $orderId = 12345;
        $apiResponse = [
            'order_id' => $orderId,
            'isValid' => true,
            'order_details' => ['id' => $orderId, 'status' => 'shipped']
        ];

        $this->curlRequestMock->method('sendRequest')->with($orderId)->willReturn($apiResponse);

        $result = $this->orderDetailApi->fetchOrderDetailFromApi($orderId);

        $this->assertEquals($apiResponse, $result);
    }

    public function testFetchOrderDetailFromApiOrderNotFound()
    {
        $orderId = 12345;
        $apiResponse = [
            'order_id' => $orderId,
            'isValid' => false,
            'error_message' => 'We couldn\'t find your order. Please review and retry. If the problem persists, you may try <a href="http://legacy-tracking-url.com/12345" target="_blank">legacy tracking</a>.'
        ];

        $this->curlRequestMock->method('sendRequest')->with($orderId)->willReturn($apiResponse);

        $result = $this->orderDetailApi->fetchOrderDetailFromApi($orderId);

        $this->assertEquals($apiResponse, $result);
    }

    public function testFetchOrderDetailFromApiException()
    {
        $orderId = 12345;
        $exceptionMessage = 'API Error';

        $this->curlRequestMock->method('sendRequest')->willThrowException(new \Exception($exceptionMessage));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Fedex\TrackOrder\Model\OrderDetailApi::fetchOrderDetailFromApi:39 Error Message: ' . $exceptionMessage);

        $result = $this->orderDetailApi->fetchOrderDetailFromApi($orderId);

        $expectedResult = [
            'order_id' => $orderId,
            'isValid' => false,
            'error_message' => 'An error occurred while fetching the order details. Please try again later. ' . $exceptionMessage
        ];

        $this->assertEquals($expectedResult, $result);
    }
}