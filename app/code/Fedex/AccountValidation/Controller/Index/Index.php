<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Controller\Index;

use Exception;
use Fedex\AccountValidation\Model\Service\FedExAccountValidator;
use Fedex\AccountValidation\Model\Service\QuoteUpdater;
use Fedex\AccountValidation\Model\Service\RecaptchaValidatorService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller to validate FedEx shipping account number via API.
 */
class Index implements HttpGetActionInterface, HttpPostActionInterface
{
    public const CHECKOUT_SHIPPING_ACCOUNT_VALIDATION_RECAPTCHA = 'checkout_shipping_account_validation';

    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly FedExAccountValidator $fedExValidator,
        private readonly LoggerInterface $logger,
        private readonly RecaptchaValidatorService $recaptchaValidatorService,
        private readonly QuoteUpdater $quoteUpdater
    ) {}

    public function execute(): ResultInterface
    {
        $resultJson = $this->jsonFactory->create();
        if($this->fedExValidator->isFedexAccountValidationCallEnabled()){
            try {
                $this->recaptchaValidatorService->validate(self::CHECKOUT_SHIPPING_ACCOUNT_VALIDATION_RECAPTCHA);
                $accountNumber = (string)$this->request->getParam('fedexShippingAccountNumber');
                if ($accountNumber === '') {
                    return $resultJson->setData([
                        'error' => true,
                        'message' => __('Account number is required.'),
                    ]);
                }
                $isValid = $this->fedExValidator->isShippingAccountValid($accountNumber);
                $this->quoteUpdater->update($accountNumber, $isValid);
                return $resultJson->setData(['error' => !$isValid]);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ' - FedEx Account Validation Error: ' . $e->getMessage(), ['exception' => $e]);
                return $resultJson->setData([
                    'error' => true,
                    'message' => __('An error occurred while validating the FedEx account.'),
                ]);
            }
        }
        return $resultJson->setData([
            'error' => true,
            'message' => __('An error occurred while validating the FedEx account.'),
        ]);
    }
}
