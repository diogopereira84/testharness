<?php
namespace Fedex\HttpRequestLogger\Plugin;

use Fedex\HttpRequestLogger\Api\ConfigInterface;
use Fedex\HttpRequestLogger\Model\Logger;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class GuzzleHttpClientPlugin
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
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return mixed
     */
    public function aroundRequest(Client $subject, callable $proceed, string $method, string $uri = '', array $options = []): mixed
    {
        if (!$this->config->isLoggerEnabled()) {
            $proceed($method, $uri, $options);
        }

        $startTime = microtime(true);
        $result = $proceed($method, $uri, $options);
        $endTime = microtime(true);

        $timeSpent = $endTime - $startTime;
        $this->logger->log($uri, $timeSpent);

        return $result;
    }
}
