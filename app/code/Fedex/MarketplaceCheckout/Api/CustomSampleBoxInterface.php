<?php
/**
 * Interface CustomSampleBoxInterface
 *
 * Provides methods to handle sample box product logic in cart and checkout.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Quote\Model\Quote;

interface CustomSampleBoxInterface
{
    /**
     * Checks if the cart contains only sample box products.
     *
     * @param Quote $quote
     * @param array $shops
     * @param bool $isFreightShippingEnabled
     * @param bool $isMktCbbEnabled
     * @param bool $isMiraklQuote
     * @return bool
     */
    public function isOnlySampleBoxProductInCart(Quote $quote, array $shops, bool $isFreightShippingEnabled, bool $isMktCbbEnabled, bool $isMiraklQuote): bool;

    /**
     * Checks if sample box products are present in the cart for any shops.
     *
     * @param array $shops
     * @return bool
     */
    public function hasSampleBoxInAnyShop(array $shops): bool;

    /**
     * Checks if sample box products are present in the cart for a specific shop.
     *
     * @param array $shop
     * @return bool
     */
    public function hasSampleBoxInShop(array $shop): bool;
}