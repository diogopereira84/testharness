<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Fedex\CustomerCanvas\Model\Config\CanvasConfig;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class CustomerCanvasTokenService
{
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly CanvasConfig $canvasConfig,
        private readonly CustomerCanvasRegistrationService $registrationService,
    ) {}

    /**
     * @param string $storefrontUserId
     * @return string|null
     */
    public function fetchToken(string $storefrontUserId): ?string
    {
        $storefrontId = $this->canvasConfig->getCanvasSToreId();
        $authToken = $this->canvasConfig->getAccessToken();
        $apiUrl = rtrim($this->canvasConfig->getApiUrl(), '/');
        $headers = [
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Bearer ".$authToken
        ];

        try {
            $token = $this->requestToken($storefrontUserId, $storefrontId, $apiUrl, $headers);
            if ($token === null) {
                $userId = $this->registrationService->registerUser($storefrontUserId);
                if($userId != null){
                    $token = $this->requestToken($userId, $storefrontId, $apiUrl, $headers);
                }
            }
            return $token;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .': Token fetch error - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param string $storefrontUserId
     * @param string $storefrontId
     * @param string $apiUrl
     * @param array $headers
     * @return string|null
     */
    private function requestToken(string $storefrontUserId, string $storefrontId, string $apiUrl, array $headers): ?string
    {
        $url = sprintf(
            '%s/storefront-users/token?storefrontUserId=%s&storefrontId=%s',
            $apiUrl,
            urlencode($storefrontUserId),
            urlencode($storefrontId)
        );
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );
        $this->curl->get($url);

        if ($this->curl->getStatus() == 200) {
            $response = $this->curl->getBody();
            return $response ?? null;
        }

        return null;
    }
}
