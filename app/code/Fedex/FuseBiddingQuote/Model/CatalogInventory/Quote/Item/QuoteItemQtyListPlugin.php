<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Model\CatalogInventory\Quote\Item;

use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList;
use Magento\Quote\Model\ResourceModel\Quote\Item\Option\CollectionFactory as ItemOptionCollectionFactory;

class QuoteItemQtyListPlugin
{
    /**
     * Product qty's checked
     * data is valid if you check quote item qty and use singleton instance
     *
     * @var array
     * @phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
     */
    protected $_checkedQuoteItems = [];

    /**
     * @var ItemOptionCollectionFactory
     * @phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
     */
    protected $_itemOptionCollectionFactory;

    /**
     * @param ItemOptionCollectionFactory $itemOptionCollectionFactory
     */
    public function __construct(ItemOptionCollectionFactory $itemOptionCollectionFactory)
    {
        $this->_itemOptionCollectionFactory = $itemOptionCollectionFactory;
    }

    /**
     * Do not add Mirakl offers to qty cache in order to separate offers from operator products in method
     * @see \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList::getQty()
     *
     * Problem was:
     * - a Mirakl offer is added to cart with qty = 1
     * - we try to add the same product but the operator offer with qty = 1
     * - Magento checks for qty = 2 before adding the product to the cart when we should exclude the offer qty
     *
     * @param QuoteItemQtyList $subject
     * @param \Closure         $proceed
     * @param int              $productId
     * @param int              $quoteItemId
     * @param int              $quoteId
     * @param int              $itemQty
     * @return int
     */
    public function aroundGetQty(
        QuoteItemQtyList $subject,
        \Closure $proceed,
        $productId,
        $quoteItemId,
        $quoteId,
        $itemQty
    ) {
        if ($quoteItemId) {
            $collection = $this->_itemOptionCollectionFactory->create();
            $collection->addItemFilter($quoteItemId)
                ->addFieldToFilter('code', 'info_buyRequest');
            if ($collection->count()) {
                /** @var \Magento\Quote\Model\Quote\Item\Option $itemOption */
                $itemOption = $collection->getFirstItem();
                $info = $this->decodeOptionValue($itemOption->getValue());
                if (isset($info['offer_id'])) {
                    $productId .= '|' . $info['offer_id'];
                } else {
                    $productId .= '|operator';
                }
            }
        }

        $qty = $itemQty;
        if (
            isset($this->_checkedQuoteItems[$quoteId][$productId]['qty']) &&
            !in_array($quoteItemId, $this->_checkedQuoteItems[$quoteId][$productId]['items'])
        ) {
            $qty += $this->_checkedQuoteItems[$quoteId][$productId]['qty'];
        }

        $this->_checkedQuoteItems[$quoteId][$productId]['qty'] = $qty;
        $this->_checkedQuoteItems[$quoteId][$productId]['items'][] = $quoteItemId;

        return $qty;
    }

    /**
     * @param string $str
     * @return array
     */
    private function decodeOptionValue($str)
    {
        $value = json_decode((string) $str, true);
        if (json_last_error() == JSON_ERROR_NONE || !$str) {
            return $value;
        }

        return unserialize($str); // phpcs:ignore
    }
}
