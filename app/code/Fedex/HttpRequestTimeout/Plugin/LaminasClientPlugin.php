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
use Laminas\Http\Client;

class LaminasClientPlugin
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
     * @param Request|null $request
     * @return Request[]|null[]
     */
    public function beforeSend(Client $subject, Request $request = null): array
    {
        $uri = '';
        try {
            if (!$this->configManagement->isFeatureEnabled()) {
                return [$request];
            }
            $defaultTimeout = $this->configManagement->getDefaultTimeout();
            $urlsWithTimeout = $this->configManagement->getCurrentEntriesValueUnserialized();
            $defaultTimeoutEnabled = $this->configManagement->isDefaultTimeoutEnabled();
            $uri = $subject->getUri();
            $uri = $uri->toString();
            if ($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri)) {
                $subject->setOptions([ConfigManagementInterface::TIMEOUT_PARAMETER => $urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER]]);
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to '.$urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER]);
                return [$request];
            }
            if ($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled)) {
                $subject->setOptions([ConfigManagementInterface::TIMEOUT_PARAMETER => $defaultTimeout]);
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to default value '.$defaultTimeout);
                return [$request];
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.' Failed to handle HTTP Timeout Request feature: '.$e->getMessage());
        }
        return [$request];
    }
}
