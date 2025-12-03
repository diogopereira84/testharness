<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\CartInfoGrouperInterface;
use Fedex\MarketplaceCheckout\Model\Constants\DateConstants;
use Fedex\MarketplaceCheckout\Model\Constants\UnitConstants;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Fedex\MarketplaceProduct\Model\Shop;

class CartInfoGrouper implements CartInfoGrouperInterface
{
    /**
     * @param ShopRepositoryInterface $shopRepository
     */
    public function __construct(
        private ShopRepositoryInterface $shopRepository
    ) {
    }

    /**
     * Retrieves marketplace cart information grouped by seller.
     *
     * @param Quote $quote
     * @return array
     */
    public function getMarketplaceCartInfoGroupedBySeller(Quote $quote): array
    {
        $shops = [];

        $result = $this->getMarketplaceItems($quote);
        $shopIds = array_keys($result);
        $shopsData = $this->shopRepository->getByIds($shopIds);

        foreach ($result as $shopId => $items) {
            $totalCartWeight = $this->calculateTotalCartWeight($items);
            $businessDays = $this->extractBusinessDays($items);

            $shops[$shopId] = $this->buildShopData(
                $shopsData[$shopId],
                $items,
                $totalCartWeight,
                $businessDays
            );
        }

        return $shops;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    private function getMarketplaceItems(Quote $quote): array
    {
        $result = [];
        $marketPlaceItems = array_filter($quote->getAllItems(), fn($item) => $item->getData('mirakl_offer_id'));

        foreach ($marketPlaceItems as $item) {
            $result[$item->getMiraklShopId()][] = $item;
        }

        return $result;
    }

    /**
     * @param QuoteItem $item
     * @return float
     */
    private function getLbsWeight(QuoteItem $item): float
    {
        $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
        if (($additionalData['weight_unit'] ?? '') === UnitConstants::WEIGHT_OZ_UNIT) {
            return $item->getWeight() / 16;
        }
        return (float)$item->getWeight();
    }

    /**
     * @param Shop $shopData
     * @param array $items
     * @param float $totalCartWeight
     * @param array $businessDays
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildShopData(Shop $shopData, array $items, float $totalCartWeight, array $businessDays): array
    {
        return [
            'shop' => $shopData,
            'weight' => $totalCartWeight,
            DateConstants::BUSINESS_DAYS => $businessDays,
            'items' => $items
        ];
    }

    /**
     * Calculates the total cart weight for the given items.
     *
     * @param array $items
     * @return float
     */
    private function calculateTotalCartWeight(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            $itemWeight = $this->getLbsWeight($item);
            return $carry + ($itemWeight * $item->getQty());
        }, 0);
    }

    /**
     * Extracts the business days from the given items.
     *
     * @param array $items
     * @return array
     */
    private function extractBusinessDays(array $items): array
    {
        $businessDays = array_map(function ($item) {
            $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
            return isset($additionalData[DateConstants::BUSINESS_DAYS]) 
                ? (int)$additionalData[DateConstants::BUSINESS_DAYS] 
                : null;
        }, $items);

        return array_filter($businessDays);
    }
}