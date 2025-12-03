<?php

declare(strict_types=1);
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Rate\Helper;

use Fedex\CoreApi\Client\AbstractApiClient;
use Fedex\Delivery\Helper\Data as DeliveryData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Rate\Api\Data\ConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Psr\Log\LoggerInterface;
use Fedex\Header\Helper\Data;

class ApiRequest extends AbstractHelper
{
    /**
     * @var AbstractApiClientInterface $apiClient
     */
    protected $apiClient;

    /**
     * ApiRequest construct.
     *
     * @param Context $context
     * @param ConfigInterface $configInterface
     * @param DeliveryData $helper
     * @param CustomerSession $customerSession
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerInterface $logger
     * @param AbstractApiClientInterface $apiClient
     * @param Json $json
     * @param JsonValidator $jsonValidator
     * @param Curl $curl
     * @param Data $data
     */
    public function __construct(
        Context $context,
        protected ConfigInterface $configInterface,
        protected DeliveryData $helper,
        protected CustomerSession $customerSession,
        protected PunchoutHelper $punchoutHelper,
        protected LoggerInterface $logger,
        AbstractApiClient $apiClient,
        private Json $json,
        private JsonValidator $jsonValidator,
        protected Curl $curl,
        protected Data $data
    ) {
        $this->apiClient = $apiClient;
        $this->apiClient->domain = '';
        parent::__construct($context);
    }

    /**
     * Price product api.
     *
     * @param string $payload
     * @return array $repsonse
     */
    public function priceProductApi($payload)
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Rate API Request started for upfront pricing');
        $authHeaderVal = $this->data->getAuthHeaderValue();
        $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Auth Header value: '.$authHeaderVal);

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            "Content-Length: " . strlen($payload),
            $authHeaderVal . $this->getGateToken()
        ];

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );

        $this->curl->post($this->configInterface->getRateApiUrl(), $payload);

        $pResult = $this->curl->getBody();

        if ($pResult && (strpos($pResult, 'errors') !== false || strpos($pResult, 'output') === false)) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Price API Rate Request:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $payload);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Price API Rate response:');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $pResult);
        }

        $response = [];
        if ($this->jsonValidator->isValid($pResult)) {
            $pResultArray = $this->json->unserialize($pResult);

            if (!empty($pResultArray)) {
                $response['response'] = $pResultArray;
                $response['status'] = !array_key_exists('errors', $pResultArray);
            }
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error no data returned from Price API Rate Request.');
            $response['errors'][] = 'Error found no data';
            $response['status'] = false;
        }

        return $response;
    }

    /**
     * Set headers data.
     *
     * @param string $payload
     */
    protected function setHeaders(string $payload): void
    {
        $authHeaderVal = $this->data->getAuthHeaderValue();
        $this->apiClient->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'json',
            'Content-Length' => strlen($payload),
            $authHeaderVal . $this->getGateToken(),
        ];
    }

    /**
     * Get gateway token
     *
     * @return string
     */
    private function getGateToken()
    {
        return $this->punchoutHelper->getAuthGatewayToken();
    }
}
