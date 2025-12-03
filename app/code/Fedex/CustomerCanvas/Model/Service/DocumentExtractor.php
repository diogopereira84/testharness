<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Model\Service;

use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Fedex\CustomerCanvas\Model\ConfigProvider;

class DocumentExtractor
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigProvider $configProvider
    ) {}

    /**
     * Extracts all MAIN_CONTENT document references from the quote.
     *
     * @param Quote $quote
     * @return array
     */
    public function extract(Quote $quote): array
    {
        $documents = [];

        foreach ($quote->getAllItems() as $item) {
            try {
                $product = $item->getProduct();
                if (!$product || !$product->getData('is_customer_canvas')) {
                    continue;
                }

                $option = $item->getOptionByCode('info_buyRequest');
                if (!$option) {
                    continue;
                }

                $decoded = json_decode($option->getValue());
                if (json_last_error() !== JSON_ERROR_NONE || !is_object($decoded)) {
                    $this->logError(
                        sprintf(
                            '%s: JSON decode failed for item %d. Error: %s',
                            __METHOD__,
                            $item->getId(),
                            json_last_error_msg()
                        ),
                        ['item_id' => $item->getId()]
                    );
                    continue;
                }

                $productConfig = $decoded->productConfig ?? null;

                $configuratorStateId = null;
                $integratorProductReference = null;

                if ($productConfig !== null) {
                    $configuratorStateId = $productConfig->configuratorStateId ?? null;
                    $integratorProductReference = $productConfig->integratorProductReference ?? null;
                }

                $externalProd = $decoded->external_prod ?? [];
                if (!is_array($externalProd) || empty($externalProd[0]?->contentAssociations)) {
                    continue;
                }

                foreach ($externalProd[0]->contentAssociations as $assoc) {
                    if (
                        ($assoc->purpose ?? '') === 'MAIN_CONTENT' &&
                        !empty($assoc->contentReference)
                    ) {
                        $documents[] = [
                            'documentId' => $assoc->contentReference,
                            'configuratorStateId' => $configuratorStateId,
                            'integratorProductReference' => $integratorProductReference,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $this->logCritical(
                    sprintf(
                        '%s: Error extracting document for item %d: %s',
                        __METHOD__,
                        $item->getId(),
                        $e->getMessage()
                    ),
                    ['item_id' => $item->getId()]
                );
            }
        }

        return $documents;
    }

    /**
     * Logs an error message with PHP session ID.
     */
    private function logError(string $message, array $context = []): void
    {
        $context['PHPSESSID'] = $this->getPHPSessionId();
        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $message, $context);
    }

    /**
     * Logs a critical message with PHP session ID.
     */
    private function logCritical(string $message, array $context = []): void
    {
        $context['PHPSESSID'] = $this->getPHPSessionId();
        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $message, $context);
    }

    /**
     * Safely retrieves the current PHP session ID.
     */
    public function getPHPSessionId(): string
    {
        return $this->configProvider->getSessionId();
    }
}
