<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Validation\Validate;

use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

class BatchValidateModel implements GraphQlBatchValidationInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param GraphQlBatchRequestCommand $requestCommand
     * @return void
     * @throws GraphQlInputException
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        $requests = $requestCommand->getRequests();
        foreach ($requests as $key => $request) {
            if (empty($request->getValue()['model'])) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' "model" value should be specified.');
                throw new GraphQlInputException(__('"model" value should be specified.'));
            }
        }
    }
}
