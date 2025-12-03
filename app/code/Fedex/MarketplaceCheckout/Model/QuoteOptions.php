<?php

/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceHelper;
use Fedex\MarketplaceCheckout\Model\PackagingCheckoutPricing;
use Magento\Checkout\Model\CartFactory;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class QuoteOptions
{
    /**
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param MarketplaceHelper $marketplaceHelper
     * @param LoggerInterface $logger
     * @param PackagingCheckoutPricing $packagingCheckoutPricing
     */
    public function __construct(
        private CartItemRepositoryInterface $cartItemRepository,
        private MarketplaceHelper           $marketplaceHelper,
        private LoggerInterface             $logger,
        private PackagingCheckoutPricing $packagingCheckoutPricing
    ) {}

    /**
     * Set Mkt shipping data.
     *
     * @param string $shipMethodData
     * @param CartFactory $quote
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setMktShipMethodDataItemOptions($shipMethodData, $quote)
    {
        if (!$shipMethodData) {
            return $this;
        }

        $shippingData = json_decode($shipMethodData, true);
        $shippingData['reference_id'] = (string)random_int(1000, 9999);
        /** Just 3P comes item_id */
        if (isset($shippingData["item_id"])) {

            $quoteItem = $quote->getItemById((int)$shippingData["item_id"]);
            // We are saving the address again because DeliveryRateApiShipAndPickup
            // is being called multiple times after storing the address and is removing the address
            $data = $this->getQuoteItemAdditionalData($quoteItem);

            $addressData = $data["mirakl_shipping_data"]["address"] ?? false;

            $this->resetShipInfoInAdditionalData($quote);

            $additionalData = $this->getQuoteItemAdditionalData($quoteItem);

            $additionalData['mirakl_shipping_data'] = $shippingData;

            if ($addressData && !isset($additionalData['mirakl_shipping_data']["address"])) {
                $additionalData['mirakl_shipping_data']["address"] = $addressData;
            }

            $quoteItem->setAdditionalData(json_encode($additionalData));
            $this->cartItemRepository->save($quoteItem);
        }
    }

    public function setMktShipMethodDataItemOptionsUpdated($shipMethodData, $quote)
    {
        if (!$shipMethodData) {
            return $this;
        }
        if ($this->marketplaceHelper->isEssendantToggleEnabled()) {
            $items = $quote->getAllVisibleItems();
        } else {
            $items = $quote->getAllItems();
        }

        $shippingData = json_decode($shipMethodData, true);

        /** Just 3P comes item_id */
        if (isset($shippingData["item_id"])) {
            $referenceBySeller = [];
            foreach ($items as $item) {
                $sellerId = $item->getData('mirakl_shop_id') ?? 0;
                $shippingSeller = isset($shippingData['seller_id']) ? $shippingData['seller_id'] : '';
                if ($item->getMiraklOfferId() && $sellerId && ($shippingSeller == $sellerId)) {

                    $shippingData['reference_id'] = $referenceBySeller[$sellerId] ?? (string)random_int(1000, 9999);
                    $referenceBySeller[$sellerId] = $shippingData['reference_id'];

                    $data = $this->getQuoteItemAdditionalData($item);

                    $addressData = $data["mirakl_shipping_data"]["address"] ?? false;

                    $additionalData = $this->getQuoteItemAdditionalData($item);

                    $additionalData['mirakl_shipping_data'] = $shippingData;

                    if ($addressData && !isset($additionalData['mirakl_shipping_data']["address"])) {
                        $additionalData['mirakl_shipping_data']["address"] = $addressData;
                    }

                    $item->setAdditionalData(json_encode($additionalData));

                    try {
                        if ($this->marketplaceHelper->isEssendantToggleEnabled() &&
                            $item->getProduct()->getTypeId() == 'configurable') {
                            $item->save();
                        } else {
                            $this->cartItemRepository->save($item);
                        }
                    } catch (CouldNotSaveException $e) {
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . 'while updating quote options facing exception' . $e->getMessage());
                    } catch (InputException $e) {
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . 'while updating quote options facing exception' . $e->getMessage());
                    } catch (NoSuchEntityException $e) {
                        $this->logger->info(__METHOD__ . ':' . __LINE__ . 'while updating quote options facing exception' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Set Mkt shipping prices.
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setMktShippingAndTaxInfo($quote)
    {
        //Filter quote by Marketplace items only
        $marketPlaceItems = array_filter($quote->getAllItems(), function ($item) {
            return $item->getData('mirakl_offer_id');
        });

        // Refactor of calculating and saving mirakl shipping prices
        if (count($marketPlaceItems) > 1) {

            // Declare Variables
            $result = $sellerInfo = [];
            $marketPlaceShippingMethodPrice = 0;

            // Build array of Marketplace items grouped by shop_id
            foreach ($marketPlaceItems as $item) {
                $shopId = $item->getMiraklShopId();
                if (array_key_exists($shopId, $result)) {
                    array_push($result[$shopId], $item);
                } else {
                    $result[$shopId] = [$item];
                }
            }

            // Build summary information about each seller (grouping all items for each seller)
            foreach ($result as $items) {
                foreach ($items as $item) {
                    $additionalData = $this->getQuoteItemAdditionalData($item);
                    if (isset($additionalData['mirakl_shipping_data'])) {
                        $shippingData = $additionalData['mirakl_shipping_data'];
                        $sellerInfo[] = [
                            'seller_id' => $item->getMiraklShopId(),
                            'shop_name' => $item->getMiraklShopName(),
                            'leadtime_to_ship' => $item->getMiraklLeadtimeToShip(),
                            'shipping_type' => $item->getMiraklShippingType(),
                            'shipping_type_label' => $item->getMiraklShippingTypeLabel(),
                            'shipping_method_price' => $shippingData['amount'],
                            'total_qty' => count($items),
                            'item_price' => round(($shippingData['amount'] / count($items)), 2)
                        ];
                        break;
                    }
                }
            }

            // Build total shipping price for all marketplace items in quote
            foreach ($sellerInfo as $info) {
                $marketPlaceShippingMethodPrice += $info['shipping_method_price'];
            }

            // Sort by mirakl_shop_id, this is needed for below price to correctly calculate
            usort($marketPlaceItems, function ($a, $b) {
                return $a['mirakl_shop_id'] <=> $b['mirakl_shop_id'];
            });

            // Divide and update shipping prices for each marketplace quote item + quote summary
            foreach ($marketPlaceItems as $index => $item) {
                $shopInfo = $this->findSellerSummary($item->getMiraklShopId(), $sellerInfo);

                if ($shopInfo) {
                    $basePrice = floor($shopInfo['shipping_method_price'] * 100 / $shopInfo['total_qty']) / 100;
                    $remaining = ($shopInfo['shipping_method_price'] * 100) - ($basePrice * 100 * $shopInfo['total_qty']);
                    if ($remaining > 0 && ($index % $shopInfo['total_qty']) < $remaining) {
                        $itemShippingAmount = $basePrice + 0.01;
                    } else {
                        $itemShippingAmount = $basePrice;
                    }

                    $this->saveQuoteItemMiraklShippingPrices($item, $itemShippingAmount, $shopInfo['shop_name'], $shopInfo['leadtime_to_ship'], $shopInfo['shipping_type'], $shopInfo['shipping_type_label']);
                }
            }

            if ($marketPlaceShippingMethodPrice > 0) {
                $this->saveQuoteMiraklShippingPrices($quote, $marketPlaceShippingMethodPrice);
            }
        } else {
            $miraklShopName = $miraklLeadtimeToShip = $miraklShippingType = $miraklShippingTypeLabel = '';
            $marketplaceQty = $marketPlaceShippingMethodPrice = 0;

            // Load all MP items and build data for future use
            foreach ($marketPlaceItems as $item) {
                $marketplaceQty++;
                if (empty($miraklShopName) && !empty($item->getMiraklShopName())) {
                    $miraklShopName = $item->getMiraklShopName();
                    $miraklLeadtimeToShip = $item->getMiraklLeadtimeToShip();
                    $miraklShippingType = $item->getMiraklShippingType();
                    $miraklShippingTypeLabel = $item->getMiraklShippingTypeLabel();
                }
                if ($marketPlaceShippingMethodPrice === 0) {
                    $additionalData = $this->getQuoteItemAdditionalData($item);
                    if (isset($additionalData['mirakl_shipping_data'])) {
                        $shippingData = $additionalData['mirakl_shipping_data'];
                        $marketPlaceShippingMethodPrice = $shippingData['amount'];
                    }
                }
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Before Update Mirakl Shipping prices - ' . $marketPlaceShippingMethodPrice . ' for Quote ID ' . $quote->getId());

            // Divide MP shipping cost and update MP quote items
            if ($marketplaceQty > 0 && $marketPlaceShippingMethodPrice > 0) {
                $itemShippingAmount = round(($marketPlaceShippingMethodPrice / $marketplaceQty), 2);
                $counter = $totalShipping = 0;
                foreach ($marketPlaceItems as $item) {
                    if ($counter == $marketplaceQty - 1) {
                        $itemShippingAmount = $marketPlaceShippingMethodPrice - $totalShipping;
                    }

                    $this->saveQuoteItemMiraklShippingPrices($item, $itemShippingAmount, $miraklShopName, $miraklLeadtimeToShip, $miraklShippingType, $miraklShippingTypeLabel);
                    $totalShipping += $itemShippingAmount;
                    $counter++;
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . 'After Update Mirakl Shipping prices - ' . $itemShippingAmount . ' for Quote Item ID ' . $item->getId());
                }

                // @codeCoverageIgnoreStart
                if ($marketplaceQty > 1) {
                    $this->saveQuoteMiraklShippingPrices($quote, $marketPlaceShippingMethodPrice);
                    $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Resaved Mirakl Shipping prices - ' . $marketPlaceShippingMethodPrice . ' for Quote ID ' . $quote->getId());
                }
                // @codeCoverageIgnoreEnd
            }
        }
    }

    public function findSellerSummary($shopId, $quote)
    {
        foreach ($quote as $row) {
            if ($row['seller_id'] == $shopId) {
                return $row;
            }
        }
        return null;
    }

    public function saveQuoteMiraklShippingPrices($quote, $marketPlaceShippingMethodPrice): void
    {
        $quote->setMiraklBaseShippingFee($marketPlaceShippingMethodPrice);
        $quote->setMiraklShippingFee($marketPlaceShippingMethodPrice);
        $quote->setMiraklBaseShippingExclTax($marketPlaceShippingMethodPrice);
        $quote->setMiraklShippingExclTax($marketPlaceShippingMethodPrice);
        $quote->setMiraklBaseShippingInclTax($marketPlaceShippingMethodPrice);
        $quote->setMiraklShippingInclTax($marketPlaceShippingMethodPrice);
        $quote->save();
    }

    public function saveQuoteItemMiraklShippingPrices($quoteItem, $itemShippingAmount, $shopName, $leadTime, $shippingType, $shippingTypeLabel): void
    {
        $quoteItem->setMiraklBaseShippingFee($itemShippingAmount);
        $quoteItem->setMiraklShippingFee($itemShippingAmount);
        $quoteItem->setMiraklBaseShippingExclTax($itemShippingAmount);
        $quoteItem->setMiraklShippingExclTax($itemShippingAmount);
        $quoteItem->setMiraklBaseShippingInclTax($itemShippingAmount);
        $quoteItem->setMiraklShippingInclTax($itemShippingAmount);
        $quoteItem->setMiraklShippingTaxPercent(0);
        $quoteItem->setMiraklBaseShippingTaxAmount(0);
        $quoteItem->setMiraklShippingTaxAmount(0);
        $quoteItem->setMiraklShippingTaxApplied(null);

        $quoteItem->setMiraklShopName($shopName);
        $quoteItem->setMiraklLeadtimeToShip($leadTime);
        $quoteItem->setMiraklShippingType($shippingType);
        $quoteItem->setMiraklShippingTypeLabel($shippingTypeLabel);


        $additionalData = $this->getQuoteItemAdditionalData($quoteItem);
        $freightInfo = $this->packagingCheckoutPricing->getPackagingItems();
        if ($this->isFreightQuoteItem($additionalData, $freightInfo)) {
            $sellerPackage = $this->packagingCheckoutPricing->findSellerRecord($quoteItem->getMiraklShopId(), $freightInfo);
            if ($sellerPackage) {
                $packaging = [];
                foreach ($sellerPackage as $item) {
                    $packaging = $item['packaging'] ?? [];
                    break;
                }
                $additionalData['freight_data'] = $packaging;
                $quoteItem->setAdditionalData(json_encode($additionalData));
            }
        }

        if (
            $this->marketplaceHelper->isEssendantToggleEnabled() &&
            $quoteItem->getProduct()->getTypeId() == 'configurable'
        ) {
            $quoteItem->save();
        } else {
            $this->cartItemRepository->save($quoteItem);
        }
    }

    /**
     * Remove shipping data from addtional info from all MP items
     * @param CartFactory $quote
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function resetShipInfoInAdditionalData($quote): void
    {
        if ($this->marketplaceHelper->isEssendantToggleEnabled()) {
            $items = $quote->getAllVisibleItems();
        } else {
            $items = $quote->getAllItems();
        }
        foreach ($items as $quoteItem) {
            if ($quoteItem->getMiraklOfferId()) {
                $additionalData = $this->getQuoteItemAdditionalData($quoteItem);
                if (isset($additionalData['mirakl_shipping_data'])) {
                    unset($additionalData['mirakl_shipping_data']);
                    $quoteItem->setAdditionalData(json_encode($additionalData));
                    if (
                        $this->marketplaceHelper->isEssendantToggleEnabled() &&
                        $quoteItem->getProduct()->getTypeId() == 'configurable'
                    ) {
                        $quoteItem->save();
                    } else {
                        $this->cartItemRepository->save($quoteItem);
                    }
                }
            }
        }
    }

    /**
     * @param $quoteItem
     * @return mixed[]
     */
    private function getQuoteItemAdditionalData($quoteItem): array
    {
        $additionalData = $quoteItem->getAdditionalData();
        if ($additionalData) {
            return json_decode($additionalData, true);
        }
        return [];
    }

    /**
     * @param array $additionalData
     * @param array $freightInfo
     * @return bool
     */
    private function isFreightQuoteItem(array $additionalData, array $freightInfo): bool
    {
        return isset($additionalData['punchout_enabled'])
            && (bool)$additionalData['punchout_enabled']
            && isset($additionalData['packaging_data'])
            && !empty($additionalData['packaging_data'])
            && $freightInfo;
    }
}
