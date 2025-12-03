<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\App\RequestInterface;

class PreferredDeliveryMethod implements ActionInterface
{

    public const PROFILE_API_URL = "sso/general/profile_api_url";

    /**
     * Initialize dependencies.
     *
     * @param Curl $curl
     * @param JsonFactory $jsonFactory
     * @param EnhancedProfile $enhancedProfile
     * @param LoggerInterface $logger
     * @param PunchoutHelper $punchoutHelper
     * @param Session $customerSession
     * @param HeaderData $headerData
     * @param RequestInterface $request
     */
    public function __construct(
        protected Curl $curl,
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        private PunchoutHelper $punchoutHelper,
        protected Session $customerSession,
        protected HeaderData $headerData,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Set preferred delivery method
     *
     * @return json
     */
    public function execute()
    {
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__ . ' Line: ';
        $customerEmail = null;
        if ($this->customerSession->getCustomer() !== null) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
        }
        $userProfileId = $this->request->getPost('userProfileId');
        $method = $this->request->getPost('method');
        $preferredStore = $this->request->getPost('preferredStore');
        $profileApiUrl = $this->enhancedProfile->getConfigValue(self::PROFILE_API_URL).'/';
        $endUrl = $profileApiUrl.$userProfileId;

        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $tazToken = $this->punchoutHelper->getTazToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "X-clientid: ISHP",
            $authHeaderVal . $gatewayToken ,
            "Cookie: Bearer=" . $tazToken
        ];

        $this->logger->info($logHeader . __LINE__ . ' Preferred Delivery Method Headers for customer :'
        . $customerEmail . var_export($headers, true));

        $postFields = '{
            "profile": {
                "userProfileId": "'.$userProfileId.'",
                "delivery": {
                    "preferredDeliveryMethod": "'.$method.'",
                    "preferredStore": "'.$preferredStore.'"
                }
            }
        }';

        $this->logger->info($logHeader .__LINE__ . ' Preferred Delivery Method Post Fields for customer :'
        . $customerEmail . var_export($postFields, true));

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            ]
        );
        try {
            $this->curl->post($endUrl, $postFields);
            $output = $this->curl->getBody();
            $response = json_decode($output);
            $this->logger->info($logHeader . __LINE__ . ' Preferred Delivery Method Response for customer :'
            . $customerEmail . var_export($output, true));
            $this->enhancedProfile->setProfileSession();
        } catch (\Exception $e) {
            $this->logger->critical($logHeader . __LINE__ . ' Preferred API is not working for customer : '
            . $customerEmail . $e->getMessage());
            $response = ["Failure" => "Preferred Delivery Method API is not working."];
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
