<?php

namespace Fedex\CartGraphQl\Api;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Model\Quote;

interface CartInterface
{
    /**
     * @param string $cart_id
     * @param ContextInterface $context
     * @return Quote
     */
    public function getCart(string $cart_id, ContextInterface $context): Quote;

    /**
     * @param $item
     * @param array $shippingContact
     * @return void
     */
    public function setContactInfo($item, array $shippingContact): void;

    /**
     * @param Quote $cart
     * @param array $shippingContact
     * @param array $alternateContact
     * @return void
     */
    public function setCustomerCartData(Quote $cart, array $shippingContact, array $alternateContact): void;
}
