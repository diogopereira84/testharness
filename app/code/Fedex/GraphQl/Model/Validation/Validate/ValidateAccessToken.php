<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Validation\Validate;

use Fedex\GraphQl\Api\GraphQlValidationInterface;
use Fedex\GraphQl\Exception\GraphQlInStoreException;
use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\Oauth\TokenProviderInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Psr\Log\LoggerInterface;

class ValidateAccessToken implements GraphQlValidationInterface
{
    /**
     * ValidateAccessToken constructor.
     * @param TokenFactory $tokenFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected TokenFactory $tokenFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param GraphQlRequestCommand $requestCommand
     * @throws GraphQlAuthenticationException
     */
    public function validate(GraphQlRequestCommand $requestCommand): void
    {
        try {
            $tokenFactory = $this->tokenFactory->create();
            $input = $requestCommand->getArgs()['input'];
            $token = $tokenFactory->loadByToken($input['oauth_token']);
            if ($token->getSecret() !== $input['oauth_token_secret']) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid oauth consumer secret.');
                throw new GraphQlInStoreException('Invalid oauth consumer secret');
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            throw new GraphQlAuthenticationException(__($e->getMessage()));
        }
    }
}
