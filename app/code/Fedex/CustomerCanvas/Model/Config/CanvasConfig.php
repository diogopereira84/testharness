<?php

declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Config;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Encryption\EncryptorInterface;

class CanvasConfig
{
    public const XML_PATH_API_URL = 'fedex/customer_canvas_api/customer_canvas_api_url';
    public const XML_PATH_STORE_ID = 'fedex/customer_canvas_api/customer_canvas_store_id';
    public const XML_PATH_TENANT_ID = 'fedex/customer_canvas_api/customer_canvas_tenant_id';
    public const XML_PATH_CLIENT_ID = 'fedex/customer_canvas_api/customer_canvas_client_id';
    public const XML_PATH_SECRET_KEY = 'fedex/customer_canvas_api/customer_canvas_client_secret';
    public const XML_PATH_AUTH_URL = 'fedex/customer_canvas_api/customer_canvas_auth_url';

    public function __construct(
        private readonly ToggleConfig       $toggleConfig,
        private readonly Curl               $curl,
        private readonly LoggerInterface    $logger,
        private readonly EncryptorInterface $encryptorInterface
    ) {}

    /**
     * @return string|null
     */
    public function getApiUrl(): string|null
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XML_PATH_API_URL);
    }
    /**
     * @return string|null
     */
    public function getAuthUrl(): string|null
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XML_PATH_AUTH_URL);
    }

    /**
     * @return string|null
     */
    public function getClientId(): string|null
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XML_PATH_CLIENT_ID);
    }

    /**
     * @return string|null
     */
    public function getSecretKey(): string|null
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XML_PATH_SECRET_KEY);
    }
    /**
     * @return string|null
     */
    public function getCanvasStoreId(): string|null
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XML_PATH_STORE_ID);
    }
    /**
     * @return string|null
     */
    public function getCanvasTenantId(): string|null
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XML_PATH_TENANT_ID);
    }

    /**
     * @return mixed|null
     */
    public function getAccessToken(){
        $authUrl = $this->getAuthUrl();
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $params = [
            'client_id' => $this->encryptorInterface->decrypt($this->getClientId()),
            'client_secret' => $this->encryptorInterface->decrypt($this->getSecretKey()),
            'grant_type' => 'client_credentials',
        ];
        $paramString = http_build_query($params);
        $postData    = json_encode($params);
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $paramString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            ]
        );


        try {
            $this->curl->setHeaders($headers);
            $this->curl->post($authUrl, $postData);
            if ($this->curl->getStatus() === 200) {
                $response = json_decode($this->curl->getBody(), true);
                return $response['access_token'] ?? null;
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .'Error fetching access token: ' . $e->getMessage());
        }

        return null;
    }
}
