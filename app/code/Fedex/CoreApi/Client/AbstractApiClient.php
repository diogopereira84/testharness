<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CoreApi\Client;

use Laminas\Http\Client\Adapter\Exception\RuntimeException;
use Laminas\Http\Client\Exception\RuntimeException as ClientRuntimeException;
use Laminas\Http\Client\Adapter\Curl;
use Magento\Framework\HTTP\LaminasClientFactory;
use Psr\Log\LoggerInterface;
use Fedex\CoreApi\Model\Config\Backend as CoreApiConfig;

class AbstractApiClient implements AbstractApiClientInterface
{
    private const API_DEBUG_STATUS_CODE_TXT = ' API debug: Status code = ';
    private const MESSAGE_TXT = ' - Message: ';

    /**
     * @var array
     */
    public array $headers = [
        'Content-Type' => 'application/json',
    ];

    /**
     * @var null
     */
    public $domain = null;

    /**
     * AbstractApiClient constructor.
     * @param LaminasClientFactory $httpClientFactory
     * @param LoggerInterface $logger
     * @param CoreApiConfig $configHelper
     */
    public function __construct(
        protected LaminasClientFactory $httpClientFactory,
        private LoggerInterface $logger,
        private CoreApiConfig $configHelper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(string $urlKey, string $method, $requestBody = null, array $params = [])
    {
        if ($this->domain === null) {
            return false;
        }

        $client = $this->httpClientFactory->create();

        $client->setHeaders($this->headers);
        $client->setUri($this->domain . $urlKey);
        $client->setMethod($method);
        if (isset($params) === true) {
            $client->setParameterPost($params);
        }
        $timeOut = $this->configHelper->getApiTimeOut();
        $client->setOptions([
            'adapter' => Curl::class,
            'timeout' => $timeOut,
            'allow_unwise' => true
        ]);
        if ($requestBody) {
            $client->setRawBody($requestBody);
        }

        try {
            $response = $client->send();
            $responseStatus = $response->getStatusCode();

            if ($responseStatus >= 500 && $responseStatus < 600) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . self::API_DEBUG_STATUS_CODE_TXT
                    . $responseStatus . self::MESSAGE_TXT . $response->getReasonPhrase());
            } elseif ($responseStatus >= 400 && $responseStatus < 500) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . self::API_DEBUG_STATUS_CODE_TXT
                    . $responseStatus . self::MESSAGE_TXT . $response->getReasonPhrase());
            } else {
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . self::API_DEBUG_STATUS_CODE_TXT
                    . $responseStatus . self::MESSAGE_TXT . $response->getReasonPhrase());
            }
        } catch (RuntimeException|\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return false;
        }

        return $response->getBody();
    }
}
