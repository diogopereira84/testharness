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
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\Oauth\TokenProviderInterface;
use Psr\Log\LoggerInterface;

class BatchValidateRequestToken implements GraphQlBatchValidationInterface
{
    /**
     * ValidateConsumerSecret constructor.
     * @param TokenProviderInterface $tokenProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TokenProviderInterface $tokenProvider,
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
                $input = $request->getArgs()['input'];
                $consumer = $this->tokenProvider->getConsumerByKey($input['oauth_consumer_key']);
                if ($consumer->getSecret() !== $input['oauth_consumer_secret']) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Invalid oauth consumer secret.');
                    throw new \Exception('Invalid oauth consumer secret');
                }
            } catch (\Exception $e) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                throw new GraphQlAuthenticationException(__($e->getMessage()));
            }
        }
    }
}
