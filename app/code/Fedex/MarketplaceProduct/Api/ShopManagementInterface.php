<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Api;

use Fedex\MarketplaceProduct\Api\Data\ShopInterface;
use \Magento\Catalog\Model\Product;

interface ShopManagementInterface
{
    /**
     * Get Shop by product
     *
     * @param Product $product
     * @return ShopInterface
     */
    public function getShopByProduct(Product $product): ShopInterface;
}
