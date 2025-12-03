<?php
/**
 * Interface ShippingMethodsInterface
 *
 * Defines methods for handling shipping methods and related operations in the Fedex MarketplaceCheckout module.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
interface ShippingTypeHandlerInterface
{
    /**
     * Retrieves the selected shipping type for the specified quote item.
     *
     * @param QuoteItem $item
     * @return ShippingFeeType
     */
    public function getItemSelectedShippingType(QuoteItem $item): ShippingFeeType;

    /**
     * Retrieves the available shipping types for the specified quote item.
     *
     * @param QuoteItem $item
     * @param AddressInterface|null $shippingAddress
     * @return ShippingFeeTypeCollection
     */
    public function getItemShippingTypes(QuoteItem $item, AddressInterface $shippingAddress = null): ShippingFeeTypeCollection;
}