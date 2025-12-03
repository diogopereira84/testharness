<?php
/**
 * Interface OffersInterface
 *
 * Defines methods for handling offer-related operations in the Fedex MarketplaceCheckout module.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Quote\Model\Quote\Item as QuoteItem;

interface OffersInterface
{
    /**
     * Extracts addresses from offers
     *
     * @param array $offers
     * @param QuoteItem $item .
     * @param string $regionCode
     * @param array $shopShippingInfo
     * @return array
     */
    public function getOfferAddresses(array $offers, QuoteItem $item, string $regionCode, array $shopShippingInfo): array;

    /**
     * Retrieves the items associated with a specific offer ID.
     *
     * @param int $offerId
     * @return array
     */
    public function getOfferItemsByOfferId(int $offerId): array;

    /**
     * Retrieves filtered offers based on the product SKU and shop ID.
     *
     * @param string $productSku
     * @param int $shopId
     * @return array
     */
    public function getFilteredOffers(string $productSku, int $shopId): array;
}