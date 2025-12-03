<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Fedex\FXOCMConfigurator\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class DocumentVendorOwnerUpdater
{
    public const XML_PATH_DOC_OWNER_URL = 'fedex/general/fxocm_doc_vendor_owner_update';

    public function __construct(
        private readonly DocumentExtractor $extractor,
        private readonly HttpFedexClient $client,
        private readonly LoggerInterface $logger,
        private readonly Data $helper,
        private readonly ScopeConfigInterface $config
    ) {}

    /**
     * Updates vendorOwnerId for all MAIN_CONTENT documents in the given quote.
     *
     * @param Quote  $quote
     * @param string $vendorOwnerId
     * @return bool
     * @throws LocalizedException
     */
    public function updateVendorOwnerId(Quote $quote, string $vendorOwnerId): bool
    {
        $documents = $this->extractDocuments($quote);
        $vendorOwnerId = $this->sanitizeVendorOwnerId($vendorOwnerId);
        $baseUrl = $this->getBaseUrl();
        $authToken = $this->getAuthToken();

        foreach ($documents as $index => $doc) {
            if (!is_array($doc) || empty($doc['documentId'])) {
                $this->logError(
                    sprintf('Skipped invalid document entry at index %d — missing documentId or incorrect format.', $index),
                    ['document' => $doc]
                );
                continue;
            }

            try {
                $this->processDocument($doc, $baseUrl, $authToken, $vendorOwnerId);
            } catch (\Throwable $e) {
                $this->logError(
                    sprintf('Failed processing document %s: %s', $doc['documentId'], $e->getMessage()),
                    ['exception' => $e]
                );
                throw $e;
            }
        }

        return true;
    }

    /**
     * Extracts documents from quote or throws an exception if none found.
     *
     * @param Quote $quote
     * @return array
     * @throws LocalizedException
     */
    private function extractDocuments(Quote $quote): array
    {
        $documents = $this->extractor->extract($quote);

        if (empty($documents)) {
            $this->logError('No MAIN_CONTENT documents found in quote.');
            throw new LocalizedException(__('No MAIN_CONTENT documents found in quote.'));
        }

        return $documents;
    }

    /**
     * Handles vendorOwnerId update for a single document.
     *
     * @param array $document
     * @param string $baseUrl
     * @param string $authToken
     * @param string $vendorOwnerId
     * @throws LocalizedException
     */
    private function processDocument(array $document, string $baseUrl, string $authToken, string $vendorOwnerId): void
    {
        $documentId = $document['documentId'] ?? null;
        if (empty($documentId)) {
            $this->logError('Missing documentId in document payload.');
            throw new LocalizedException(__('Missing documentId in document payload.'));
        }

        $endpoint = sprintf('%s/%s/vendorownerid', $baseUrl, $documentId);
        $payload = sprintf('"%s"', addslashes($vendorOwnerId));

        $telemetry = [
            'documentId' => $documentId,
            'vendorOwnerId' => $vendorOwnerId,
        ];

        $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' Processing document', $telemetry);

        $success = $this->client->putWithRetry($endpoint, $payload, $authToken, $telemetry);

        if (!$success) {
            $message = sprintf(
                'Failed to update vendorOwnerId for document %s after retry attempts.',
                $documentId
            );
            $this->logError($message, $telemetry);
            throw new LocalizedException(__($message));
        }
        if ($success) {
            $this->logger->info(
                __METHOD__ . ' Document details that were updated',
                ['document' => $document]
            );
        }
    }

    /**
     * Returns trimmed vendorOwnerId string.
     */
    private function sanitizeVendorOwnerId(string $vendorOwnerId): string
    {
        return trim($vendorOwnerId);
    }

    /**
     * Retrieves and validates FedEx Document Service base URL.
     */
    private function getBaseUrl(): string
    {
        $baseUrl = trim((string) $this->config->getValue(self::XML_PATH_DOC_OWNER_URL));

        if (empty($baseUrl)) {
            $this->logError('Missing configuration for FedEx Document Service URL.');
            throw new LocalizedException(__('FedEx Document Service URL is not configured.'));
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Retrieves the FedEx authentication token.
     */
    private function getAuthToken(): string
    {
        $authToken = trim((string) $this->helper->getFxoCMClientId());

        if (empty($authToken)) {
            $this->logError('Missing FedEx authentication token.');
            throw new LocalizedException(__('FedEx authentication token is not available.'));
        }

        return $authToken;
    }

    /**
     * Logs an error message with optional context.
     */
    private function logError(string $message, array $context = []): void
    {
        $context['PHPSESSID'] = $this->extractor->getPHPSessionId();
        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $message, $context);
    }
}
