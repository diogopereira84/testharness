<?php
/**
 * Interface ContextCheckerInterface
 *
 * Defines methods for checking if the current context is a product page and if a product is configurable.
 *
 * @category     Fedex
 * @package      Fedex_Catalog
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Api;

use Magento\Catalog\Api\Data\ProductInterface;

interface ContextCheckerInterface
{
    public const CATALOG_PRODUCT_VIEW = 'catalog_product_view';

    /**
     * Check if the current context is a product page
     *
     * @return bool
     */
    public function isProductPage(): bool;

    /**
     * Check if the product is configurable
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isConfigurableProduct(ProductInterface $product): bool;
}