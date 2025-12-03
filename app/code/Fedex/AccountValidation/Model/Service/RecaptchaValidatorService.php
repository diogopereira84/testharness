<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Model\Service;

use Fedex\Recaptcha\Model\Validator;
use Exception;

class RecaptchaValidatorService
{
    public function __construct(
        private readonly Validator $recaptchaValidator
    ) {}

    /**
     * Validate Recaptcha for the given scope.
     *
     * @param string $scope
     * @throws Exception
     */
    public function validate(string $scope): void
    {
        if (!$this->recaptchaValidator->isRecaptchaEnabled($scope)) {
            return;
        }

        $result = $this->recaptchaValidator->validateRecaptcha($scope);
        if (is_array($result)) {
            throw new Exception('Recaptcha validation failed.');
        }
    }
}
