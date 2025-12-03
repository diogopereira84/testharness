<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model\Middleware;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InputException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\AuthorizationException;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\IntegrationException;

class AuthorizationMiddleware
{
    /**
     * @param LoggerInterface $logger
     * @param JsonFactory $jsonFactory
     * @param HttpRequest $request
     * @param ScopeConfigInterface $configInterface
     * @param Http $httpResponse
     */
    public function __construct(
        private LoggerInterface      $logger,
        private JsonFactory          $jsonFactory,
        private HttpRequest          $request,
        private ScopeConfigInterface $configInterface,
        private Http $httpResponse
    ) {
    }

    /**
     * Validate the "authorization" header from the request.
     *
     * @param HttpRequest $request
     * @return void
     * @throws InputException
     */
    public function validateAuthorizationHeader()
    {
        $authorizationHeader   = $this->request->getHeader('Authorization');
        $expectedAuthorization = $this->getAuthorizationCode();

        if ($authorizationHeader !== $expectedAuthorization) {
            $message = 'Invalid Authorization Header from Mirakl Webhook, Authorization: '.$authorizationHeader;
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $message);
            $this->sendErrorAuthenticationResponse($message);
        }
    }

    /**
     * Create a JSON error response with HTTP 401 status code.
     *
     * @param string $message
     * @return void
     */
    public function sendErrorAuthenticationResponse($message)
    {
        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($message);
        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $message);
        throw new AuthorizationException(__($message));
    }

    /**
     * Create a JSON error response.
     *
     * @param string $message
     * @return void
     */
    public function sendErrorResponse($message)
    {
        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($message);
        $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $message);
        throw new IntegrationException(__($message));
    }

    /**
     * Create a JSON success response with HTTP 200 status code.
     *
     * @param string $message
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function sendSuccessResponse($message)
    {
        $resultJson = $this->jsonFactory->create();
        $resultJson->setData($message);
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $message);
        return $resultJson;
    }

    /**
     * Get Authorization Code URL.
     *
     * @return mixed
     */
    private function getAuthorizationCode()
    {
        return $this->configInterface->getValue(
            "fedex/marketplacewebhook/authorization_code",
            ScopeInterface::SCOPE_STORE
        );
    }
}
