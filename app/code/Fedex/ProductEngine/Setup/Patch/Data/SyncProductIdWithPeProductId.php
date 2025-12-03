<?php
/**
 * @category    Fedex
 * @package     Fedex_ProductEngine
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class SyncProductIdWithPeProductId implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductCollection $productCollection
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private ProductCollection        $productCollection
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->syncProductIdWithPeProductId();

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @return void
     */
    public function revert()
    {
    }

    public function getAliases() : array
    {
        return [];
    }

    public static function getDependencies() : array
    {
        return [
            AddProductEngineAttributes::class,
            AddPeProductIdAttribute::class
        ];
    }

    private function syncProductIdWithPeProductId()
    {
        $this->productCollection->addAttributeToSelect(['product_id', 'pe_product_id']);
        $products = $this->productCollection->getItems();
        /** @var Product $product */
        foreach ($products as $product) {
            if ($product->getProductId()) {
                $product->setPeProductId($product->getProductId());
                $product->getResource()->saveAttribute($product, 'pe_product_id');
            }
        }
    }
}
