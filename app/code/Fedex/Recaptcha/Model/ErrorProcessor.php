<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Model;

use Fedex\Recaptcha\Logger\RecaptchaLogger;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\ReCaptchaUi\Model\ErrorMessageConfigInterface;
use Magento\ReCaptchaValidationApi\Model\ValidationErrorMessagesProvider;

/**
 * Process error during ajax login
 *
 * Set "no dispatch" flag and error message to Response
 */
class ErrorProcessor
{
    /**
     * @param ActionFlag $actionFlag
     * @param SerializerInterface $serializer
     * @param RecaptchaLogger $recaptchaLogger
     * @param ErrorMessageConfigInterface $errorMessageConfig
     * @param ValidationErrorMessagesProvider $validationErrorMessagesProvider
     * @param SessionManagerInterface $sessionManagerInterface
     */
    public function __construct(
        private ActionFlag $actionFlag,
        private SerializerInterface $serializer,
        private RecaptchaLogger $recaptchaLogger,
        private ErrorMessageConfigInterface $errorMessageConfig,
        private ValidationErrorMessagesProvider $validationErrorMessagesProvider,
        protected SessionManagerInterface $sessionManagerInterface
    ){}

    /**
     * Set "no dispatch" flag and error message to Response
     *
     * @param array $errorMessages
     * @param string $sourceKey
     * @return array
     */
    public function processError(array $errorMessages, string $sourceKey): array
    {
        $validationErrorText = $this->errorMessageConfig->getValidationFailureMessage();
        $technicalErrorText = $this->errorMessageConfig->getTechnicalFailureMessage();

        $message = $errorMessages ? $validationErrorText : $technicalErrorText;

        foreach ($errorMessages as $errorMessageCode => $errorMessageText) {
            if (!$this->isValidationError($errorMessageCode)) {
                $message = $technicalErrorText;
                $this->recaptchaLogger->error(
                    __(
                        $this->sessionManagerInterface->getSessionId()
                        . ' reCAPTCHA \'%1\' form error: %2',
                        $sourceKey,
                        $errorMessageText
                    )
                );
            }
        }

        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        return [
            'status' => 'recaptcha_error',
            'message' => $message,
        ];
    }

    /**
     * Check if error code present in validation errors list.
     *
     * @param string $errorMessageCode
     * @return bool
     */
    private function isValidationError(string $errorMessageCode): bool
    {
        return $errorMessageCode !== $this->validationErrorMessagesProvider->getErrorMessage($errorMessageCode);
    }
}
