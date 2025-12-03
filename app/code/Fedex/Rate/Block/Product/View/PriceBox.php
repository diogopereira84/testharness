<?php
/**
 * @category  Fedex
 * @package   Fedex_Rate
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\Rate\Block\Product\View;

use Magento\Catalog\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Catalog\Model\Config as CatalogConfig;

class PriceBox extends Template
{
    /**
     * @param Context $context
     * @param CatalogConfig $catalogConfig
     * @param array $data
     */
    public function __construct(
        private readonly Context       $context,
        private readonly CatalogConfig $catalogConfig,
        private readonly Data          $catalogData,
        array                          $data = []
    )
    {
        parent::__construct($this->context, $data);
    }

    /**
     * Get Catalog Config
     *
     * @return CatalogConfig
     */
    public function getCatalogConfig(): CatalogConfig
    {
        return $this->catalogConfig;
    }

    /**
     * Get Product Type
     *
     * @return array|string|null
     */
    public function getProductType(): array|string|null
    {
        $product = $this->catalogData->getProduct();

        if ($product) {
            return $product->getTypeId();
        }

        return null;
    }
}
