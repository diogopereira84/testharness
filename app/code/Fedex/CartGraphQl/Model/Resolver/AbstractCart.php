<?php
/**
 * @category    Fedex
 * @package     Fedex_AbstractCart
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Olimjon Akhmedov <oakhmedov@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateCartIdForCreateOrder as ValidateCartId;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchCompositeFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @deprecated in B-2248879
 * @see \Fedex\CartGraphQl\Model\Checkout\Cart
 */
abstract class AbstractCart implements BatchResolverInterface
{
    /**
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     * @param Address $address
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationBatchCompositeFactory $validationBatchCompositeFactory
     * @param ValidateCartId $validateCartId
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected GetCartForUser          $getCartForUser,
        protected CartRepositoryInterface $cartRepository,
        protected Address                 $address,
        protected RequestCommandFactory   $requestCommandFactory,
        protected ValidationBatchCompositeFactory $validationBatchCompositeFactory,
        protected ValidateCartId $validateCartId,
        protected LoggerInterface         $logger
    ) {
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param array $requests
     * @return GraphQlBatchRequestCommand
     */
    public function getRequestCommand(
        ContextInterface $context,
        Field            $field,
        array $requests
    ): GraphQlBatchRequestCommand {
        return $this->requestCommandFactory->create([
            'field' => $field,
            'context' => $context,
            'requests' => $requests
        ]);
    }

    /**
     * Get cart
     *
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
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, null, $storeId);

        if (!$cart->isSaveAllowed()) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            ' Customer contact is not allowed to save into cart. GTN: ' . $cart->getGtn() ?? '');
            throw new GraphQlInputException(__('Customer contact is not allowed to save into cart'));
        }
        return $cart;
    }

    /**
     * Set contact info
     *
     * @param mixed $item
     * @param array $shippingContact
     * @return void
     */
    public function setContactInfo($item, array $shippingContact)
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
     * Set cart data
     *
     * @param Quote $cart
     * @param array $shippingContact
     * @param array $alternateContact
     * @return void
     */
    public function setCustomerCartData(Quote $cart, array $shippingContact, array $alternateContact): void
    {
        $cart->setCustomerIsGuest(true);
        $cart->setCustomerFirstname($shippingContact['firstname'] ?? '');
        $cart->setCustomerLastname($shippingContact['lastname'] ?? '');
        $cart->setCustomerEmail($shippingContact['email'] ?? '');
        $cart->setCustomerTelephone($shippingContact['telephone'] ?? '');
        $cart->setdata('customer_PhoneNumber_ext', $shippingContact['ext'] ?? null);
        $cart->setData('is_alternate_pickup', (bool)$alternateContact);
        $cart->setData('is_alternate', (bool)$alternateContact);
    }
}
