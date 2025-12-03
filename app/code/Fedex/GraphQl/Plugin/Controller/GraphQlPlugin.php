<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Plugin\Controller;

use Exception;
use Fedex\GraphQl\Model\Config;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\GraphQl\Exception\ExceptionFormatter;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\GraphQl\Controller\GraphQl;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class GraphQlPlugin
{
    const ALLOWED_METHODS = [
        'createRequestToken',
        'createAccessToken',
        'getTooltipData'
    ];

    const HTTP_GRAPH_QL_SCHEMA_UNAUTHORIZED_STATUS = 403;
    const HTTP_GRAPH_QL_SCHEMA_STATUS = 200;

    /**
     * @param JsonFactory $jsonFactory
     * @param HttpResponse $httpResponse
     * @param SerializerInterface $jsonSerializer
     * @param QueryFields $queryFields
     * @param TokenFactory $tokenFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param Session $customerSession
     * @param ExceptionFormatter $graphQlError
     * @param LoggerInterface $logger
     * @param InstoreConfig $inStoreConfig
     * @param Config $config
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected HttpResponse $httpResponse,
        protected SerializerInterface $jsonSerializer,
        protected QueryFields $queryFields,
        protected TokenFactory $tokenFactory,
        protected DateTimeFactory $dateTimeFactory,
        protected Session $customerSession,
        protected ExceptionFormatter $graphQlError,
        protected LoggerInterface $logger,
        protected InstoreConfig $inStoreConfig,
        private Config $config
    ) {
    }

    /**
     * @param GraphQl $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return ResponseInterface
     *
     * @SuppressWarnings("unused")
     * @throws Throwable
     */
    public function aroundDispatch(GraphQl $subject, callable $proceed, RequestInterface $request): ResponseInterface
    {
        try {
            if (!$this->isAllowedMethod($request)) {
                if (!$request->getHeader('X-On-Behalf-Of') && $this->inStoreConfig->isEnabledXOnBehalfOfHeader()) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Required header parameter "X-On-Behalf-Of" is missing.');
                    throw new GraphQlInputException(__('Required header parameter "X-On-Behalf-Of" is missing.'));
                }
                $this->customerSession->setOnBehalfOf($request->getHeader('X-On-Behalf-Of'));

                $tokenFactory = $this->tokenFactory->create();
                $token = $tokenFactory->loadByToken($this->getBearerToken($request));

                if (!$token || $token->getType() !== Token::TYPE_ACCESS) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid token.');
                    throw new GraphQlAuthenticationException(new Phrase('Invalid token'));
                }

                $dateModel = $this->dateTimeFactory->create();
                if ($token->getExpiresAt() <= $dateModel->gmtDate()) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Expired token.');
                    throw new GraphQlAuthenticationException(new Phrase('Expired token'));
                }
            }
        } catch (GraphQlInputException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return $this->prepareErrorResponse($e, self::HTTP_GRAPH_QL_SCHEMA_STATUS);
        } catch (Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            return $this->prepareErrorResponse($e, self::HTTP_GRAPH_QL_SCHEMA_UNAUTHORIZED_STATUS);
        }

        return $proceed($request);
    }

    /**
     * @param Exception $error
     * @param int $statusCode
     * @return HttpResponse
     * @throws Throwable
     */
    private function prepareErrorResponse(Exception $error, int $statusCode): HttpResponse
    {
        $result['errors'] = isset($result) && isset($result['errors']) ? $result['errors'] : [];
        $result['errors'][] = $this->graphQlError->create($error);

        /*foreach ($result['errors'] as $error) {
            if($error['message'] == 'Internal server error') {
                $error['message'] = $error->getMessage();
            }
        }*/

        $jsonResult = $this->jsonFactory->create();
        $jsonResult->setHttpResponseCode($statusCode);
        $jsonResult->setData($result);
        $jsonResult->renderResult($this->httpResponse);
        return $this->httpResponse;
    }

    /**
     * @param $request
     * @return bool
     */
    private function isAllowedMethod($request): bool
    {
        if ($request->isPost()) {
            if ($this->config->isGraphqlRequestErrorLogsEnabled()) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' GraphQl request query/mutation : ' . $request->getContent());
            }

            $data = $this->jsonSerializer->unserialize($request->getContent());
            if (isset($data['operationName']) && $data['operationName'] === 'IntrospectionQuery') {
                return true;
            }
            if (isset($data['query'])) {
                $this->queryFields->setQuery($data['query']);
                $info = $this->queryFields->getFieldsUsedInQuery();

                if (count(array_intersect(self::ALLOWED_METHODS, array_keys($info))) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extracts the bearer token from the request header
     *
     * @param RequestInterface $request
     * @return string|null
     */
    private function extractBearerToken(RequestInterface $request): ?string
    {
        $auth = $request->getHeader('authorization');
        if ($auth && is_string($auth)) {
            $authParts = explode('Bearer ', $auth);
            return $authParts[1] ?? null;
        }
        return null;
    }

    /**
     * Parses the request content and returns it as an array
     *
     * @param RequestInterface $request
     * @return array
     */
    private function parseRequestContent(RequestInterface $request): array
    {
        try {
            $content = $request->getContent() ?: '{}';
            $parsed = $this->jsonSerializer->unserialize($content);
            return is_array($parsed) ? $parsed : [];
        } catch (\InvalidArgumentException $e) {
            // Specific exception from Magento's JSON serializer
            $this->logger->error(
                'Invalid JSON in request: ' . $e->getMessage(),
                ['request_content' => $content ?? '']
            );
            return [];
        } catch (Exception $e) {
            // Fallback for other exceptions
            $this->logger->error(
                'Error parsing request content: ' . $e->getMessage(),
                ['request_content' => $content ?? '', 'exception' => get_class($e)]
            );
            return [];
        }
    }

    /**
     * Validates that a token exists if required by configuration
     *
     * @param string|null $token
     * @param array $requestData
     * @throws GraphQlAuthenticationException
     */
    private function validateRequiredToken(?string $token, array $requestData): void
    {
        if ($token === null && $this->inStoreConfig->isEmptyTokenErrorLogEnabled()) {
            // Ensure the log message is meaningful even with empty request data
            $this->logger->error(
                'Authentication token is missing in GraphQL request.',
                [
                    'request_data' => !empty($requestData) ? json_encode($requestData) : 'empty request',
                    'operation' => $requestData['query'] ?? 'unknown operation'
                ]
            );
            throw new GraphQlAuthenticationException(__('Authentication token is required.'));
        }
    }

    /**
     * Gets the bearer token from request and validates if it's required
     * 
     * @param RequestInterface $request
     * @return string|null
     * @throws GraphQlAuthenticationException
     */
    private function getBearerToken(RequestInterface $request): ?string
    {
        $token = $this->extractBearerToken($request);
        // Only parse request and validate if token is null and validation is enabled
        if ($token === null && $this->inStoreConfig->isEmptyTokenErrorLogEnabled()) {
            $requestData = $this->parseRequestContent($request);
            $this->validateRequiredToken($token, $requestData);
        }
        return $token;
    }
}
