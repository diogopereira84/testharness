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

class ValidateUpdateCartItems implements GraphQlValidationInterface
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
        foreach ($requestCommand->getArgs()['cartItems'] as $cartItemData) {
            if (!isset($cartItemData['data']) &&
                !(isset($cartItemData['quantity']) && isset($cartItemData['cart_item_id']))) {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Required parameter "data" or "quantity" and "cart_item_id" on "cartItems" are missing.');
                throw new GraphQlInputException(
                    __('Required parameter "data" or "quantity" and "cart_item_id" on "cartItems" are missing.')
                );
            }
        }
    }
}
