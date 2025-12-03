<?php

namespace Fedex\Mars\Model;

use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

/**
 * Token model
 */
class Token
{
    /**
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Curl            $curl,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @param string $clientId
     * @param string $secret
     * @param string $resource
     * @param string $grantType
     * @param string $apiURL
     * @param int $maxRetries
     * @return mixed
     * @throws ConfigurationMismatchException
     * @throws LocalizedException
     */
    public function getToken(
        string $clientId,
        string  $secret,
        string  $resource,
        string  $grantType,
        string  $apiURL,
        int $maxRetries
    ): mixed {

        if (!$secret || !$resource || !$grantType || !$clientId || !$apiURL) {
            throw new ConfigurationMismatchException(__('Missing Mars Token Configuration!'));
        }
        $headers = [
            "Content-Type: application/x-www-form-urlencoded",
            "Connection: Keep-Alive",
            "Keep-Alive: 300"
        ];

        $params = [
            'client_id' => $clientId,
            'client_secret' => $secret,
            'resource' => $resource,
            'grant_type' => $grantType,
        ];

        $paramString = http_build_query($params);
        $postData = json_encode($params);
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $paramString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            ]
        );

        $requestCounter = 0;
        do {
            if ($requestCounter == $maxRetries) {
                $message = __(' No response from token API');
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . $message);
                throw new LocalizedException($message);
            }
            $requestCounter++;
            $this->curl->post($apiURL, $postData);
            $response = json_decode($this->curl->getBody(), true);
            if (isset($response['error'])) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $response['error']);
                if (isset($response['error_description'])) {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $response['error_description']);
                }
            }
            $gatewayToken = $response['access_token'] ?? null;
        } while (!$gatewayToken);
        return $response;
    }
}
