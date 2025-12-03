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

class ValidateCartId implements GraphQlValidationInterface
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
        $args = $requestCommand->getArgs();
        if (empty($args['input']['cart_id']) && empty($args['cartId'])) {
            isset($args['input']) ? $cartParameter = 'cart_id' : $cartParameter = 'cartId';
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Required parameter ' . $cartParameter . ' is missing.');
            throw new GraphQlInputException(
                __('Required parameter "%1" is missing.', $cartParameter)
            );
        }
    }
}
