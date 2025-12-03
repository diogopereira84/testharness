<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Test\Unit\Model\Service;

use Fedex\AccountValidation\Model\Service\FedExAccountValidator;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FedExAccountValidatorTest extends TestCase
{
    private Curl&MockObject $curl;
    private LoggerInterface&MockObject $logger;
    private ToggleConfig&MockObject $toggleConfig;
    private Session&MockObject $customerSession;
    private PunchoutHelper&MockObject $punchoutHelper;
    private HeaderData&MockObject $headerData;

    private FedExAccountValidator $validator;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->customerSession = $this->createMock(Session::class);
        $this->punchoutHelper = $this->createMock(PunchoutHelper::class);
        $this->headerData = $this->createMock(HeaderData::class);

        $this->toggleConfig->method('getToggleConfig')->willReturn('https://example.com/validate/{accountNumber}');
        $this->punchoutHelper->method('getAuthGatewayToken')->willReturn('gateway-token');
        $this->punchoutHelper->method('getTazToken')->willReturn('taz-token');
        $this->headerData->method('getAuthHeaderValue')->willReturn('AuthHeader: ');

        $this->validator = new FedExAccountValidator(
            $this->curl,
            $this->logger,
            $this->toggleConfig,
            $this->customerSession,
            $this->punchoutHelper,
            $this->headerData
        );
    }

    public function testValidShippingAccount(): void
    {
        $response = json_encode([
            'status' => 200,
            'output' => [
                'accounts' => [
                    [
                        'accountUsage' => [
                            'originatingOpco' => 'FX',
                            'print' => [
                                'status' => 'ACTIVE',
                                'payment' => ['allowed' => null]
                            ],
                            'ship' => [
                                'status' => 'true'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->curl->expects($this->once())->method('post');
        $this->curl->method('getBody')->willReturn($response);

        $this->assertTrue($this->validator->isShippingAccountValid('123456'));
    }

    public function testThrowsExceptionFor500Status(): void
    {
        $response = json_encode([
            'output' => [
                'accounts' => [
                    [
                        'accountUsage' => [
                            'print' => ['status' => 'ACTIVE', 'payment' => ['allowed' => null]],
                            'originatingOpco' => 'FX',
                            'ship' => ['status' => 'true']
                        ]
                    ]
                ]
            ]
        ]);
        $this->curl->method('getBody')->willReturn($response);
        $this->curl->method('getStatus')->willReturn(500);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FedEx API returned HTTP 500 Internal Server Error');
        $this->validator->isShippingAccountValid('123456');
    }

    public function testThrowsExceptionWhenAccountUsageIsMissing(): void
    {
        $response = json_encode(['output' => ['accounts' => [[]]]]);
        $this->curl->method('getBody')->willReturn($response);
        $this->curl->method('getStatus')->willReturn(200);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FedEx API response missing accountUsage data');
        $this->validator->isShippingAccountValid('123456');
    }

    public function testReturnsFalseWhenAccountInactive(): void
    {
        $response = json_encode([
            'output' => [
                'accounts' => [
                    [
                        'accountUsage' => [
                            'print' => ['status' => 'INACTIVE'],
                            'originatingOpco' => 'FX',
                            'ship' => ['status' => 'true'],
                        ]
                    ]
                ]
            ]
        ]);
        $this->curl->method('getBody')->willReturn($response);
        $this->curl->method('getStatus')->willReturn(200);
        $this->assertFalse($this->validator->isShippingAccountValid('123456'));
    }

    public function testThrowsExceptionOnException(): void
    {
        $this->curl->method('post')->willThrowException(new \Exception('API Failure'));

        $this->logger->expects($this->once())->method('critical')
            ->with($this->stringContains('API call failed:'), $this->arrayHasKey('exception'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Failure');
        $this->validator->isShippingAccountValid('123456');
    }

    public function testReturnsFalseForNonShippingAccount(): void
    {
        $response = json_encode([
            'output' => [
                'accounts' => [
                    [
                        'accountUsage' => [
                            'print' => ['status' => 'ACTIVE', 'payment' => ['allowed' => 'N']],
                            'originatingOpco' => 'FXK',
                            'ship' => ['status' => 'false']
                        ]
                    ]
                ]
            ]
        ]);
        $this->curl->method('getBody')->willReturn($response);
        $this->curl->method('getStatus')->willReturn(200);
        $this->assertFalse($this->validator->isShippingAccountValid('123456'));
    }
}
