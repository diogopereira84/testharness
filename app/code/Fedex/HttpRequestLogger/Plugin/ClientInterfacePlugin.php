<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\HttpRequestLogger\Plugin;

use Fedex\HttpRequestLogger\Api\ConfigInterface;
use Fedex\HttpRequestLogger\Model\Logger;
use Magento\Framework\HTTP\ClientInterface;

class ClientInterfacePlugin
{
    /**
     * @param Logger $logger
     * @param ConfigInterface $config
     */
    public function __construct(
        private Logger $logger,
        private ConfigInterface $config
    ) {
    }

    /**
     * @param ClientInterface $subject
     * @param callable $proceed
     * @param mixed $uri
     * @return mixed
     */
    public function aroundGet(ClientInterface $subject, callable $proceed, mixed $uri): mixed
    {
        if (!$this->config->isLoggerEnabled()) {
            return $proceed($uri);
        }

        $startTime = microtime(true);
        $result = $proceed($uri);
        $endTime = microtime(true);

        $timeSpent = $endTime - $startTime;
        $this->logger->log($uri, $timeSpent);

        return $result;
    }

    /**
     * @param ClientInterface $subject
     * @param callable $proceed
     * @param mixed $uri
     * @param mixed $params
     * @return mixed
     */
    public function aroundPost(ClientInterface $subject, callable $proceed, mixed $uri, mixed $params): mixed
    {
        if (!$this->config->isLoggerEnabled()) {
            return $proceed($uri, $params);
        }

        $startTime = microtime(true);
        $result = $proceed($uri, $params);
        $endTime = microtime(true);

        $timeSpent = $endTime - $startTime;
        $this->logger->log($uri, $timeSpent);

        return $result;
    }
}
