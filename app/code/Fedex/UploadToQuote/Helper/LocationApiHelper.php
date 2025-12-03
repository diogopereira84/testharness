<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Header\Helper\Data as HeaderData;

/**
 * LocationApiHelper class for location search
 */
class LocationApiHelper extends AbstractHelper
{
    public $formData;

    /**
     * LocationApiHelper Constructor
     *
     * @param Context $context
     * @param HeaderData $headerData
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerInterface $logger
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        protected HeaderData $headerData,
        protected PunchoutHelper $punchoutHelper,
        protected LoggerInterface $logger,
        protected Curl $curl
    ) {
        parent::__construct($context);
    }

    /**
     * Get hub center code by state.
     *
     * @param array $postData
     * @param string $setupURL
     * @return array
     */
    public function getHubCenterCodeByState($postData, $setupURL)
    {
        try {
            $authenticationDetails = $this->getAuthenticationDetails();
            if (!$authenticationDetails['gateWayToken'] || !$authenticationDetails['accessToken']) {
                $response = [
                    "errors" => [
                        [
                            "code" => "Token_Error",
                            "message" => "Some error occured in generating token"
                        ]
                    ]
                ];

                return $response;
            }
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $dataString = $this->prepareData($postData);
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($dataString),
                $authHeaderVal . $authenticationDetails['gateWayToken'],
                "Cookie: Bearer=" . $authenticationDetails['accessToken']
            ];

            $response  = $this->callLocationSearchApi($setupURL, $headers, $dataString);

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' LOCATION API ERROR:');
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $response = [
                "errors" => [
                    [
                        "code" => "LOCATION_API_Error",
                        "message" => "System error. Please Try Again"
                    ]
                ]
            ];
        }

        return $response;
    }

    /**
     * Get authentication details
     *
     * @return array
     */
    public function getAuthenticationDetails()
    {
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $accessToken = $this->punchoutHelper->getTazToken();

        return [
            'gateWayToken' => $gateWayToken,
            'accessToken' => $accessToken
        ];
    }

    /**
     * Call Location Search Api
     *
     * @param string $setupURL
     * @param array $headers
     * @param string $dataString
     * @return array
     */
    public function callLocationSearchApi($setupURL, $headers, $dataString)
    {
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
                CURLOPT_POSTFIELDS => $dataString
            ]
        );

        $this->curl->post($setupURL, $dataString);
        $output = $this->curl->getBody();
        $response = json_decode($output, true);

        return $response;
    }

    /**
     * To prepare Location API request data.
     *
     * @param array $postData
     * @return string
     */
    public function prepareData($postData)
    {
        $dataArr = [
            'locationSearchRequest' => [
                'address' => [
                    "streetLines" => null,
                    "city" => null,
                    "stateOrProvinceCode" => $postData['stateCode'] ?? '',
                    "postalCode" => null,
                    "countryCode" => $postData['countryCode'] ?? '',
                    "addressClassification" => "BUSINESS"
                ],
                'include' => [
                    "printHubOnly" => true
                ]
            ]
        ];

        return json_encode($dataArr);
    }
}
