<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Resolver;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Fedex\GraphQl\Model\Token\UpdateToken;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Integration\Api\IntegrationServiceInterface as IntegrationService;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration as IntegrationModel;
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateAccessToken extends AbstractResolver
{
    /**
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param TokenFactory $tokenFactory
     * @param OauthServiceInterface $oauthService
     * @param TokenProviderInterface $tokenProvider
     * @param UpdateToken $updateToken
     * @param IntegrationService $integrationService
     * @param array $validations
     */
    public function __construct(
        RequestCommandFactory $requestCommandFactory,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        ValidationComposite $validationComposite,
        NewRelicHeaders $newRelicHeaders,
        private readonly TokenFactory $tokenFactory,
        private readonly OauthServiceInterface $oauthService,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly UpdateToken $updateToken,
        private readonly IntegrationService $integrationService,
        array $validations = []
    ) {
        parent::__construct(
            $requestCommandFactory,
            $batchResponseFactory,
            $loggerHelper,
            $validationComposite,
            $newRelicHeaders,
            $validations
        );
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     * @throws GraphQlAuthenticationException
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        $response = $this->batchResponseFactory->create();
        try {
            foreach ($requests as $request) {
                $args = $request->getArgs();

                $tokenFactory = $this->tokenFactory->create();
                $token = $tokenFactory->loadByToken($args['input']['oauth_token']);

                $consumer = $this->oauthService->loadConsumer($token->getConsumerId());
                $accessToken = $this->tokenProvider->getAccessToken($consumer);
                $this->updateToken->execute($token);

                $integration = $this->integrationService->findByConsumerId($consumer->getId());
                $integration->setStatus(IntegrationModel::STATUS_ACTIVE);
                $integration->setAllResources(true);
                $this->integrationService->update($integration->getData());
            }
        } catch (\Exception $exception) {
            $this->loggerHelper->critical(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage(), $headerArray);
            throw new GraphQlAuthenticationException(__($exception->getMessage()));
        }
        foreach ($requests as $request) {
            $response->addResponse($request, [
                "access_token" => $accessToken['oauth_token'] ?? '',
                "expires_at" => $token->getExpiresAt()
            ]);
        }
        return $response;
    }
}
