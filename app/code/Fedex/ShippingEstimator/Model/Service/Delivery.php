<?php

namespace Fedex\ShippingEstimator\Model\Service;

use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\ShippingEstimator\Helper\Data;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\CoreApi\Client\AbstractApiClient;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Delivery
{
    const HAS_ERROR = 'hasError';
    const MESSAGE = 'message';
    const OUTPUT = 'output';

    /**
     * @var AbstractApiClientInterface
     */
    private $apiClient;

    /**
     * Delivery constructor.
     * @param Data $helper
     * @param AbstractApiClient $apiClient
     * @param PunchoutHelper $punchoutHelper
     * @param LoggerInterface $logger
     * @param HeaderData $headerData
     * @param Curl $curl
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private Data $helper,
        AbstractApiClient $apiClient,
        private PunchoutHelper $punchoutHelper,
        private LoggerInterface $logger,
        private HeaderData $headerData,
        private Curl $curl,
        private ToggleConfig $toggleConfig
    ) {
        $this->apiClient = $apiClient;
        $this->apiClient->domain = '';
    }

    /**
     * @param $region_id
     * @param $postcode
     * @param $products
     * @return array
     */
    public function getDeliveryInfo($params)
    {
        $response = [];
        $setupURL = $this->helper->getdeliveryApiUrl();
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $payload = $this->helper->createRequestPayload($params);
        $payload = json_encode($payload);

        if ($this->toggleConfig->getToggleConfigValue('explorers_D169823_fix')) {
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($payload),
                $authHeaderVal . $gatewayToken
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

            $this->curl->post($setupURL, $payload);
            $result = $this->curl->getBody();
        } else {
            $this->apiClient->headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                $authHeaderVal . $gatewayToken
            ];
            $result = $this->apiClient->execute($setupURL, RestRequest::METHOD_POST, $payload, []);
        }

        if ($result) {
            $validatedResult = $this->validateResult($result);

            if ($validatedResult[self::HAS_ERROR]) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__ . ' Error occured while getting delivery information.'
                );
                $response['response'] = $validatedResult;
                $response['status'] = false;

                return $response;
            }
        }

        $pResultArray = json_decode($result, true);

        $shippingInfo = $this->helper->formatResult($pResultArray);
        $info = [
            [
                'label' => $this->helper->getCheapestDeliveryLabel(),
                'methods' => $shippingInfo['cheapest_delivery']
            ]
        ];
        if (!empty($shippingInfo['fastest_delivery'])) {
            $fastInfo =
                [
                    'label' => $this->helper->getFastestDeliveryLabel(),
                    'methods' => $shippingInfo['fastest_delivery']
                ];
            array_push($info, $fastInfo);
        }
        $response['response'] = ['data' => $info,
            self::HAS_ERROR => false,
            self::MESSAGE => ''
        ];
        $response['status'] = true;

        return $response;
    }

    /**
     * @param string $serializedResult
     *
     * @return array
     */
    protected function validateResult($serializedResult)
    {
        $result = json_decode($serializedResult, true);
        $response = [
            self::HAS_ERROR => false,
            'data' => '',
            self::MESSAGE => null
        ];

        if (strpos($serializedResult, 'errors') === true || strpos($serializedResult, self::OUTPUT) === false) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $serializedResult);
            $response = [
                self::HAS_ERROR => true,
                self::MESSAGE => $result['errors']
            ];
        }

        if (empty($result)) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error: found no data');
            $response = [
                self::HAS_ERROR => true,
                self::MESSAGE => 'Error: found no data'
            ];
        }

        if (array_key_exists(self::OUTPUT, $result) && empty($result[self::OUTPUT]['deliveryOptions'])) {
            $this->logger->info(__METHOD__.':'.__LINE__.':Endpoint deliveryoptions api returned empty options:');

            $response = [
                self::HAS_ERROR => true,
                self::MESSAGE => 'Error: API returned no delivery options'
            ];
        }

        if ($response[self::HAS_ERROR] === true) {
            $response['data'] = '';
            $this->logger->info(__METHOD__.':'.__LINE__.':response: ' . $serializedResult);
        }

        return $response;
    }
}
