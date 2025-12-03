<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Validation\Validate;

use Fedex\GraphQl\Api\GraphQlValidationInterface;
use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

class ValidateModel implements GraphQlValidationInterface
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
        if (empty($requestCommand->getResult()['model']) && empty($requestCommand->getValue()['model'])) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' "model" value should be specified.');
            throw new GraphQlInputException(__('"model" value should be specified.'));
        }
    }
}
