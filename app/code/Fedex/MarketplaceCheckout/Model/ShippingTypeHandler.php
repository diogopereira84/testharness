<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\MarketplaceCheckout\Api\ShippingTypeHandlerInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\FrontendDemo\Helper\Quote\Item as QuoteItemHelper;
use Mirakl\FrontendDemo\Model\Quote\Updater as QuoteUpdater;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;

class ShippingTypeHandler implements ShippingTypeHandlerInterface
{
    /**
     * @param QuoteItemHelper $quoteItemHelper
     * @param QuoteUpdater $quoteUpdater
     */
    public function __construct(
        private QuoteItemHelper $quoteItemHelper,
        private QuoteUpdater    $quoteUpdater
    ) {
    }

    /**
     * Retrieves the selected shipping type for the specified quote item.
     *
     * @param QuoteItem $item
     * @return ShippingFeeType
     */
    public function getItemSelectedShippingType(QuoteItem $item): ShippingFeeType
    {
        $shippingTypeCode = $item->getMiraklShippingType();
        return $shippingTypeCode
            ? $this->quoteUpdater->getItemShippingTypeByCode($item, $shippingTypeCode)
            : $this->quoteUpdater->getItemSelectedShippingType($item);
    }

    /**
     * Retrieves the available shipping types for the specified quote item.
     *
     * @param QuoteItem $item
     * @param $shippingAddress
     * @return ShippingFeeTypeCollection
     */
    public function getItemShippingTypes(QuoteItem $item, $shippingAddress = null): ShippingFeeTypeCollection
    {
        return $this->quoteItemHelper->getItemShippingTypes($item, $shippingAddress);
    }
}