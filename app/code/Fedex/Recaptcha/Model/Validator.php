<?php
/**
 * @category Fedex
 * @package  Fedex_Recaptcha
 * @copyright   Copyright (c) 2025 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Recaptcha\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Recaptcha\Api\ValidatorInterface;
use Fedex\Recaptcha\Logger\RecaptchaLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\ValidationConfigResolverInterface;
use Magento\ReCaptchaValidation\Model\ReCaptchaFactory;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Model\ErrorMessagesProvider;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response;

class Validator implements ValidatorInterface
{
    /**
     * @var string[]
     */
    const BYPASS_DEBUG = ['checkout_order'];
    /**
     * This is used to store whether the quote is only Prinful quote or not
     * @var bool
     */
    protected $isEligibleForPrintfulTransactionBlock = false;

    /**
     * @param CaptchaResponseResolverInterface $captchaResponseResolver
     * @param ValidationConfigResolverInterface $validationConfigResolver
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestInterface $requestInterface
     * @param ErrorProcessor $errorProcessor
     * @param ValidationResultFactory $validationResultFactory
     * @param ErrorMessagesProvider $errorMessagesProvider
     * @param ReCaptchaFactory $reCaptchaFactory
     * @param RecaptchaLogger $recaptchaLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     * @param SessionManagerInterface $sessionManagerInterface
     * @param PrintfulRecaptcha $printfulRecaptcha
     */
    public function __construct(
        protected CaptchaResponseResolverInterface  $captchaResponseResolver,
        protected ValidationConfigResolverInterface $validationConfigResolver,
        protected IsCaptchaEnabledInterface         $isCaptchaEnabled,
        protected RequestInterface                  $requestInterface,
        protected ErrorProcessor                    $errorProcessor,
        protected ValidationResultFactory           $validationResultFactory,
        protected ErrorMessagesProvider             $errorMessagesProvider,
        protected ReCaptchaFactory                  $reCaptchaFactory,
        protected RecaptchaLogger                   $recaptchaLogger,
        protected ScopeConfigInterface              $scopeConfig,
        protected ToggleConfig                      $toggleConfig,
        protected SessionManagerInterface           $sessionManagerInterface,
        protected PrintfulRecaptcha                 $printfulRecaptcha
    ) {
        $this->isEligibleForPrintfulTransactionBlock = $this->printfulRecaptcha->checkIfQuoteIsEligibleForPrintfulTransactionBlock();
    }


    /**
     * @inheritDoc
     */
    public function isRecaptchaEnabled($captchaFormName): bool
    {
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor($captchaFormName))
        {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function validateRecaptcha($captchaFormName): array|bool|null
    {
        $validationConfig = $this->validationConfigResolver->get($captchaFormName);

        try {
            $reCaptchaResponse = $this->captchaResponseResolver->resolve($this->requestInterface);
        } catch (InputException $e) {
            $this->recaptchaLogger->error($this->sessionManagerInterface->getSessionId() . ' ' . $e);
            return $this->errorProcessor->processError(
                [],
                $captchaFormName
            );
        }

        $validationResult = $this->isValid($reCaptchaResponse, $validationConfig, $captchaFormName);
        if (false === $validationResult->isValid()) {
            return $this->errorProcessor->processError(
                $validationResult->getErrors(),
                $captchaFormName
            );
        }

        return $validationResult->isValid();
    }

    /**
     * @inheritdoc
     */
    public function isValid(
        string $reCaptchaResponse,
        ValidationConfigInterface $validationConfig,
        string $captchaFormName
    ): ValidationResult {
        /** @var ReCaptcha $reCaptcha */
        $reCaptcha = $this->reCaptchaFactory->create(['secret' => $validationConfig->getPrivateKey()]);

        $extensionAttributes = $validationConfig->getExtensionAttributes();
        if ($extensionAttributes && (null !== $extensionAttributes->getScoreThreshold())) {
            if ($this->isEligibleForPrintfulTransactionBlock) {
                $extensionAttributes->setScoreThreshold(
                    $this->printfulRecaptcha->isPrintfulRecaptchaTransactionBlockThreshold()
                );
            }
            $reCaptcha->setScoreThreshold($extensionAttributes->getScoreThreshold());
        }

        $reCaptcha->setExpectedAction($captchaFormName);

        $result = $reCaptcha->verify($reCaptchaResponse, $validationConfig->getRemoteIp());
        $this->logRecaptchaResult($captchaFormName, $result, $validationConfig->getRemoteIp());

        $validationErrors = [];
        if (false === $result->isSuccess()) {
            foreach ($result->getErrorCodes() as $errorCode) {
                $validationErrors[$errorCode] = $this->errorMessagesProvider->getErrorMessage($errorCode);
            }
            // 'score-threshold-not-met' error is present in response even if some technical issue happened.
            if (count($validationErrors) > 1) {
                unset($validationErrors[ReCaptcha::E_SCORE_THRESHOLD_NOT_MET]);
            }
        }

        return $this->validationResultFactory->create(['errors' => $validationErrors]);
    }

    /**
     * @param Response $result
     * @param string $remoteIp
     */
    private function logRecaptchaResult($captchaFormName, $result, $remoteIp = null): void
    {
        if($this->isDebugModeEnabled($captchaFormName, $result->isSuccess())) {
            $logMessage = [
                'Form' => $captchaFormName,
                'Success' => ($result->isSuccess() ? 'true' : 'false'),
                'Score:' => $result->getScore(),
                'Action:' => $result->getAction(),
                'Hostname:' => $result->getHostname(),
                'ChallengeTs:' => $result->getChallengeTs(),
                'IP:' => $remoteIp
            ];
            if (!empty($result->getErrorCodes())) {
                $logMessage['ErrorCodes'] = implode(', ', $result->getErrorCodes());
                $this->recaptchaLogger->error($this->sessionManagerInterface->getSessionId()
                    . ' Recaptcha Result Error => ' . json_encode($logMessage));
            } else {
                $this->recaptchaLogger->info($this->sessionManagerInterface->getSessionId()
                    . ' Recaptcha Result => ' . json_encode($logMessage));
            }
        }
    }

    /**
     * @param $captchaFormName
     * @param $isSuccess
     * @return bool
     */
    private function isDebugModeEnabled($captchaFormName, $isSuccess): bool
    {
        $debugList = $this->scopeConfig->getValue('recaptcha_frontend/type_recaptcha_v3/debug');
        if (!$debugList && !in_array($captchaFormName, self::BYPASS_DEBUG)) {
            return false;
        }
        if(in_array($captchaFormName, explode(',', (string)$debugList))
            || (in_array($captchaFormName, self::BYPASS_DEBUG) && !$isSuccess)) {
            return true;
        }
        return false;
    }
}
