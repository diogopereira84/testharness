<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\GraphQl\Service\CheckLogEnabledForMutation;
use Magento\Framework\Webapi\Rest\Request;
use Magento\NewRelicReporting\Model\NewRelicWrapper;
use Psr\Log\LoggerInterface;

class NewRelicHeaders
{
    /**
     * @param NewRelicWrapper $newRelicWrapper
     * @param Request $request
     * @param CheckLogEnabledForMutation $checkLogEnabledForMutation
     * @param LoggerInterface $logger
     * @param InstoreConfig $instoreConfig
     */
    public function __construct(
        private readonly NewRelicWrapper $newRelicWrapper,
        private readonly Request $request,
        private readonly CheckLogEnabledForMutation $checkLogEnabledForMutation,
        private readonly LoggerInterface $logger,
        private readonly InstoreConfig $instoreConfig
    ) {
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $headerArray = [];
        if ($this->instoreConfig->isLoggingToNewrelicEnabled()) {
            $headerArray = $this->addCustomParamToNewrelicLogs();
        }
        return $headerArray;
    }

    /**
     * @param string $mutationName
     * @return array
     */
    public function getHeadersForMutation(string $mutationName = ''): array
    {
        $headerArray = [];
        if ($this->instoreConfig->isLoggingToNewrelicEnabled()
            && $this->checkLogEnabledForMutation->execute($mutationName)) {
            $headerArray = $this->addCustomParamToNewrelicLogs();
        }
        return $headerArray;
    }

    /**
     * @return array
     */
    private function addCustomParamToNewrelicLogs(): array
    {
        $headerArray = [];
        try {
            $headers = $this->request->getHeaders();
            if ($headers) {
                foreach ($headers as $header) {
                    $headerKey = explode(": ", $header->toString());
                    if ($this->checkIfHeaderKeyAvailable($headerKey)) {
                        $headerArray[$headerKey[0]] = $headerKey[1];
                        $this->newRelicWrapper->addCustomParameter($headerKey[0], $headerKey[1]);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error("Unable to log to Newrelic: " . $exception->getMessage());
        }
        return $headerArray;
    }

    /**
     * @param array $headerKey
     * @return bool
     */
    private function checkIfHeaderKeyAvailable($headerKey): bool
    {
        $fuseHeaders = $this->instoreConfig->headersLoggedToNewrelic();
        if ($fuseHeaders && $headerKey && isset($headerKey[0]) && isset($headerKey[1]) && in_array(strtolower($headerKey[0]), $fuseHeaders)) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function addSpanIdToNewrelicLogsForGetAllQuotes(): array
    {
        $headerId = [];
        try {
            $headers = $this->request->getHeaders();
            if ($headers) {
                foreach ($headers as $header) {
                    $headerKey = explode(": ", $header->toString());
                    if ($headerKey && isset($headerKey[0]) && strtolower($headerKey[0]) == 'x-parent-span-id') {
                        $headerId['x-parent-span-id'] = $headerKey[1];
                        $this->newRelicWrapper->addCustomParameter($headerKey[0], $headerKey[1]);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error("Unable to log to Newrelic: " . $exception->getMessage());
        }
        return $headerId;
    }
}
