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
use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

class ValidateInput implements GraphQlValidationInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected LoggerInterface $logger
    )
    {
    }

    public function validate(GraphQlRequestCommand $requestCommand): void
    {
        if (empty($requestCommand->getArgs()['input']) || !is_array($requestCommand->getArgs()['input'])) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' "input" value should be specified.');
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
    }
}
