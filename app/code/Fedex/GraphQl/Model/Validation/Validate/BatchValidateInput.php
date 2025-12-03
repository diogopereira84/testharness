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
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

class BatchValidateInput implements GraphQlBatchValidationInterface
{
    const RESOLVERS = [
        'notes',
        'createOrUpdateOrder',
        'placeOrder',
        'addOrUpdateFedexAccountNumber',
        'addOrUpdateDueDate'
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Validates the input
     * @param GraphQlBatchRequestCommand $requestCommand
     * @return void
     * @throws GraphQlInputException
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        $requests = $requestCommand->getRequests();
        foreach ($requests as $request) {
            if (in_array($request->getField()->getName(), self::RESOLVERS)) {
                if (empty($request->getArgs()['input']) || !is_array($request->getArgs()['input'])) {
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . ' "input" value should be specified.');
                    throw new GraphQlInputException(__('"input" value should be specified'));
                }
            }
        }
    }
}
