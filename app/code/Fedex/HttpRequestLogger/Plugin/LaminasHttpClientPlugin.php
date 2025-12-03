<?php
namespace Fedex\HttpRequestLogger\Plugin;

use Fedex\HttpRequestLogger\Api\ConfigInterface;
use Fedex\HttpRequestLogger\Model\Logger;
use Laminas\Http\Client;

class LaminasHttpClientPlugin
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
     * @param Client $subject
     * @param callable $proceed
     * @param mixed $args
     * @return mixed
     */
    public function aroundSend(Client $subject, callable $proceed, mixed $args = null): mixed
    {
        if (!$this->config->isLoggerEnabled()) {
            return $proceed($args);
        }
        $startTime = microtime(true);
        $result = $proceed($args);
        $endTime = microtime(true);

        $timeSpent = $endTime - $startTime;
        $this->logger->log($subject->getUri(), $timeSpent);

        return $result;
    }
}
