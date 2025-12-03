<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Fedex\CustomerCanvas\Model\Config\CanvasConfig;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\Service\StoreFrontUserIdService;

class CustomerCanvasUserInfo
{
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly CanvasConfig $canvasConfig,
        private readonly StoreFrontUserIdService $storeFrontUserIdService,
    ) {}

    /**
     * Fetch CustomerCanvas user info by storefrontUserId
     *
     * @param string $storefrontUserId
     * @return array|null
     */
    public function getUserInfo($merge=false): ?array
    {
        $storefrontUserId = $this->storeFrontUserIdService->getUserTokenFromSession($merge)['userId'] ?? null;

        if (empty($storefrontUserId)) {
            return null;
        }

        try {
            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $this->buildHeaders(),
                    CURLOPT_ENCODING => ''
                ]
            );
            $this->curl->get($this->buildUserInfoUrl($storefrontUserId));

            if ($this->curl->getStatus() === 200) {
                return $this->parseUserInfo($this->curl->getBody());
            }
        } catch (\Throwable $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param string $storefrontUserId
     * @return string
     */
    private function buildUserInfoUrl(string $storefrontUserId): string
    {
        return rtrim($this->canvasConfig->getApiUrl(), '/') .
            '/storefront-users?storefrontId=' .
            $this->canvasConfig->getCanvasStoreId() .
            '&storefrontUserId=' . urlencode($storefrontUserId);
    }

    /**
     * @return string[]
     */
    private function buildHeaders(): array
    {
        return [
            'Authorization: Bearer ' . $this->canvasConfig->getAccessToken(),
            'Content-Type: application/json'
        ];
    }

    /**
     * @param string $body
     * @return array|null
     */
    private function parseUserInfo(string $body): ?array
    {
        $dataArray = null;
        if (is_array($decoded = json_decode($body, true)) && isset($decoded['items'][0])) {
            $dataArray = $decoded['items'][0];
        }
        return $dataArray;
    }
}
