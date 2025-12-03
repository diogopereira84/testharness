<?php
/**
 * @category     Fedex
 * @package      Fedex_CoreApi
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Model;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class LogHelperApi
{
    /**
     * @var string XML_PATH_RATE_QUOTE_LOGS_ENABLED
     */
    public const XML_PATH_RATE_QUOTE_LOGS_ENABLED = 'tiger_add_new_relic_details_to_rate_quote_logs';

    /**
     * @var array $headers
     */
    private array $headers = [];

    /**
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly LoggerHelper $loggerHelper,
        private readonly NewRelicHeaders $newRelicHeaders,
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @param int $responseStatus
     * @param string $message
     * @return void
     */
    public function logResponseStatus(int $responseStatus, string $message) {

        $this->{$this->getLogResponseLevel($responseStatus)}($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function error(string $message) {
        $this->loggerHelper->error($message, $this->getHeaders());
    }

    /**
     * @param string $message
     * @return void
     */
    public function info(string $message) {
        $this->loggerHelper->info($message, $this->getHeaders());
    }

    /**
     * @param string $message
     * @return void
     */
    public function critical(string $message) {
        $this->loggerHelper->critical($message, $this->getHeaders());
    }

    /**
     * @param $responseStatus
     * @return string
     */
    private function getLogResponseLevel($responseStatus): string
    {
        return match (true) {
            $responseStatus >= 500 && $responseStatus < 600 => 'critical',
            $responseStatus >= 400 && $responseStatus < 500 => 'error',
            default => 'info',
        };
    }

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        if (!$this->isRateQuoteLogsEnabled()) {
            return [];
        }

        if (empty($this->headers)) {
            $this->headers = $this->newRelicHeaders->getHeaders();
        }
        return $this->headers;
    }

    /**
     * @return bool
     */
    private function isRateQuoteLogsEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::XML_PATH_RATE_QUOTE_LOGS_ENABLED);
    }

}
