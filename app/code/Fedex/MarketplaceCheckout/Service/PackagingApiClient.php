<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Service;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class PackagingApiClient
{
    private const CONTENT_TYPE_JSON = 'application/json';

    public function __construct(
        private readonly Curl $curlClient,
        private readonly Json $jsonSerializer,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param array $items
     * @param ShopInterface $seller
     * @return array
     */
    public function requestPackaging(array $items, ShopInterface $seller): array
    {
        if (empty($items)) {
            $this->logger->warning(__METHOD__ . ': Packaging API skipped - empty items array.');
            return [];
        }

        $endpoint = $seller->getSellerPackageApiEndpoint();
        if (!$endpoint) {
            $this->logger->warning(__METHOD__ . ': Packaging API skipped - missing endpoint for seller.');
            return [];
        }

        try {
            $payload = $this->jsonSerializer->serialize(['items' => $items]);
            $this->logPayload($payload);

            $this->prepareRequest();
            $this->curlClient->post($endpoint, $payload);

            $responseBody = $this->curlClient->getBody();
            $this->logResponse($responseBody);

            if ($this->isSuccessful($this->curlClient->getStatus())) {
                return $this->jsonSerializer->unserialize($responseBody);
            }
        } catch (\Throwable $e) {
            $this->logger->error(__METHOD__ . ': API request failed.', ['exception' => $e]);
        }

        return [];
    }

    /**
     * @return void
     */
    private function prepareRequest(): void
    {
        $this->curlClient->setOptions([
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . self::CONTENT_TYPE_JSON,
                'Accept: ' . self::CONTENT_TYPE_JSON,
                'Accept-Language: en',
            ],
        ]);
    }

    /**
     * @param int $status
     * @return bool
     */
    private function isSuccessful(int $status): bool
    {
        return $status >= 200 && $status < 300;
    }

    /**
     * @param string $payload
     * @return void
     */
    private function logPayload(string $payload): void
    {
        $this->logger->info(__METHOD__ . ': Sending packaging payload.', ['payload' => $payload]);
    }

    /**
     * @param string $response
     * @return void
     */
    private function logResponse(string $response): void
    {
        $this->logger->info(__METHOD__ . ': Received packaging response.', ['response' => $response]);
    }
}
