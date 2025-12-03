<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Block\Product\ProductList;

use Fedex\Catalog\Model\Config;
use Magento\Catalog\Block\Product\ProductList\Related;
use Magento\Catalog\Model\Product;

class RelatedPlugin
{
    /**
     * @param Config $config
     */
    public function __construct(
        protected Config $config
    ){}

    /**
     * @param Related $subject
     * @param Product $product
     * @param callable $proceed
     * @return string
     */
    public function aroundGetProductPrice(Related $subject, callable $proceed, Product $product)
    {
        if ($this->config->getTigerDisplayUnitCost3P1PProducts() && $subject->getType() == 'related'
            && $product->getUnitCost()) {
            return $subject->getProductPriceHtml(
                $product,
                \Fedex\Catalog\Pricing\Price\UnitCost::PRICE_CODE,
                \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST
            );
        }
        elseif ($this->config->getTigerDisplayUnitCost3P1PProducts() && $subject->getType() == 'related') {
            $finalPrice = $proceed($product);
            $priceContainerClass = 'price-container price-final_price';
            if (str_contains($finalPrice, $priceContainerClass)) {
                return str_replace($priceContainerClass, $priceContainerClass.' show-each', $finalPrice);
            }
        }

        return $proceed($product);
    }

}
