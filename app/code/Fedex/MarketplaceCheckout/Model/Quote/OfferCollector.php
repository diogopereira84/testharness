<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as QuoteItemOption;

class OfferCollector extends \Mirakl\Connector\Model\Quote\OfferCollector
{
    /**
     * This change allows to add multiple products with the same offer id at the cart
     *
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getItemsWithOffer(CartInterface $quote): array
    {
        $hash = $this->cache->getQuoteControlHash($quote);
        if ($cache = $this->cache->getCachedMethodResult(__METHOD__, $quote->getId(), $hash)) {
            return $cache;
        }

        $quoteItemsWithOffer = [];
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
            $quoteItemsWithOffer[$offer->getId()] = $quoteItem;
        }

        $this->cache->setCachedMethodResult(__METHOD__, $quote->getId(), $quoteItemsWithOffer, $hash);

        return $quoteItemsWithOffer;
    }
}
