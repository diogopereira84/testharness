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

class BatchValidateUpdateCartItems implements GraphQlBatchValidationInterface
{
    const RESOLVERS = ['updateCartItems'];

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
     * @throws GraphQlInputException
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void
    {
        $requests = $requestCommand->getRequests();
        foreach ($requests as $key => $request) {
            if (in_array($request->getField()->getName(), self::RESOLVERS)) {
                $args = $request->getArgs();
                $this->getCheckValidationCartItems($args);
            }
        }
    }

    /**
     * @param $args
     * @return void
     * @throws GraphQlInputException
     */
    private function getCheckValidationCartItems($args): void
    {
        foreach ($args['cartItems'] as $cartItemData) {
            if (!isset($cartItemData['data']) &&
                !(isset($cartItemData['quantity']) && isset($cartItemData['cart_item_id']))) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                    ' Required parameter "data" or "quantity" and "cart_item_id" on "cartItems" are missing.');
                throw new GraphQlInputException(
                    __('Required parameter "data" or "quantity" and "cart_item_id" on "cartItems" are missing.')
                );
            }
        }
    }
}
