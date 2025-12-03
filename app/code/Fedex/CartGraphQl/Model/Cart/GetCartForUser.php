<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Cart;

use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\IsActive;
use Magento\QuoteGraphQl\Model\Cart\UpdateCartCurrency;

class GetCartForUser extends \Magento\QuoteGraphQl\Model\Cart\GetCartForUser
{
    /**
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     * @param IsActive $isActive
     * @param UpdateCartCurrency $updateCartCurrency
     * @param ConfigInterface $config
     */
    public function __construct(
        private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private CartRepositoryInterface $cartRepository,
        IsActive $isActive,
        private UpdateCartCurrency $updateCartCurrency,
        private ConfigInterface $config
    ) {
        parent::__construct(
            $maskedQuoteIdToQuoteId,
            $cartRepository,
            $isActive,
            $updateCartCurrency
        );
    }

    /**
     * @param string $cartHash
     * @param int|null $customerId
     * @param int $storeId
     * @return Quote
     * @throws GraphQlAuthorizationException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    public function execute(string $cartHash, ?int $customerId, int $storeId): Quote
    {
        if (!$this->config->isEnabledUserCannotPerformCartOperationsFix()) {
            return parent::execute($cartHash, $customerId, $storeId);
        }

        try {
            return parent::execute($cartHash, $customerId, $storeId);
        } catch (GraphQlAuthorizationException) {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($cartHash);
            $cart = $this->cartRepository->get($cartId);
            return $this->updateCartCurrency->execute($cart, $storeId);
        }
    }
}
