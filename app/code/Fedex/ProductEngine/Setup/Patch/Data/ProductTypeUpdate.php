<?php
/**
 * @category Fedex
 * @package Fedex _ModuleName
 * @copyright (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class ProductTypeUpdate implements DataPatchInterface, PatchRevertableInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->updateAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->removeAttributeUpdate($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases() : array
    {
        return [];
    }

    public static function getDependencies() : array
    {
        return [];
    }

    private function updateAttribute(EavSetup $eavSetup)
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'product_type');
        if($attrId) {
            $eavSetup->updateAttribute(Product::ENTITY, 'product_type', 'attribute_code', 'fxo_product_type');
        }
    }

    private function removeAttributeUpdate(EavSetup $eavSetup)
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'fxo_product_type');
        if($attrId) {
            $eavSetup->updateAttribute(Product::ENTITY, 'fxo_product_type', 'attribute_code', 'product_type');
        }
    }
}
