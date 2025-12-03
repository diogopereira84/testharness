<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\Recaptcha\Model\Validator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Framework\App\RequestInterface;

class AddNewAccount implements  ActionInterface
{

    public const PROFILE_API_URL = "sso/general/profile_api_url";
    public const NICK_NAME_STATUS = 'nick_name_status';
    public const CHECKOUT_FEDEX_ACCOUNT_RECAPTCHA = 'checkout_fedex_account';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param Validator $recaptchaValidator
     * @param AuthHelper $authHelper
     * @param RequestInterface $request
     */
    public function __construct(
        private Context $context,
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected Validator $recaptchaValidator,
        protected AuthHelper $authHelper,
        private RequestInterface $request
    )
    {
    }

    /**
     * Update account
     *
     * @return json
     */
    public function execute()
    {
        if ($this->recaptchaValidator->isRecaptchaEnabled(self::CHECKOUT_FEDEX_ACCOUNT_RECAPTCHA)) {
            $recaptchaValidation = $this->recaptchaValidator->validateRecaptcha(
                self::CHECKOUT_FEDEX_ACCOUNT_RECAPTCHA
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
            $userProfileId = $this->request->getPost('userProfileId');
            $accountNumber = $this->request->getPost('accountNumber');
            $accountLabel = $this->request->getPost('nickName');
            $billingReference = $this->request->getPost('billingReference');
            $isPrimary = $this->request->getPost('isPrimary');
            $isNickName = $this->request->getPost('isNickName');
            $profileApiUrl = $this->enhancedProfile->getConfigValue(self::PROFILE_API_URL).'/';
            $endUrl = $profileApiUrl.$userProfileId.'/accounts';
            $postFields = '{
                "accounts": [
                    {
                        "profileAccountId": "",
                        "accountNumber": "' . $accountNumber . '",
                        "accountLabel": "' . $accountLabel . '",
                        "billingReference": "' . $billingReference . '",
                        "accountType": "PRINTING",
                        "primary": "' . $isPrimary . '"
                    }
                ]
            }';
            try {
                if (!$this->getNickNameStatus($isNickName, $accountLabel)) {
                    $apiResponse = $this->enhancedProfile->apiCall('POST', $endUrl, $postFields);
                    if (isset($apiResponse->output)) {
                        $cardInfo = $apiResponse->output;
                        $cardHtml = $this->enhancedProfile->makeNewAccountHtml($cardInfo);
                        $isPayment = false;
                        if ($isPrimary == 'true') {
                            $isPayment = $this->setPreferredPaymentMethod();
                        }
                        $this->enhancedProfile->setProfileSession();
                        $response['info'] = $cardHtml;
                        $response['message'] = __("Payment Method has been successfully added.");
                        $response['status'] = true;
                        $response['isPayment'] = $isPayment;
                        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Add New Account Response: '
                        . var_export($apiResponse, true));
                    } else {
                        $response = $apiResponse;
                    }
                } else {
                    $response['status'] = self::NICK_NAME_STATUS;
                }
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Add Account API is not working: '
                . $e->getMessage());
                $response = ["Failure" => "Add Account API is not working."];
            }
        } else {
            $response = ["Failure" => "System Error, Please Try Again."];
        }

        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }

    /**
     * Set Preferred Payment Method
     *
     * @return boolean
     */
    public function setPreferredPaymentMethod()
    {
        try {
            $isCreditCard = false;
            $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
            if (isset($profileInfo->output->profile->creditCards)) {
                $isCreditCard = true;
            }
            if (!$isCreditCard) {
                $userProfileId = $profileInfo->output->profile->userProfileId;
                $postFields = '{
                    "profile": {
                        "userProfileId": "' . $userProfileId . '",
                        "payment": {
                            "preferredPaymentMethod": "ACCOUNT"
                        }
                    }
                }';
                $profileApiUrl = $this->enhancedProfile->getConfigValue(self::PROFILE_API_URL).'/';
                $endUrl = $profileApiUrl . $userProfileId;
                $response = $this->enhancedProfile->apiCall('PUT', $endUrl, $postFields);
                if (isset($response->output->profile->payment)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Make a Default Shipping Account API is not working: ' . $e->getMessage());
        }
    }

    /**
     * Nick Name Unique Validation
     *
     * @param string $isNickName
     * @param string $accountLabel
     * @return boolean
     */
    public function getNickNameStatus($isNickName, $accountLabel)
    {
        if ($isNickName == 'true') {
            $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
            $accountsList = [];
            if (isset($profileInfo->output->profile->accounts)) {
                $accountsList = $profileInfo->output->profile->accounts;
            }
            foreach ($accountsList as $list) {
                if (strtoupper($list->accountLabel) == strtoupper($accountLabel)) {
                    return true;
                }
            }
        }
    }
}
