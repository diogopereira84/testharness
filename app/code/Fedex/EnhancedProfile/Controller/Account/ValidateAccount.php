<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\Recaptcha\Model\Validator;
use Magento\Framework\App\ActionInterface;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Framework\App\RequestInterface;

class ValidateAccount implements ActionInterface
{
    public const PROFILE_FEDEX_ACCOUNT_RECAPTCHA = 'profile_fedex_account';

    /**
     * Initialize dependencies.
     *
     * @param EnhancedProfile $enhancedProfile
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param Validator $recaptchaValidator
     * @param AuthHelper $authHelper
     * @param RequestInterface $request
     */
    public function __construct(
        protected EnhancedProfile $enhancedProfile,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected Validator $recaptchaValidator,
        protected AuthHelper $authHelper,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Validate account
     *
     * @return boolean
     */
    public function execute()
    {
        if ($this->recaptchaValidator->isRecaptchaEnabled(self::PROFILE_FEDEX_ACCOUNT_RECAPTCHA)) {
            $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(
                self::PROFILE_FEDEX_ACCOUNT_RECAPTCHA
            );
            if(is_array($recaptchaValidation)) {
                $result = $this->jsonFactory->create();
                $result->setData($recaptchaValidation);

                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Add Account API is not working: Recaptcha Error');
                return $result;
            }
        }

        $response = [];

            if ($this->authHelper->isLoggedIn()) {
                $accountNumber = $this->request->getPost('accountNumber');
                $getAccountSummary = $this->enhancedProfile->getAccountSummary($accountNumber);
                $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
                $isFedExAccount = false;
                if (isset($profileInfo->output->profile->accounts)) {
                    $accountsList = $profileInfo->output->profile->accounts;
                    foreach ($accountsList as $account) {
                        if (isset($account->accountType) && (strtolower($account->accountType) == 'printing')) {
                            $accountSummary = $this->enhancedProfile->getAccountSummary($account->accountNumber);
                            if (!empty($accountSummary)) {
                                if (strtolower($accountSummary['account_status']) == 'active') {
                                    $isFedExAccount = true;
                                }
                            }
                        }
                    }
                }
                $response['status'] = false;
                if (!empty($getAccountSummary)) {
                    if ($getAccountSummary['account_status']) {
                        $response['info'] = $getAccountSummary;
                        $response['status'] = true;
                        $response['isFedExAccount'] = $isFedExAccount;
                    }
                }
            } else {
                $response['status'] = false;
            }

        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }
}
