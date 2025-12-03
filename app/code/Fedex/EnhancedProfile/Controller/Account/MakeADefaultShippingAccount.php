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
use Magento\Framework\App\RequestInterface;

class MakeADefaultShippingAccount implements ActionInterface
{

    public const PROFILE_API_URL = "sso/general/profile_api_url";

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Context $context,
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
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
        $userProfileId = $this->request->getPost('userProfileId');
        $accountNumber = $this->request->getPost('accountNumber');

        $profileApiUrl = $this->enhancedProfile->getConfigValue(self::PROFILE_API_URL).'/';
        $endUrl = $profileApiUrl.$userProfileId;

        $postFields = '{
                            "profile": {
                                "primaryShippingAccount": "'.$accountNumber.'"
                            }
                        }';
        try {
            $response = $this->enhancedProfile->apiCall('PUT', $endUrl, $postFields);
            $this->enhancedProfile->setProfileSession();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ' Make a Default Shipping Account API is not working: ' . $e->getMessage());
            $response = ["Failure" => "Make a Shipping Account API is not working."];
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
