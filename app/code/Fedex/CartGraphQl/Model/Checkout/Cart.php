<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Checkout;

use Fedex\CartGraphQl\Api\CartInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

class Cart implements CartInterface
{
    /**
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        private readonly GetCartForUser $getCartForUser
    ) {
    }

    /**
     * @param string $cart_id
     * @param ContextInterface $context
     * @return Quote
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    public function getCart(
        string $cart_id,
        ContextInterface $context
    ): Quote {
        $maskedCartId = $cart_id;
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        return $this->getCartForUser->execute($maskedCartId, null, $storeId);
    }

    /**
     * @param $item
     * @param array $shippingContact
     * @return void
     */
    public function setContactInfo($item, array $shippingContact): void
    {
        $item->setFirstName($shippingContact['firstname'] ?? null);
        $item->setLastname($shippingContact['lastname'] ?? null);
        $item->setEmail($shippingContact['email'] ?? null);
        $item->setTelephone($shippingContact['telephone'] ?? null);
        $item->setExt($shippingContact['ext'] ?? null);
        $item->setContactNumber($shippingContact['telephone'] ?? null);
        $item->setContactExt($shippingContact['ext'] ?? null);
    }

    /**
     * @param Quote $cart
     * @param array $shippingContact
     * @param array $alternateContact
     * @return void
     */
    public function setCustomerCartData(Quote $cart, array $shippingContact, array $alternateContact): void
    {
        $isAlternateContact = !empty($alternateContact);

        $cart->setCustomerIsGuest(true);
        $cart->setCustomerFirstname($shippingContact['firstname'] ?? '');
        $cart->setCustomerLastname($shippingContact['lastname'] ?? '');
        $cart->setCustomerEmail($shippingContact['email'] ?? '');
        $cart->setCustomerTelephone($shippingContact['telephone'] ?? '');
        $cart->setData('customer_PhoneNumber_ext', $shippingContact['ext'] ?? null);
        $cart->setData('is_alternate_pickup', $isAlternateContact);
        $cart->setData('is_alternate', $isAlternateContact);
    }

    /**
     * @param Quote $cart
     * @return bool
     */
    public function checkIfQuoteIsEmpty(Quote $cart): bool
    {
        return $cart->getItemsCount() == 0;
    }
}
