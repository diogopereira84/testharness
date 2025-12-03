<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Model;

use Exception;
use Fedex\FXOApi\Helper\ApiTokenHelper;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class GetApiCallBuilder
{
    /**
     * @param Curl $curl
     * @param ApiTokenHelper $apiTokenHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Curl $curl,
        protected ApiTokenHelper $apiTokenHelper,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Build generic FXO get api call
     *
     * @param string $uri
     * @param string $gateway
     * @return mixed
     */
    public function buildGetApiCall(string $uri, string $gateway): mixed
    {
        $apiCallOutput = null;

        try {
            $this->buildGetApiOptions($gateway);

            $this->curl->get($uri);
            $apiCallOutput = json_decode($this->curl->getBody(), true);
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . 'Error while building FXO get api call: ' . $e->getMessage()
            );
        }

        return $apiCallOutput;
    }

    /**
     * Build generic FXO get api call options
     *
     * @param string $gateway
     * @return void
     */
    public function buildGetApiOptions($gateway): void
    {
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => $this->buildGetApiHeader($gateway)
            ]
        );
    }

    /**
     * Build generic FXO get api call header
     *
     * @param string $gateway
     * @return array
     */
    public function buildGetApiHeader($gateway): array
    {
        $getApiHeader = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Accept-Language: json',
            $this->apiTokenHelper->getTazToken(),
            $this->apiTokenHelper->getGatewayToken($gateway)
        ];

        return $getApiHeader;
    }
}
