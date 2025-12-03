<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;

class HttpFedexClient
{
    private const MAX_RETRIES = 3;
    private const INITIAL_BACKOFF = 1; // seconds

    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly ConfigProvider $configProvider
    ) {}

    /**
     * Sends a PUT request with retry logic and exponential backoff.
     *
     * @param string $endpoint
     * @param string $payload
     * @param string $clientId
     * @param array  $telemetry
     * @return bool
     */
    public function putWithRetry(
        string $endpoint,
        string $payload,
        string $clientId,
        array $telemetry = []
    ): bool {
        $delay = self::INITIAL_BACKOFF;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $start = microtime(true);

            try {
                $this->curl->setOptions([
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $this->buildHeaders($clientId),
                    CURLOPT_ENCODING => '',
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_CONNECTTIMEOUT => 10,
                ]);

                $this->curl->post($endpoint, $payload); // Magento's Curl adapter uses POST for data + CUSTOMREQUEST=PUT

                $status = $this->curl->getStatus();
                $responseBody = $this->curl->getBody();
                $latency = round((microtime(true) - $start) * 1000, 2);

                $context = array_merge($telemetry, [
                    'attempt' => $attempt,
                    'status' => $status,
                    'latency_ms' => $latency,
                    'response_snippet' => mb_substr($responseBody, 0, 500),
                    'endpoint' => $endpoint,
                    'PHPSESSID' => $this->getPHPSessionId()
                ]);

                if ($status >= 200 && $status < 300) {
                    $this->logger->info(__METHOD__ . ': ' . __LINE__ . ' Document updated successfully', $context);
                    return true;
                }

                $this->logger->error(__METHOD__ . ':' . __LINE__ . ':Document update failed', $context);

                // Retry only for server-side errors (5xx)
                if ($status >= 500 && $attempt < self::MAX_RETRIES) {
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }

                break;
            } catch (\Throwable $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    sprintf('Exception during PUT attempt %d — %s', $attempt, $e->getMessage()),
                    ['endpoint' => $endpoint, 'PHPSESSID' => $this->getPHPSessionId()]
                );

                if ($attempt < self::MAX_RETRIES) {
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }

                return false;
            }
        }

        return false;
    }

    /**
     * Build headers array for the request.
     */
    private function buildHeaders(string $clientId): array
    {
        return [
            'Content-Type: application/json',
            'Accept: application/json',
            'client_id: ' . $clientId,
        ];
    }

    /**
     * Optionally extracts a transactionId or trace token from the response.
     */
    private function extractTransactionId(string $response): ?string
    {
        $decoded = json_decode($response, true);
        return is_array($decoded) && isset($decoded['transactionId'])
            ? (string)$decoded['transactionId']
            : null;
    }

    /**
     * @return string
     */
    private function getPHPSessionId(): string
    {
        return $this->configProvider->getSessionId();
    }
}
