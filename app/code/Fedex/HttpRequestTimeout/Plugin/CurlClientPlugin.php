<?php
/**
 * @category Fedex
 * @package  Fedex_HttpRequestTimeout
 * @copyright   Copyright (c) 2024 FedEx
 */
namespace Fedex\HttpRequestTimeout\Plugin;

use Exception;
use Fedex\HttpRequestTimeout\Api\ConfigManagementInterface;
use Fedex\HttpRequestTimeout\Api\CurlClientPluginInterface;
use Fedex\HttpRequestTimeout\Model\TimeoutValidator;
use Psr\Log\LoggerInterface;

class CurlClientPlugin implements CurlClientPluginInterface
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
     * @inheritDoc
     */
    public function beforePost($subject, $uri, $params): array
    {
        try {
            if (!$this->configManagement->isFeatureEnabled()) {
                return [$uri, $params];
            }
            $defaultTimeout = $this->configManagement->getDefaultTimeout();
            $urlsWithTimeout = $this->configManagement->getCurrentEntriesValueUnserialized();
            $defaultTimeoutEnabled = $this->configManagement->isDefaultTimeoutEnabled();
            if ($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri)) {
                $subject->setOption(CURLOPT_TIMEOUT, $urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER]);
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to '.$urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER]);
                return [$uri, $params];
            }
            if ($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled)) {
                $subject->setOption(CURLOPT_TIMEOUT, $defaultTimeout);
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to default value '.$defaultTimeout);
                return [$uri, $params];
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.' Failed to handle HTTP Timeout Request feature: '.$e->getMessage());
        }
        return [$uri, $params];
    }

    /**
     * @inheritDoc
     */
    public function beforeGet($subject, $uri): array
    {
        try {
            if (!$this->configManagement->isFeatureEnabled()) {
                return [$uri];
            }
            $defaultTimeout = $this->configManagement->getDefaultTimeout();
            $urlsWithTimeout = $this->configManagement->getCurrentEntriesValueUnserialized();
            $defaultTimeoutEnabled = $this->configManagement->isDefaultTimeoutEnabled();
            if ($this->timeoutValidator->isSuitableForDefinedTimeout($urlsWithTimeout, $uri)) {
                $subject->setOption(CURLOPT_TIMEOUT, $urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER]);
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to '.$urlsWithTimeout[$uri][ConfigManagementInterface::TIMEOUT_PARAMETER]);
                return [$uri];
            }
            if ($this->timeoutValidator->isSuitableForDefaultTimeout($urlsWithTimeout, $uri, $defaultTimeoutEnabled)) {
                $subject->setOption(CURLOPT_TIMEOUT, $defaultTimeout);
                $this->logger->info(__METHOD__.':'.__LINE__.' HTTP Request Timeout for '.$uri.' set to default value '.$defaultTimeout);
                return [$uri];
            }
        } catch (Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.' Failed to handle HTTP Timeout request Feature: '.$e->getMessage());
        }
        return [$uri];
    }
}
