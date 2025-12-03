<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Model;

use Fedex\Mars\Api\ClientInterface;
use Fedex\Mars\Model\Cache\Type\CacheType;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 *  Client Model
 */
class Client implements ClientInterface
{
    /**
     * @param Config $moduleConfig
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param CacheInterface $cache
     * @param Token $token
     * @param Json $serializer
     */
    public function __construct(
        private Config          $moduleConfig,
        private LoggerInterface $logger,
        private Curl            $curl,
        private CacheInterface  $cache,
        private Token           $token,
        private Json            $serializer
    )
    {
    }

    /**
     * Send order json
     *
     * @param array $dataJson
     * @param int $id
     * @return void
     * @throws LocalizedException
     */
    public function sendJson($dataJson, int $id): void
    {
        $cacheKey = CacheType::TYPE_IDENTIFIER;
        $cacheTag = CacheType::CACHE_TAG;
        $token = $this->cache->load($cacheKey);

        if ($token == "") {
            $clientId = $this->moduleConfig->getClientId();
            $secret = $this->moduleConfig->getSecret();
            $resource = $this->moduleConfig->getResource();
            $grantType = $this->moduleConfig->getGrantType();
            $apiURL = $this->moduleConfig->getTokenApiUrl();
            $response = $this->token->getToken(
                $clientId,
                $secret,
                $resource,
                $grantType,
                $apiURL,
                $this->moduleConfig->getMaxRetries()
            );
            $token = $response['access_token'];
            $cacheLifeTime = $response['expires_in'] - 300;
            $this->cache->save(
                $token,
                $cacheKey,
                [$cacheTag],
                $cacheLifeTime
            );
        }

        $dataJson = $this->serializer->serialize($dataJson[0]);
        if ($this->moduleConfig->isLoggingEnabled()) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . __(':MARS - json content') . ':' . $dataJson);
        }

        $response = $this->push($dataJson, $token);
        $this->logger->info(sprintf(
            __METHOD__ . ':' . __LINE__ . ' order "%d" successfully sent to Mars',
            $id
        ));
        $this->logger->info((string)$response);
    }

    /**
     * Push json
     *
     * @param string $dataJson
     * @param string $token
     * @return void|null
     * @throws LocalizedException
     */
    protected function push(string $dataJson, string $token)
    {
        $successCodes = $this->moduleConfig->getSuccessCodes() ?? '100';
        $successCodesArr = explode(',', $successCodes);
        $apiURL = $this->moduleConfig->getApiUrl();

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json;type=entry;charset=utf=8",
            "Connection: Keep-Alive"
        ];
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            ]
        );

        $this->curl->post($apiURL, $dataJson);
        $status = $this->curl->getStatus();

        if (!in_array($status, $successCodesArr)) {
            $message = __(':MARS -  Could not send order to Mars. Response status code: ' . $status);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . $message);
            throw new LocalizedException(__($message));
        } else {
            $message = __(':MARS - send order to Mars. Response status code: ' . $status);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . $message);
        }
    }
}
