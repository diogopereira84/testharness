<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;

class UpdateAccount implements ActionInterface
{

    public const PROFILE_API_URL = "sso/general/profile_api_url";
    public const NICK_NAME_STATUS = 'nick_name_status';

    /**
     * Initialize dependencies.
     *
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected RequestInterface $request
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
        $userProfileId = $this->request->getPost('userProfileId');
        $profileAccountId = $this->request->getPost('profileAccountId');
        $accountNumber = $this->request->getPost('accountNumber');
        $maskedAccountNumber = $this->request->getPost('maskedAccountNumber');
        $accountLabel = $this->request->getPost('accountLabel');
        $accountType = $this->request->getPost('accountType');
        $billingReference = $this->request->getPost('billingReference');
        $primary = $this->request->getPost('primary');
        $isNickName = $this->request->getPost('isNickName');
        
        $profileApiUrl = $this->enhancedProfile->getConfigValue(self::PROFILE_API_URL).'/';
        $endUrl = $profileApiUrl.$userProfileId.'/accounts/'.$profileAccountId;

        $postFields = '{
                            "account": {
                                "accountNumber": "'.$accountNumber.'",
                                "maskedAccountNumber": "'.$maskedAccountNumber.'",
                                "accountLabel": "'.$accountLabel.'",
                                "billingReference": "'.$billingReference.'",
                                "primary": '.$primary.',
                                "accountType": "'.$accountType.'"
                            }
                        }';

        try {
            if (!$this->getNickNameStatus($isNickName, $accountLabel, $accountNumber)) {
                $response = $this->enhancedProfile->apiCall('PUT', $endUrl, $postFields);
                $this->enhancedProfile->setProfileSession();
            } else {
                $response['status'] = self::NICK_NAME_STATUS;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Update Account API is not working: '
            . $e->getMessage());
            $response = ["Failure" => "Update Account API is not working."];
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
    
    /**
     * Nick Name Unique Validation
     *
     * @param string $isNickName
     * @param string $accountLabel
     * @param string $accountNumber
     * @return boolean
     */
    public function getNickNameStatus($isNickName, $accountLabel, $accountNumber)
    {
        if ($isNickName == 'true') {
            $profileInfo = $this->enhancedProfile->getLoggedInProfileInfo();
            $accountsList = [];
            if (isset($profileInfo->output->profile->accounts)) {
                $accountsList = $profileInfo->output->profile->accounts;
            }
            foreach ($accountsList as $list) {
                if (strtoupper($list->accountLabel) == strtoupper($accountLabel)
                && $list->accountNumber != $accountNumber) {
                    return true;
                }
            }
        }
    }
}
