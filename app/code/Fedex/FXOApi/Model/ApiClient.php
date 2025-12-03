<?php
/**
 * @category    Fedex
 * @package     Fedex_FXOApi
 * @copyright   Copyright (c) 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\FXOApi\Model;

use Fedex\FXOApi\Api\ApiClientInterface;
use Fedex\FXOApi\Model\GetApiCallBuilder;
use Psr\Log\LoggerInterface;

/**
 *  Client Model
 */
class ApiClient implements ApiClientInterface
{
    /**
     * @param LoggerInterface $logger
     * @param GetApiCallBuilder $getApiCallBuilder
     */
    public function __construct(
        private LoggerInterface $logger,
        private GetApiCallBuilder $getApiCallBuilder
    )
    {
    }

    /**
     * Handle FXO Api Calls
     *
     * @param string $type
     * @param string $uri
     * @param string $gateway
     * @return mixed
     */
    public function fxoApiCall(string $type, string $uri, string $gateway): mixed
    {
        $apiCallOutput = [];
        $apiCallType = strtoupper($type);

        switch ($apiCallType) {
            case 'GET':
                $apiCallOutput = $this->getApiCallBuilder->buildGetApiCall($uri, $gateway);
                break;
            case 'POST':
                break;
            default:
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . 'Invalid Api Call type. Type: ' . $type);
        }

        return $apiCallOutput;
    }
}
