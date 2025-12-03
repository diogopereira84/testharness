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
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class AddPeProductIdAttribute implements DataPatchInterface, PatchRevertableInterface
{
    const PRODUCT_ID_LABEL = 'Product ID';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetup $eavSetupFactory
     * @param Attribute $eavAttribute
     */
    public function __construct(
        private ModuleDataSetupInterface    $moduleDataSetup,
        private EavSetup                    $eavSetupFactory,
        private Attribute                   $eavAttribute
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->revertProductIdAttributeChange();

        $this->addPeProductIdAttribute();

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->revertProductIdAttributeChange();

        $this->removePeProductIdAttribute();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases() : array
    {
        return [];
    }

    public static function getDependencies() : array
    {
        return [AddProductEngineAttributes::class];
    }

    /**
     * @param EavSetup $eavSetup
     * @return void
     */
    private function revertProductIdAttributeChange()
    {
        $peProductId = $this->eavAttribute->getIdByCode(Product::ENTITY, 'pe_product_id');
        if (!empty($peProductId)) {
            $this->eavSetupFactory->updateAttribute(
                Product::ENTITY,
                $peProductId,
                'attribute_code',
                'product_id'
            );
            $this->eavSetupFactory->updateAttribute(
                Product::ENTITY,
                $peProductId,
                'frontend_label',
                self::PRODUCT_ID_LABEL
            );
        }
    }

    /**
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addPeProductIdAttribute()
    {
        $this->eavSetupFactory->addAttribute(
            Product::ENTITY,
            'pe_product_id',
            [
                'type' => 'varchar',
                'label' => self::PRODUCT_ID_LABEL,
                'input' => 'text',
                'required' => true,
                'sort_order' => '1',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => true,
                'visible_in_advanced_search' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'group' => 'Product Engine Attributes',
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false
            ]
        );
    }

    /**
     * @param EavSetup $eavSetup
     * @return void
     */
    private function removePeProductIdAttribute()
    {
        $this->eavSetupFactory->removeAttribute(
            Product::ENTITY,
            'pe_product_id',
        );
    }
}
