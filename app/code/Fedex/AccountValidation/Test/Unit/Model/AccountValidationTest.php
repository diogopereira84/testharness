<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Test\Unit\Model;

use Fedex\AccountValidation\Model\AccountValidation;
use Fedex\AccountValidation\Api\AccountValidationInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AccountValidationTest extends TestCase
{
    private AccountValidation $accountValidation;
    private MockObject $toggleConfig;
    private MockObject $urlBuilder;
    private MockObject $enhancedProfile;
    private MockObject $logger;
    private MockObject $punchoutHelper;
    private MockObject $headerData;
    private MockObject $curl;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->enhancedProfile = $this->createMock(EnhancedProfile::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->punchoutHelper = $this->createMock(PunchoutHelper::class);
        $this->headerData = $this->createMock(HeaderData::class);
        $this->curl = $this->createMock(Curl::class);

        $this->accountValidation = new AccountValidation(
            $this->toggleConfig,
            $this->urlBuilder,
            $this->enhancedProfile,
            $this->logger,
            $this->punchoutHelper,
            $this->headerData,
            $this->curl
        );
    }

    public function testIsToggleE456656Enabled(): void
    {
        $this->toggleConfig
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(AccountValidation::XML_PATH_TOGGLE_E456656)
            ->willReturn(true);

        $result = $this->accountValidation->isToggleE456656Enabled();
        $this->assertTrue($result);
    }

    public function testGetAccountValidationUrl(): void
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('getUrl')
            ->with('accountvalidation/account/validateaccount')
            ->willReturn('http://example.com/accountvalidation/account/validateaccount');

        $url = $this->accountValidation->getAccountValidationUrl();
        $this->assertEquals('http://example.com/accountvalidation/account/validateaccount', $url);
    }

    public function testValidateAccountSuccess(): void
    {
        $accountNumber = '1234567890';
        $this->enhancedProfile
            ->expects($this->once())
            ->method('getConfigValue')
            ->willReturn('http://example.com/validate');

        $this->curl
            ->expects($this->once())
            ->method('post')
            ->willReturnSelf();

        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'output' => [
                    'accounts' => [
                        [
                            'accountUsage' => [
                                'print' => [
                                    'status' => 'active',
                                    'payment' => [
                                        'allowed' => 'Y'
                                    ]
                                ],
                                'originatingOpco' => 'FXK'
                            ]
                        ]
                    ]
                ]
            ]));

        $result = $this->accountValidation->validateAccount($accountNumber, '', '');
        $this->assertEquals('active', $result['status']);
        $this->assertEquals('PAYMENT', $result['accountType']);
    }

    public function testValidateAccountSuccessShipping(): void
    {
        $accountNumber = '1234567890';
        $this->enhancedProfile
            ->expects($this->once())
            ->method('getConfigValue')
            ->willReturn('http://example.com/validate');

        $this->curl
            ->expects($this->once())
            ->method('post')
            ->willReturnSelf();

        $this->curl
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'output' => [
                    'accounts' => [
                        [
                            'accountUsage' => [
                                'print' => [
                                    'status' => 'active',
                                    'payment' => [
                                        'allowed' => 'Y'
                                    ]
                                ],
                                'ship' => [
                                    'status' => 'true',
                                ],
                                'originatingOpco' => 'FX'
                            ]
                        ]
                    ]
                ]
            ]));

        $result = $this->accountValidation->validateAccount($accountNumber, '', '');
        $this->assertEquals('active', $result['status']);
        $this->assertEquals('SHIPPING', $result['accountType']);
    }

    public function testValidateAccountThrowsExceptionWhenAccountNotFound(): void
    {
//        $this->expectException(LocalizedException::class);
//        $this->expectExceptionMessage('An unexpected error occurred while validating the account.');

        $this->enhancedProfile
            ->expects($this->any())
            ->method('getConfigValue')
            ->willReturn('http://example.com/validate');

        $this->curl
            ->expects($this->any())
            ->method('post')
            ->willReturnSelf();

        $this->curl
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode([
                'status' => 500,
                'message' => 'Account Not Found'
            ]));

        $result = $this->accountValidation->validateAccount('', '', '');
        $this->assertFalse($result['status']);
    }

    public function testValidateAccountThrowsExceptionWhenInvalidAccountNumber(): void
    {
//        $this->expectException(LocalizedException::class);
//        $this->expectExceptionMessage('An unexpected error occurred while validating the account.');

        $result = $this->accountValidation->validateAccount('invalid_account', '', '');
        $this->assertFalse($result['status']);
    }
}
