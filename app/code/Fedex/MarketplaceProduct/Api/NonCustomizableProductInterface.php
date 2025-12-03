<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;

interface NonCustomizableProductInterface
{
    /**
     * Checks if marketplace CBB toggle is enabled
     * 
     * @return bool
     */
    public function isMktCbbEnabled(): bool;

    /**
     * Checks if marketplace Essendant toggle is enabled
     * 
     * @return bool
     */
    public function isEssendantEnabled(): bool;

    /**
     * Checks if D213961 toggle is enabled
     * 
     * @return bool
     */
    public function isD213961Enabled(): bool;

    /**
     * Checks if D217169 toggle is enabled
     * 
     * @return bool
     */
    public function isD217169Enabled(): bool;

    /**
     * Checks if there are any third party with punchout disabled products in the cart
     * 
     * @return bool
     */
    public function isThirdPartyOnlyCartWithAnyPunchoutDisabled();

    /**
     * Return minQty, maxQty and punchoutEnabled to be used in the template
     * 
     * @param mixed $offer
     * @param Product $product
     * @return array [minQty, maxQty, punchoutDisabled]
     */
    public function getMinMaxPunchoutInfo($offer, $product);

    /**
     * Return Punchout status on the item (false = disabled | true = enabled)
     * Punchout determines if the item is a third party product and can be updated
     * 
     * @param Item $item
     * @return bool
     */
    public function isProductPunchoutDisabledForThirdPartyItem(Item $item);

    /**
     * Validate Product Max Qty
     * 
     * @param Product|ProductInterface|int $product
     * @param int $itemQty
     * @return string Error message if validation fails, empty string otherwise
     */
    public function validateProductMaxQty($product, $itemQty);

    /**
     * Get product image URL
     * 
     * @param Product $product
     * @param string $imageId
     * @return string
     */
    public function getProductImage($product, $imageId = 'product_small_image');

    /**
     * Check if product is FXO non-customizable product
     * 
     * @param ProductInterface|null $product
     * @return bool
     */
    public function checkIfNonCustomizableProduct($product): bool;
} 