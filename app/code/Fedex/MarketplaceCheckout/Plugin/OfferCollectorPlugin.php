<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Plugin;

use Fedex\MarketplaceCheckout\Helper\Data;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\Quote\Cache;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Mirakl\MMP\Front\Domain\Shipping\OfferQuantityShippingTypeTuple;

class OfferCollectorPlugin
{
    /** @var string */
    private const MIRAKL_SHIPPING_RATES_VALUE = 'mirakl-shipping-rates';

    /**
     * @param MarketplaceConfig $config
     * @param QuoteItemResourceFactory $quoteItemResourceFactory
     * @param OfferFactory $offerFactory
     * @param Cache $cache
     */
    public function __construct(
        private MarketplaceConfig $config,
        private QuoteItemResourceFactory $quoteItemResourceFactory,
        private OfferFactory $offerFactory,
        private Cache $cache,
        private Data $helperData
    ) {
    }

    /**
     * @param OfferCollector $subject
     * @param callable $proceed
     * @param CartInterface $quote
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetOffersWithQty(OfferCollector $subject, callable $proceed, CartInterface $quote)
    {
        $offersWithQty = [];

        /** @var QuoteItem $quoteItem */
        foreach ($this->getItemsWithOffer($quote) as $quoteItem) {
            /** @var Offer $offer */
            $offer = $quoteItem->getData('offer');
            $offerQty = (new OfferQuantityShippingTypeTuple())
                ->setOfferId($offer->getId())
                ->setQuantity($quoteItem->getQty())
                ->setShippingTypeCode($quoteItem->getMiraklShippingType());

            $offersWithQty[] = $offerQty;
        }

        return $this->insertLeadtimeToShip($quote, $offersWithQty) ?? $offersWithQty;
    }

    /**
     * Insert Leadtime to Ship in offers with qty
     *
     * @param CartInterface $quote
     * @return array
     */
    public function insertLeadtimeToShip(CartInterface $quote, $offersWithQty)
    {
        $leadTime = [];
        foreach ($quote->getAllItems() as $item) {
            if ($item->getMiraklOfferId()) {
                $shopCustomAttributes = $this->config->getShopCustomAttributesByProductSku($item->getSku());
                if ($shopCustomAttributes['shipping-rate-options'] == self::MIRAKL_SHIPPING_RATES_VALUE) {
                    $leadTime[$item->getMiraklShopId()]['leadtimes'][] =  $item->getMiraklLeadtimeToShip();
                    $leadTime[$item->getMiraklShopId()]['offer_ids'][] =  $item->getMiraklOfferId();
                }
            }
        }

        foreach ($offersWithQty as $offerQty) {
            foreach ($leadTime as $leadTimeItem) {
                if (in_array($offerQty->getOfferId(), $leadTimeItem['offer_ids'])) {
                    $offerQty->setLeadtimeToShip(max($leadTimeItem['leadtimes']));
                    break;
                }
            }
        }

        return $offersWithQty;
    }

    /**
     * Returns current offers in specified quote
     *
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getItemsWithOffer(CartInterface $quote)
    {
        $quoteItemsByItemId = [];
        /** @var QuoteItem $quoteItem */
        foreach ($this->getQuoteItems($quote) as $quoteItem) {
            if ($quoteItem->isDeleted() || $quoteItem->getParentItemId()) {
                continue;
            }

            /** @var QuoteItemOption $offerCustomOption */
            $offerCustomOption = $quoteItem->getProduct()->getCustomOption('mirakl_offer');
            if (!$offerCustomOption) {
                continue;
            }

            $offer = $this->offerFactory->fromJson($offerCustomOption->getValue());

            $quoteItem->setData('offer', $offer);
            $quoteItemsByItemId[$quoteItem->getId()] = $quoteItem;
        }

        return $quoteItemsByItemId;
    }

    /**
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getQuoteItems(CartInterface $quote)
    {
        $registryKey = sprintf('mirakl_quote_items_%d', $quote->getId());
        if (null === ($items = $this->cache->registry($registryKey))) {
            $items = $quote->getAllItems();
            $this->cache->register($registryKey, $items);
        }

        return $items;
    }
}
