<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Fedex\Header\Helper\Data as HeaderData;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\EnhancedProfile;
use Psr\Log\LoggerInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\App\RequestInterface;

class PreferredPaymentMethod implements ActionInterface
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
     * @param HeaderData  $headerData
     * @param RequestInterface $request
     */
    public function __construct(
        protected Curl $curl,
        protected JsonFactory $jsonFactory,
        protected EnhancedProfile $enhancedProfile,
        protected LoggerInterface $logger,
        private PunchoutHelper $punchoutHelper,
        protected HeaderData $headerData,
        protected RequestInterface $request
    )
    {
    }

    /**
     * Set preferred payment method
     *
     * @return json
     */
    public function execute()
    {
        $userProfileId = $this->request->getPost('userProfileId');
        $paymentMethod = $this->request->getPost('paymentMethod');

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
            $authHeaderVal . $gatewayToken,
            "Cookie: Bearer=" . $tazToken
        ];

        $postFields = '{
                            "profile": {
                                "userProfileId": "'.$userProfileId.'",
                                "payment": {
                                    "preferredPaymentMethod": "'.$paymentMethod.'"
                                }
                            }
                        }';

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
            $response = json_decode($this->curl->getBody());
            $this->enhancedProfile->setProfileSession();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Preferred API is not working: ' . $e->getMessage());
            $response = ["Failure" => "Preferred Payment Method API is not working."];
        }
        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
