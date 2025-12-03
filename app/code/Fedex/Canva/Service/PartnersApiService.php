<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Service;

use Fedex\Punchout\Helper\Data;
use Fedex\Header\Helper\Data as HeaderData;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Fedex\Canva\Api\Data\ConfigInterface as ModuleConfig;
use Psr\Log\LoggerInterface;

class PartnersApiService
{
    public $userTokenResponseFactory;

    /**
     * PartnersApiService constructor
     *
     * @param CookieManagerInterface $cookieManager
     * @param ModuleConfig $moduleConfig
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param Session $customerSession
     * @param Data $gateTokenHelper
     * @param Json $json
     * @param JsonValidator $jsonValidator
     * @param LoggerInterface $logger
     * @param HeaderData $headerData
     */
    public function __construct(
        private CookieManagerInterface      $cookieManager,
        private ModuleConfig                $moduleConfig,
        private ClientFactory               $clientFactory,
        private ResponseFactory             $responseFactory,
        private Session                     $customerSession,
        private Data $gateTokenHelper,
        private Json $json,
        private JsonValidator $jsonValidator,
        protected LoggerInterface $logger,
        protected HeaderData $headerData
    )
    {
    }

    /**
     * Fetch user token from API
     *
     * @return UserTokenResponseInterface
     */
    public function usertokens(): UserTokenResponseInterface
    {
        $userTokenResponse = $this->userTokenResponseFactory->create();
        $responseContent = null;
        $accessToken = $this->gateTokenHelper->getTazToken();
        $gateWayToken = $this->gateTokenHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        if ($accessToken) {
            $params = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Cookie' => "Bearer=" . $accessToken,
                    $authHeaderVal . $gateWayToken
                ]
            ];
            $customer = $this->customerSession->getCustomer();
            if ($customer->getId() && $customer->getCustomerCanvaId()) {
                $params = [
                    'body' => json_encode([
                        'userTokensRequest' => [
                            'canvaUserId' => $customer->getCustomerCanvaId()
                        ]
                    ])
                ];
            }
            $response = $this->request(
                $this->moduleConfig->getUserTokenApiUrl(),
                $params,
                Request::HTTP_METHOD_POST
            );

            $status = $response->getStatusCode();
            if ($status == 201) {
                $responseBody = $response->getBody();
                $responseContent = $responseBody->getContents();
                $this->customerSession->setResponseContent($responseContent);
            }
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Unable to get access token.');
        }
        return $responseContent;
    }

    /**
     * Do request with provided params
     *
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return Response
     */
    private function request(
        string $uriEndpoint,
        array  $params = [],
        string $requestMethod = Request::HTTP_METHOD_GET
    ): Response {
        /** @var Client $client */
        $client = $this->clientFactory->create();

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            $exceptionCode = $exception->getCode();
            $exceptionMessage = $exception->getMessage();

            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exceptionCode,
                'reason' => $exceptionMessage
            ]);

            if ($exceptionCode >= 500 && $exceptionCode < 600) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' An error occurred on the server. ' . $exceptionMessage);
            } elseif ($exceptionCode >= 400 && $exceptionCode < 500) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' An error occurred. ' . $exceptionMessage);
            } else {
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' ' . $exceptionMessage);
            }
        }

        return $response;
    }
}
