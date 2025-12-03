<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FujitsuReceipt\Model\TransactionReceiptApi;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Header\Helper\Data as HeaderData;

/**
 * FujitsuTransactionReceiptApiHandler Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class FujitsuTransactionReceiptApiHandler
{
    /**
     * constant for get Fujitsu Receipt Api Url
     */
    public const GET_FUJITSU_RECEIPT_API_URL = "fedex/fujitsu_receipt/fujitsu_receipt_api_url";
    public const PRICING_STORE = "9890";
    public const RECEIPT_FORMAT = "INVOICE_EIGHT_BY_ELEVEN";

    /**
     * Toggle constant for creating order before payment
     */
    public const TOKEN = 'token';

    /**
     * FujitsuTransactionReceiptApiHandler constructor
     *
     * @param ScopeConfigInterface $configInterface
     * @param DeliveryHelper $deliveryHelper
     * @param PunchoutHelper $punchoutHelper
     * @param SubmitOrderHelper $submitOrderHelper
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param HeaderData $headerData
     */
    public function __construct(
        private ScopeConfigInterface $configInterface,
        private DeliveryHelper $deliveryHelper,
        private PunchoutHelper $punchoutHelper,
        private SubmitOrderHelper $submitOrderHelper,
        private LoggerInterface $logger,
        private Curl $curl,
        protected HeaderData $headerData
    )
    {
    }

    /**
     * Get config value
     *
     * @param string $config
     * @return mixed
     */
    public function getConfigValue($config)
    {
        return $this->configInterface->getValue($config);
    }

    /**
     * Get Headers for curl request
     *
     * @param null|array $tokenStr
     * @return string|[]
     */
    public function getHeaders(?array $tokenStr)
    {
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            $authHeaderVal . $gateWayToken
        ];

        if (isset($tokenStr[self::TOKEN]) && $tokenStr[self::TOKEN]) {
            $headers[] = "Cookie: Bearer=" . $tokenStr[self::TOKEN];
        } else {
            $headers = $this->submitOrderHelper->getCustomerOnBehalfOf($headers);
        }

        return $headers;
    }

    /**
     * Curl Post Data
     *
     * @param string|array
     */
    public function callCurlPost(string $transactionReceiptRequestData)
    {
        $accessToken = $this->deliveryHelper->getApiToken();
        $setupURL = $this->getConfigValue(self::GET_FUJITSU_RECEIPT_API_URL);
        $headers = $this->getHeaders($accessToken);

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $transactionReceiptRequestData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );

        try {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . "Transaction Receipt API call request initiated");
            $this->logger->info(__METHOD__ . ':' . __LINE__ . "Request: " . $transactionReceiptRequestData);

            $this->curl->post($setupURL, $transactionReceiptRequestData);
            $response = $this->curl->getBody();
            $responseData = '';
            if ($response) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . "Transaction Receipt API call successful");
                $this->logger->info(__METHOD__ . ':' . __LINE__ . "Response: ". $response);
                $responseData = json_decode($response, true);
            }
            if ($responseData && !empty($responseData['error']) || !empty($responseData['errors'])) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . ' Fujitsu transaction receipt API Request: '
                    . $transactionReceiptRequestData
                );
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . ' Fujitsu transaction receipt API response: '. $response
                );
            }

            return $responseData;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Exception occurred while pulling Fujitsu Receipt: ' . $e->getMessage());
        }
    }

    /**
     * Prepare Fujitsu Receipt Api Request Data
     *
     * @param array $orderData
     * @return string
     */
    public function prepareFujitsuReceiptApiRequestData($orderData)
    {
        $transactionId = $orderData["retail_transaction_id"];
        $firstName = $orderData['customer_first_name'];
        $lastName = $orderData['customer_last_name'];
        $emailAddress = $orderData['customer_email'];

        $fujitsuReceiptRequestData = [
            "transactionReceiptRequest" => [
                "transactionId" => $transactionId,
                "pricingStore" => self::PRICING_STORE,
                "transactionReceiptDetails" => [
                    "receiptType" => "EMAIL",
                    "receiptFormat" => self::RECEIPT_FORMAT
                ],
                "contact" => [
                    "personName" => [
                        "firstName" => $firstName,
                        "lastName" => $lastName
                    ],
                    "emailDetail" => [
                        "emailAddress" => $emailAddress
                    ]
                ]
            ]
        ];

        return json_encode($fujitsuReceiptRequestData);
    }
}
