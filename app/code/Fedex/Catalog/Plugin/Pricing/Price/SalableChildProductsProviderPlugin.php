<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author   Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2025 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Pricing\Price;

use Fedex\Catalog\Model\ContextChecker;
use Fedex\Catalog\Model\FilterSalableChildren;
use Fedex\Catalog\Model\ToggleConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider;

class SalableChildProductsProviderPlugin
{
    public function __construct(
        private ToggleConfig          $config,
        private ContextChecker        $checker,
        private FilterSalableChildren $childFilter
    ) {
    }

    /**
     * After plugin to ensure all child products are loaded for price calculation,
     * but only on the Product Detail Page (PDP).
     *
     * @param LowestPriceOptionsProvider $subject
     * @param array $result
     * @param ProductInterface $product
     * @return array
     */
    public function afterGetProducts(
        LowestPriceOptionsProvider $subject,
        array                      $result,
        ProductInterface           $product
    ): array {
        if ($this->shouldSkipProcessing($product)) {
            return $result;
        }

        $children = $this->getFilteredChildren($product);
        return !empty($children) ? $children : $result;
    }

    private function shouldSkipProcessing(ProductInterface $product): bool
    {
        return !$this->checker->isProductPage()
            || !$this->checker->isConfigurableProduct($product)
            || !$this->config->isEssendantToggleEnabled();
    }

    private function getFilteredChildren(ProductInterface $product): array
    {
        return $this->childFilter->filter($product->getTypeInstance()->getUsedProducts($product));
    }
}