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
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateRequestToken extends AbstractResolver
{
    /**
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param OauthServiceInterface $oauthService
     * @param TokenProviderInterface $tokenProvider
     * @param UpdateToken $updateToken
     * @param TokenFactory $tokenFactory
     * @param array $validations
     */
    public function __construct(
        RequestCommandFactory $requestCommandFactory,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        ValidationComposite $validationComposite,
        NewRelicHeaders $newRelicHeaders,
        private readonly OauthServiceInterface $oauthService,
        private readonly TokenProviderInterface $tokenProvider,
        private readonly UpdateToken $updateToken,
        private readonly TokenFactory $tokenFactory,
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
        foreach ($requests as $request) {
            $args = $request->getArgs();
            try {
                $consumer = $this->tokenProvider->getConsumerByKey($args['input']['oauth_consumer_key']);

                /** Remove existing token associated with consumer before issuing a new one. */
                if (!$this->oauthService->deleteIntegrationToken($consumer->getId())) {
                    $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' ' . 'Can\'t delete existing token.', $headerArray);
                }

                $token = $this->tokenFactory->create()->createVerifierToken($consumer->getId());

                if ($token->getType() !== Token::TYPE_VERIFIER && $token->getType() !== Token::TYPE_ACCESS) {
                    throw new \Magento\Framework\Oauth\Exception(
                        __('Cannot create request token because consumer token is a "%1" token', $token->getType())
                    );
                }

                $token->createRequestToken($token->getId(), $consumer->getCallbackUrl());

                $this->updateToken->execute($token);
            } catch (\Exception $exception) {
                $this->loggerHelper->critical(__METHOD__ . ':' . __LINE__ . ' ' . $exception->getMessage(), $headerArray);
                throw new GraphQlAuthenticationException(__($exception->getMessage()));
            }
            $response->addResponse($request, [
                "oauth_token" => $token->getToken() ?? '',
                "oauth_token_secret" => $token->getSecret() ?? '',
                "expires_at" => $token->getExpiresAt()
            ]);
        }
        return $response;
    }
}
