<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
namespace Fedex\HttpRequestTimeout\Plugin;

use Exception;
use Fedex\HttpRequestTimeout\Api\ConfigManagementInterface;
use Fedex\HttpRequestTimeout\Model\TimeoutValidator;
use Laminas\Http\Request;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

class GuzzleClientPlugin
{
    /**
     * @param ConfigManagementInterface $configManagement
     * @param TimeoutValidator $timeoutValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ConfigManagementInterface $configManagement,
        private TimeoutValidator $timeoutValidator,
        private LoggerInterface $logger
    ) {
    }


    /**
     * @param Client $subject
     * @param string $method
     * @param $uri
     * @param array $options
     * @return array
     */
    public function beforeRequest(Client $subject, string $method, $uri = '', array $options = []): array
    {
        try {
            if (!$this->configManagement->isFeatureEnabled()) {
                return [$method, $uri, $options];
            }
            $defaultTimeout = $this->configManagement->getDefaultTimeout();
            $urlsWithTimeout = $this->configManagement->getCurrentEntriesValueUnserialized();
            $defaultTimeoutEnabled = $this->configManagement->isDefaultTimeoutEnabled();
            if ($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri)) {
                $options[ConfigManagementInterface::TIMEOUT_PARAMETER] = $urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER];
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to '.$urlsWithTimeout[$uri]['timeout']);
                return [$method, $uri, $options];
            }
            if ($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled)) {
                $options[ConfigManagementInterface::TIMEOUT_PARAMETER] = $defaultTimeout;
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to default value '.$defaultTimeout);
                return [$method, $uri, $options];
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.' Failed to handle HTTP Timeout Request feature: '.$e->getMessage());
        }
        return [$method, $uri, $options];
    }
}
