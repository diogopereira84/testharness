<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Test\Unit\Controller\Index;

use Exception;
use Fedex\AccountValidation\Controller\Index\Index;
use Fedex\AccountValidation\Model\Service\FedExAccountValidator;
use Fedex\AccountValidation\Model\Service\QuoteUpdater;
use Fedex\AccountValidation\Model\Service\RecaptchaValidatorService;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IndexTest extends TestCase
{
    private JsonFactory&MockObject $jsonFactory;
    private RequestInterface&MockObject $request;
    private FedExAccountValidator&MockObject $fedExValidator;
    private LoggerInterface&MockObject $logger;
    private RecaptchaValidatorService&MockObject $recaptchaValidatorService;
    private QuoteUpdater&MockObject $quoteUpdater;
    private Json&MockObject $resultJson;
    private Index $controller;

    protected function setUp(): void
    {
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->fedExValidator = $this->createMock(FedExAccountValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->recaptchaValidatorService = $this->createMock(RecaptchaValidatorService::class);
        $this->quoteUpdater = $this->createMock(QuoteUpdater::class);
        $this->resultJson = $this->createMock(Json::class);

        $this->jsonFactory->method('create')->willReturn($this->resultJson);

        $this->controller = new Index(
            $this->jsonFactory,
            $this->request,
            $this->fedExValidator,
            $this->logger,
            $this->recaptchaValidatorService,
            $this->quoteUpdater
        );
    }

    public function testExecuteReturnsErrorWhenAccountNumberIsEmpty(): void
    {
        $this->fedExValidator->method('isFedexAccountValidationCallEnabled')->willReturn(true);
        $this->request->method('getParam')->with('fedexShippingAccountNumber')->willReturn('');
        $this->resultJson->expects($this->once())->method('setData')->with([
            'error' => true,
            'message' => __('Account number is required.')
        ])->willReturn($this->resultJson);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteReturnsValidResponseWhenAccountNumberIsValid(): void
    {
        $this->fedExValidator->method('isFedexAccountValidationCallEnabled')->willReturn(true);
        $this->request->method('getParam')->with('fedexShippingAccountNumber')->willReturn('123456');
        $this->fedExValidator->method('isShippingAccountValid')->with('123456')->willReturn(true);
        $this->quoteUpdater->expects($this->any())->method('update')->with('123456', true);

        $this->resultJson->expects($this->once())->method('setData')->willReturn($this->resultJson);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteHandlesExceptionAndLogsError(): void
    {
        $this->fedExValidator->method('isFedexAccountValidationCallEnabled')->willReturn(true);
        $exception = new Exception('Some error');
        $this->recaptchaValidatorService->method('validate')->willThrowException($exception);
        $this->logger->expects($this->any())->method('error')->with(
            $this->stringContains('FedEx Account Validation Error'),
            $this->arrayHasKey('exception')
        );
        $this->resultJson->expects($this->any())->method('setData')->with([
            'error' => true,
            'message' => __('An error occurred while validating the FedEx account.')
        ])->willReturn($this->resultJson);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }

    public function testExecuteReturnsErrorWhenValidationCallDisabled(): void
    {
        $this->fedExValidator->method('isFedexAccountValidationCallEnabled')->willReturn(false);
        $this->resultJson->expects($this->once())->method('setData')->with([
            'error' => true,
            'message' => __('An error occurred while validating the FedEx account.')
        ])->willReturn($this->resultJson);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJson, $result);
    }
}
