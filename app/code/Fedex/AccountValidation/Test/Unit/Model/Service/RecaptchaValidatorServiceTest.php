<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Test\Unit\Model\Service;

use Exception;
use Fedex\AccountValidation\Model\Service\RecaptchaValidatorService;
use Fedex\Recaptcha\Model\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecaptchaValidatorServiceTest extends TestCase
{
    /** @var Validator&MockObject */
    private $recaptchaValidator;

    private RecaptchaValidatorService $service;

    protected function setUp(): void
    {
        $this->recaptchaValidator = $this->createMock(Validator::class);
        $this->service = new RecaptchaValidatorService($this->recaptchaValidator);
    }

    public function testValidateSkipsWhenRecaptchaDisabled(): void
    {
        $scope = 'test_scope';
        $this->recaptchaValidator->method('isRecaptchaEnabled')->with($scope)->willReturn(false);

        $this->recaptchaValidator->expects($this->never())->method('validateRecaptcha');

        $this->service->validate($scope);
        $this->assertTrue(true); // Implicit success
    }

    public function testValidatePassesWhenRecaptchaValid(): void
    {
        $scope = 'test_scope';
        $this->recaptchaValidator->method('isRecaptchaEnabled')->with($scope)->willReturn(true);
        $this->recaptchaValidator->method('validateRecaptcha')->with($scope)->willReturn(true);

        $this->service->validate($scope);
        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateThrowsExceptionWhenRecaptchaFails(): void
    {
        $scope = 'test_scope';
        $this->recaptchaValidator->method('isRecaptchaEnabled')->with($scope)->willReturn(true);
        $this->recaptchaValidator->method('validateRecaptcha')->with($scope)->willReturn(['error' => 'invalid']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recaptcha validation failed.');

        $this->service->validate($scope);
    }
}
