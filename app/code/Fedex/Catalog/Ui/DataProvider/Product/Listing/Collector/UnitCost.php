<?php
declare(strict_types=1);

namespace Fedex\Catalog\Ui\DataProvider\Product\Listing\Collector;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductRenderExtensionFactory;
use Magento\Catalog\Api\Data\ProductRenderInterface;
use Magento\Catalog\Ui\DataProvider\Product\ProductRenderCollectorInterface;

class UnitCost implements ProductRenderCollectorInterface
{
    const UNIT_COST = "unit_cost";
    const BASE_QUANTITY = "base_quantity";
    const BASE_PRICE = "base_price";

    /**
     * UnitCost constructor.
     * @param ProductRenderExtensionFactory $productRenderExtensionFactory
     */
    public function __construct(
        private ProductRenderExtensionFactory $productRenderExtensionFactory
    ) {}

    /**
     * @inheritdoc
     */
    public function collect(ProductInterface $product, ProductRenderInterface $productRender)
    {
        $extensionAttributes = $productRender->getExtensionAttributes();

        if (!$extensionAttributes) {
            $extensionAttributes = $this->productRenderExtensionFactory->create();
        }

        if($product->getUnitCost()) {
            $extensionAttributes->setUnitCost($product->getUnitCost());
        }

        if($product->getBaseQuantity()) {
            $extensionAttributes->setBaseQuantity($product->getBaseQuantity());
        }

        if($product->getBasePrice()) {
            $extensionAttributes->setBasePrice($product->getBasePrice());
        }

        $productRender->setExtensionAttributes($extensionAttributes);
    }
}
