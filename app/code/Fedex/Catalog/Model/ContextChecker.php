<?php
/**
 * @category    Fedex
 * @package     Fedex_Catalog
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Catalog\Model;

use Fedex\Catalog\Api\ContextCheckerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\RequestInterface;

class ContextChecker implements ContextCheckerInterface
{
    public function __construct(
        private readonly RequestInterface $request,
    ) {
    }

    /**
     * @return bool
     */
    public function isProductPage(): bool
    {
        return $this->request->getFullActionName() === static::CATALOG_PRODUCT_VIEW;
    }

    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isConfigurableProduct(ProductInterface $product): bool
    {
        return $product->getTypeId() === ConfigurableType::TYPE_CODE;
    }
}