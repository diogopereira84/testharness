<?php
/**
 * @category    Fedex
 * @package     Fedex_FujitsuGateway
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\CartGraphQl\Model\Note\Command\SaveInterface;
use Fedex\CartGraphQl\Model\Resolver\CreateOrUpdateOrder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * @deprecated this plugin should be removed when the createOrUpdateOrder Mutation be removed
 */
class SetOrderNotesOnQuote
{

    /**
     * SetOrderNotesOnQuote constructor
     *
     * @param SaveInterface $commandOrderNoteSave
     * @param Cart $cartModel
     */
    public function __construct(
        private readonly SaveInterface $commandOrderNoteSave,
        private readonly Cart          $cartModel
    ) {
    }

    /**
     * @param CreateOrUpdateOrder $subject
     * @param $result
     * @param $context
     * @param Field $field
     * @param array $requests
     * @return mixed
     * @throws NoSuchEntityException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function afterResolve(
        CreateOrUpdateOrder $subject,
        $result,
        $context,
        Field               $field,
        array               $requests
    ) {
        foreach ($requests as $request) {
            $args = $request->getArgs();
            $note = $args['input']['notes'] ?? null;

            if (($note) && (!empty($note))) {
                $cart = $this->cartModel->getCart($args['input']['cart_id'], $context);
                $this->commandOrderNoteSave->execute($cart, json_encode($note));
            }
        }

        return $result;
    }
}
