<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\OffersInterface;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingAddressKeys;
use Fedex\MarketplaceCheckout\Model\Constants\ShippingConstants;
use Fedex\MarketplaceCheckout\Model\DTO\AddressDTO;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollection;

class Offers implements OffersInterface
{
    /**
     * @param OfferCollection $offerCollectionFactory
     */
    public function __construct(
        private OfferCollection $offerCollectionFactory
    ) {
    }

    /**
     * Builds a collection of offer addresses with their associated quantities based on the provided offers and quote item.
     *
     * @param array $offers The offers data to process.
     * @param QuoteItem $item The quote item to associate the offers with.
     * @param string $regionCode The region code for the offers.
     * @param array $shopShippingInfo Shipping information for the shop.
     * @return array Collection of offer addresses with quantities.
     */
    public function getOfferAddresses(array $offers, QuoteItem $item, string $regionCode, array $shopShippingInfo): array
    {
        $offerAddress = [];

        foreach ($offers as $offer) {
            if (!$this->isRegionCodeValid($offer, $regionCode)) {
                continue;
            }

            $itemQty = $this->computeMergedItemQuantity($offer, $item, $offerAddress);
            $originRef = $this->getOriginReference($offer);

            $addressData = $this->buildAddressData($offer, $shopShippingInfo);
            $addressData[$originRef] = $itemQty;

            $offerAddress[$offer->getId()] = $addressData;
        }

        return $offerAddress;
    }

    /**
     * Retrieves the items associated with a specific offer ID.
     *
     * @param int $offerId The ID of the offer.
     * @return array The items associated with the offer.
     */
    public function getOfferItemsByOfferId(int $offerId): array
    {
        return $this->offerCollectionFactory->create()
            ->addFieldToFilter('offer_id', $offerId)
            ->getItems();
    }

    /**
     * Retrieves filtered offers based on the product SKU and shop ID.
     *
     * @param string $productSku The SKU of the product.
     * @param int $shopId The ID of the shop.
     * @return array The filtered offers.
     */
    public function getFilteredOffers(string $productSku, int $shopId): array
    {
        return $this->offerCollectionFactory->create()
            ->addFieldToFilter('product_sku', $productSku)
            ->addFieldToFilter('shop_id', $shopId)
            ->getItems();
    }

    /**
     * @param Offer $offer
     * @param QuoteItem $item
     * @param array $offerAddress
     * @return float
     */
    private function computeMergedItemQuantity(Offer $offer, QuoteItem $item, array $offerAddress): float
    {
        $itemQty = $item->getQty();
        $info = $offer->getAdditionalInfo();

        if (!isset($info[ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE])) {
            throw new \LogicException('Missing ORIGIN_ADDRESS_REFERENCE in offer');
        }

        $originRef = $info[ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE];

        if (isset($addressCounted[$offer->getId()][$originRef])) {
            $itemQty += $addressCounted[$offer->getId()][$originRef];
        }

        return $itemQty;
    }

    /**
     * @param Offer $offer
     * @param string $regionCode
     * @return bool
     */
    private function isRegionCodeValid(Offer $offer, string $regionCode): bool
    {
        $statesValue = $this->getOfferStateString($offer);

        if ($statesValue === ShippingConstants::ALL_STATES) {
            return true;
        }

        $states = array_map('trim', explode(',', $statesValue));
        return in_array($regionCode, $states, true);
    }

    private function getOfferStateString(Offer $offer): string
    {
        $info = $offer->getAdditionalInfo();
        if (!isset($info[ShippingAddressKeys::ORIGIN_ADDRESS_STATES])) {
            throw new \LogicException("Missing ORIGIN_ADDRESS_STATES in offer additional info.");
        }

        return trim($info[ShippingAddressKeys::ORIGIN_ADDRESS_STATES]);
    }

    /**
     * @param Offer $offer
     * @param array $shopShippingInfo
     * @return array
     */
    private function buildAddressData(Offer $offer, array $shopShippingInfo): array
    {
        $info = $offer->getAdditionalInfo();

        $address = new AddressDTO(
            $info[ShippingAddressKeys::ORIGIN_CITY] ?? $shopShippingInfo[ShippingAddressKeys::ORIGIN_SHOP_CITY] ?? '',
            $info[ShippingAddressKeys::ORIGIN_STATE] ?? $shopShippingInfo[ShippingAddressKeys::ORIGIN_SHOP_STATE] ?? '',
            $info[ShippingAddressKeys::ORIGIN_ZIPCODE] ?? $shopShippingInfo[ShippingAddressKeys::ORIGIN_SHOP_ZIPCODE] ?? ''
        );

        return $address->toArray();
    }

    private function getOriginReference($offer): string
    {
        $info = $offer->getAdditionalInfo();
        if (!isset($info[ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE])) {
            throw new \LogicException('Missing ORIGIN_ADDRESS_REFERENCE in offer additional info');
        }

        return $info[ShippingAddressKeys::ORIGIN_ADDRESS_REFERENCE];
    }
}