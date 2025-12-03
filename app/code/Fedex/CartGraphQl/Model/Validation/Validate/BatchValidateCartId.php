<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Validation\Validate;

use Fedex\GraphQl\Api\GraphQlBatchValidationInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

class BatchValidateCartId implements GraphQlBatchValidationInterface
{
    const RESOLVERS = [
        'addProductsToCart',
        'updateCartItems'
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param GraphQlBatchRequestCommand $requestCommand
     * @return void
     * @throws GraphQlInputException
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        $requests = $requestCommand->getRequests();
        foreach ($requests as $key => $request) {
            if (in_array($request->getField()->getName(), self::RESOLVERS)) {
                $args = $request->getArgs();
                if (empty($args['cartId'])) {
                    isset($args['input']) ? $cartParameter = 'cart_id' : $cartParameter = 'cartId';
                    $this->logger->error(__METHOD__ . ':' . __LINE__ .
                        ' Required parameter ' . $cartParameter . ' is missing.');
                    throw new GraphQlInputException(
                        __('Required parameter "%1" is missing.', $cartParameter)
                    );
                }
            }
        }
    }
}
