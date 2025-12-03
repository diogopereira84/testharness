<?php
/**
 * @category Fedex
 * @package  Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Model;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Fedex\Customer\Api\Data\SalesForceResponseInterface;
use Fedex\Customer\Api\SalesForceInterface;
use Fedex\Customer\Model\SalesForce\Source\SubscriberDataSource;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

class SalesForce implements SalesForceInterface
{
    /**
     * @var array
     */
    public array $headers = [];

    /**
     * @param ResponseFactory $responseFactory
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $logger
     * @param ConfigInterface $config
     * @param JsonValidator $jsonValidator
     * @param Json $json
     * @param SubscriberDataSource $subscriberDataSource
     * @param SalesForceResponseInterface $salesForceResponse
     */
    public function __construct(
        private ResponseFactory $responseFactory,
        private ClientFactory $clientFactory,
        private LoggerInterface $logger,
        protected ConfigInterface $config,
        protected JsonValidator $jsonValidator,
        protected Json $json,
        protected SubscriberDataSource $subscriberDataSource,
        protected SalesForceResponseInterface $salesForceResponse
    ) {
        $this->setHeaders();
    }

    /**
     * @inheritDoc
     */
    public function subscribe(SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriber): SalesForceResponseInterface
    {
        if (!$this->config->isMarketingOptInEnabled()) {
            $this->salesForceResponse->setStatus(false);
            $this->salesForceResponse->setErrorMessage('Feature disabled');
            return $this->salesForceResponse;
        }

        $marketingOptInApiUrl = $this->config->getMarketingOptInApiUrl();

        $params = [
            'headers' => $this->headers,
            'body' => $this->prepareSubscriberData($salesForceCustomerSubscriber)
        ];

        $response = $this->request(
            $marketingOptInApiUrl,
            $params
        );
        $status = $response->getStatusCode();

        if ($status == 200) {
            $responseBody = $response->getBody();
            $responseContent = $responseBody->getContents();
            $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' SalesForce Response => ' . $responseContent);
            if ($this->jsonValidator->isValid($responseContent)) {
                $this->subscriberDataSource->map($this->salesForceResponse, $this->json->unserialize($responseContent));
            }
        }

        return $this->salesForceResponse;
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
        string $requestMethod = Request::HTTP_METHOD_POST
    ): Response {
        /** @var Client $client */
        $client = $this->clientFactory->create();

        try {
            $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' SalesForce Request => ' . $this->json->serialize($params));
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
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' SalesForce Request Error - An error occurred on the server. ' . $exceptionMessage);
            } elseif ($exceptionCode >= 400 && $exceptionCode < 500) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' SalesForce Request Error - An error occurred. ' . $exceptionMessage);
            } else {
                $this->logger->debug(__METHOD__ . ':' . __LINE__ . ' SalesForce Request Error - ' . $exceptionMessage);
            }
        }

        return $response;
    }

    /**
     * Prepare subscribe data for API
     *
     * @return string
     */
    private function prepareSubscriberData(SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriber)
    {
        $subscriberData['firstName'] = $salesForceCustomerSubscriber->getFirstName();
        $subscriberData['lastName'] = $salesForceCustomerSubscriber->getLastName();
        $subscriberData['emailAddress'] = $salesForceCustomerSubscriber->getEmailAddress();

        $subscriberData['postalCode'] = $salesForceCustomerSubscriber->getPostalCode();
        $subscriberData['cityName'] = $salesForceCustomerSubscriber->getCityName();
        $subscriberData['stateProvince'] = $salesForceCustomerSubscriber->getStateProvince();
        $subscriberData['streetAddress'] = $salesForceCustomerSubscriber->getStreetAddress();
        $subscriberData['countryCode'] = $salesForceCustomerSubscriber->getCountryCode();

        $subscriberData['languageCode'] = $salesForceCustomerSubscriber->getLanguageCode();
        $subscriberData['companyName'] = $salesForceCustomerSubscriber->getCompanyName();

        return $this->json->serialize($subscriberData);
    }

    /**
     * Setup Headers for API Call
     *
     * @return void
     */
    private function setHeaders()
    {
        $this->headers['Accept'] = 'application/json';
        $this->headers['Content-Type'] = 'application/json';
    }
}
