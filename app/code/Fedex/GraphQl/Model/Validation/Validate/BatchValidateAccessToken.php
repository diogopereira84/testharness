<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model\Validation\Validate;

use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Exception\GraphQlInStoreException;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Integration\Model\Oauth\TokenFactory;
use Psr\Log\LoggerInterface;

class BatchValidateAccessToken implements GraphQlBatchValidationInterface
{
    /**
     * ValidateAccessToken constructor.
     * @param TokenFactory $tokenFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TokenFactory $tokenFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param GraphQlBatchRequestCommand $requestCommand
     * @throws GraphQlAuthenticationException
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        $requests = $requestCommand->getRequests();
        foreach ($requests as $request) {
            try {
                $tokenFactory = $this->tokenFactory->create();
                $input = $request->getArgs()['input'];
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
}
