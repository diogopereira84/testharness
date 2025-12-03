<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Api;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationResult;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

/**
 * Validate reCAPTCHA response
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Check if reCAPTCHA is enabled for the form
     *
     * @param $captchaFormName
     * @return bool
     * @throws InputException
     */
    public function isRecaptchaEnabled($captchaFormName): bool;

    /**
     * Validate reCAPTCHA
     *
     * @param $captchaFormName
     * @return array|bool|null
     */
    public function validateRecaptcha($captchaFormName): array|bool|null;

    /**
     * Return true if reCAPTCHA validation has passed
     *
     * @param string $reCaptchaResponse
     * @param ValidationConfigInterface $validationConfig
     * @param string $captchaFormName
     * @return ValidationResult
     */
    public function isValid(
        string $reCaptchaResponse,
        ValidationConfigInterface $validationConfig,
        string $captchaFormName
    ): ValidationResult;
}
