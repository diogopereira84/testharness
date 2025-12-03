<?php
/**
 * @category    Fedex
 * @package     Fedex_CoreApi
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CoreApi\Gateway\Http;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Psr\Log\LoggerInterface;

class Client
{
    /**
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ClientFactory       $clientFactory,
        private ResponseFactory     $responseFactory,
        protected LoggerInterface     $logger
    )
    {
    }

    /**
     * Execute client request to API
     *
     * @param TransferInterface $transfer
     * @return Response
     */
    public function request(TransferInterface $transfer): Response
    {
        $client = $this->clientFactory->create();

        try {
            $response = $client->request(
                $transfer->getMethod(),
                $transfer->getUri(),
                $transfer->getParams()
            );
        } catch (GuzzleException $exception) {
            $exceptionCode = $exception->getCode();
            $exceptionMessage = $exception->getMessage();

            $response = $this->responseFactory->create([
                'status' => $exceptionCode,
                'reason' => $exceptionMessage
            ]);

            if ($exceptionCode >= 500 && $exceptionCode < 600) {
                $this->logger->critical(
                    __METHOD__ . ':' . __LINE__ . ' An error occurred on the server. ' . $exceptionMessage
                );
            } else {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' An error occurred. ' . $exceptionMessage);
            }
        }

        return $response;
    }
}
