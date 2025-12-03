<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Magento\Quote\Model\Quote;
use Fedex\MarketplaceCheckout\Api\CustomSampleBoxInterface;
use Fedex\MarketplaceCheckout\Model\Offers;
use Magento\Quote\Model\Quote\Item;

class CustomSampleBox implements CustomSampleBoxInterface
{
    public function __construct(
        private Offers $offers
    ) {
    }

    /**
     * Checks if the cart contains only sample box products.
     *
     * @param Quote $quote The quote object representing the cart.
     * @param array $shops List of shops associated with the cart.
     * @param bool $isFreightShippingEnabled Indicates if freight shipping is enabled.
     * @param bool $isMktCbbEnabled Indicates if the marketplace CBB feature is enabled.
     * @param bool $isMiraklQuote Indicates if the quote is a Mirakl quote.
     * @return bool True if only sample box products are in the cart, false otherwise.
     */
    public function isOnlySampleBoxProductInCart(Quote $quote, array $shops, bool $isFreightShippingEnabled, bool $isMktCbbEnabled, bool $isMiraklQuote): bool
    {
        if (!$isFreightShippingEnabled) {
            return false;
        }

        if ($this->doesCartHaveOneShopOnly($isMiraklQuote, $shops)) {
            return $this->isOnlyNonPunchoutInCart($shops);
        }

        if ($isMktCbbEnabled && $isMiraklQuote) {
            return $this->hasSampleBoxInAnyShop($shops);
        }

        return false;
    }

    /**
     * Checks if sample box products are present in the cart for all shops.
     *
     * @param array $shops List of shops to check.
     * @return bool True if sample box products are present for all shops, false otherwise.
     */
    public function hasSampleBoxInAnyShop(array $shops): bool
    {
        foreach ($shops as $shop) {
            if ($this->hasSampleBoxInShop($shop)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if sample box products are present in the cart for a specific shop.
     *
     * @param array $shop The shop to check.
     * @return bool True if sample box products are present for the shop, false otherwise.
     */
    public function hasSampleBoxInShop(array $shop): bool
    {
        if (count($shop['items']) !== 1) {
            return false;
        }

        $item = reset($shop['items']);
        $additionalData = $item->getAdditionalData();

        if (is_null($additionalData)) {
            return false;
        }

        $additionalInfo = json_decode($additionalData);
        if (!empty($additionalInfo->punchout_enabled) && (bool)$additionalInfo->punchout_enabled) {
            return false;
        }

        return $this->hasForcedShippingFlag($item);
    }

    private function doesCartHaveOneShopOnly(bool $isMiraklQuote, array $shops): bool
    {
        return $isMiraklQuote && count($shops) === 1;
    }

    /**
     * @param array $shops
     * @return bool
     */
    private function isOnlyNonPunchoutInCart(array $shops): bool
    {
        $shop = reset($shops);
        if (count($shop['items']) !== 1) {
            return false;
        }

        $item = reset($shop['items']);
        $additionalData = $item->getAdditionalData();
        if (is_null($additionalData)) {
            return false;
        }

        $decoded = json_decode($additionalData);
        if (!isset($decoded->punchout_enabled)) {
            return true;
        }

        return !(bool)$decoded->punchout_enabled;
    }

    /**
     * @param Item $item
     * @return bool
     */
    private function hasForcedShippingFlag(Item $item): bool
    {
        $offerId = (int) $item->getData('mirakl_offer_id');
        if (!$offerId) {
            return false;
        }

        $offers = $this->offers->getOfferItemsByOfferId($offerId);

        foreach ($offers as $offer) {
            $data = $offer->getAdditionalInfo();
            if (!empty($data['force_mirakl_shipping_options']) && $data['force_mirakl_shipping_options'] === 'true') {
                return true;
            }
        }

        return false;
    }
}