<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Fedex\CustomerCanvas\Model\Config\CanvasConfig;
use Magento\Customer\Model\Session;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class CustomerCanvasRegistrationService
{
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly CanvasConfig $canvasConfig,
        protected Session $customerSession
    ) {}

    /**
     * @param string $storefrontUserId
     * @return string|null
     */
    public function registerUser(string $storefrontUserId): ?string
    {
        $url = $this->buildRegistrationUrl();
        $payload = $this->buildPayload($storefrontUserId);
        $accessToken = $this->canvasConfig->getAccessToken();

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer ".$accessToken
        ];

        try {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                    CURLOPT_POSTFIELDS => $payload
                ]
            );
            $this->curl->post($url, $payload);
            $statusCode = $this->curl->getStatus();
            $response = $this->curl->getBody();

            if ($statusCode === 201) {
                return $this->extractUserId($response);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .' error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return string
     */
    private function buildRegistrationUrl(): string
    {
        return rtrim($this->canvasConfig->getApiUrl(), '/') .
            '/storefront-users?storefrontId=' .
            $this->canvasConfig->getCanvasStoreId();
    }

    /**
     * @param string $storefrontUserId
     * @return string
     */
    private function buildPayload(string $storefrontUserId): string
    {
        $isAnonymous = !$this->customerSession->isLoggedIn();
        return json_encode([
            'storefrontUserId' => $storefrontUserId,
            'isAnonymous' => $isAnonymous
        ]);
    }

    /**
     * @param string $body
     * @return string|null
     */
    private function extractUserId(string $body): ?string
    {
        $data = json_decode($body, true);
        return $data['userId'] ?? null;
    }

}
