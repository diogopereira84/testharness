<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\TrackOrder\Model;

use Fedex\TrackOrder\Api\CurlRequestInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\Punchout\Helper\Data as GateTokenHelper;
use Fedex\Header\Helper\Data as HeaderHelper;

class CurlRequest implements CurlRequestInterface
{
    /**
     * Constructor
     *
     * @param Config $config
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param GateTokenHelper $gateTokenHelper
     * @param HeaderHelper $headerHelper
     */
    public function __construct(
        protected Config $config,
        protected Curl $curl,
        protected LoggerInterface $logger,
        protected GateTokenHelper $gateTokenHelper,
        protected HeaderHelper $headerHelper
    ) {
    }

    /**
     * Send a curl request.
     *
     * @param int $orderId
     * @return array
     */
    public function sendRequest(int $orderId): array
    {
        $apiUrl = $this->config->getOrderDetailXapiUrl();
        $accessToken = $this->gateTokenHelper->getTazToken();
        $gateWayToken = $this->gateTokenHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerHelper->getAuthHeaderValue();
        $dataString = json_encode(['orderNo' => $orderId]);

        $headers = [
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "Accept-Language" => "json",
            "Content-Length" => strlen($dataString),
            "Authorization" => $authHeaderVal . $gateWayToken,
            "Cookie" => "Bearer=" . $accessToken,
            "client_id" => $gateWayToken,
        ];
        
        $completeUrl = $apiUrl . '?orderNo=' . $orderId;
        $this->logger->debug(__METHOD__ . ":" . __LINE__ . " headers: ", [$headers]);
        $this->logger->debug(__METHOD__ . ":" . __LINE__ . " Complete URL: " . $completeUrl);
        $this->curl->setHeaders($headers);
        $this->curl->setOptions(
            [
                CURLOPT_SSL_VERIFYPEER => 0
            ]
        );
        $this->curl->get($completeUrl);
        $response = json_decode($this->curl->getBody(), true);
        $this->logger->debug(__METHOD__ . ":" . __LINE__ . " Response from API: ", [$response]);
        if (isset($response['output']['order']) && !empty($response['output']['order'])) {
            
            // Filter the order details
            $filteredOrderDetails = $this->filterOrderDetails($response['output']['order']);

            return [
                'order_id' => $orderId,
                'isValid' => true,
                'order_details' => $filteredOrderDetails
            ];
        } else {
            return [
                'order_id' => $orderId,
                'isValid' => false,
                'error_message' => 'We couldn\'t find your order. Please review and retry. If the problem persists, you may try <a href="' . $this->config->getLegacyTrackOrderUrl() . $orderId . '" target="_blank">legacy tracking</a>.'
            ];
        }
        return json_decode($response, true);
    }

    /**
     * Filter order details to include only the required fields.
     *
     * @param array $orderDetails
     * @return array
     */

    private function filterOrderDetails(array $orderDetails): array
    {
        $requiredFields = [
            'orderNumber',
            'status',
            'totalAmount',
            'submissionTime',
            'productionDueTime',
            'expectedReleaseTime',
            'productDetails',
        ];

        // Filter only the required fields
        return array_intersect_key($orderDetails, array_flip($requiredFields));
    }
}
