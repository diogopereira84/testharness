<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;
use Magento\Framework\App\RequestInterface;

class DeleteAccount implements  ActionInterface
{

    public const PROFILE_API_URL = "sso/general/profile_api_url";

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param AuthHelper $authHelper
     * @param RequestInterface $request
     */
    public function __construct(
        private Context $context,
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        protected AuthHelper $authHelper,
        private RequestInterface $request
    )
    {
    }

    /**
     * Delete account
     *
     * @return json
     */
    public function execute()
    {
        if ($this->authHelper->isLoggedIn()) {
            $userProfileId = $this->request->getPost('userProfileId');
            $profileAccountId = $this->request->getPost('profileAccountId');

            $profileApiUrl = $this->enhancedProfile->getConfigValue(self::PROFILE_API_URL) . '/';
            $endUrl = $profileApiUrl . $userProfileId . '/accounts/' . $profileAccountId;
            $postFields = '';
            try {
                $response = $this->enhancedProfile->apiCall('DELETE', $endUrl, $postFields);
                $this->enhancedProfile->setProfileSession();
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Delete Account API is not working: '
                    . $e->getMessage());
                $response = ["Failure" => "Delete Account API is not working."];
            }
        } else {
            $response = ["Failure" => "System Error, Please Try Again."];
        }

        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
