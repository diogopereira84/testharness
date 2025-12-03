<?php

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
class AddProductEngineAttributes implements DataPatchInterface, PatchRevertableInterface
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

        $this->createFedexAttributeSet($eavSetup);
        $this->createFedexAttributesGroup($eavSetup);
        $this->addProductIdAttribute($eavSetup);
        $this->addPresetIdAttribute($eavSetup);
        $this->addQtyAttribute($eavSetup);
        $this->addVisibleAttributesAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $defaultAttrSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'FXOPrintProducts');

        $eavSetup->removeAttributeGroup(Product::ENTITY, $defaultAttrSetId, 'Product Engine Attributes');
        $eavSetup->removeAttributeSet(Product::ENTITY, 'FXOPrintProducts');
        $eavSetup->removeAttribute(Product::ENTITY, 'product_id');
        $eavSetup->removeAttribute(Product::ENTITY, 'preset_id');
        $eavSetup->removeAttribute(Product::ENTITY, 'quantity');
        $eavSetup->removeAttribute(Product::ENTITY, 'visible_attributes');

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

    private function createFedexAttributeSet(EavSetup $eavSetup)
    {
        $eavSetup->addAttributeSet(Product::ENTITY, 'FXOPrintProducts');
    }

    private function createFedexAttributesGroup(EavSetup $eavSetup)
    {
        $defaultAttrSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'FXOPrintProducts');
        $eavSetup->addAttributeGroup(
            Product::ENTITY,
            $defaultAttrSetId,
            'Product Engine Attributes',
            13
        );
        $eavSetup->updateAttributeGroup(Product::ENTITY, $defaultAttrSetId, 'Product Engine Attributes', 'tab_group_code', 'basic');
    }

    private function addProductIdAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'product_id',
            [
                'type' => 'varchar',
                'label' => 'Product ID',
                'input' => 'text',
                'required' => true,
                'sort_order' => '5',
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

    private function addPresetIdAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'preset_id',
            [
                'type' => 'varchar',
                'label' => 'Preset ID',
                'input' => 'text',
                'required' => false,
                'sort_order' => '10',
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

    private function addVisibleAttributesAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'visible_attributes',
            [
                'type' => 'text',
                'label' => 'Visible Attributes',
                'note' => __('Maximum number of options: 6. More than that and the last selected option will not be shown.'),
                'input' => 'multiselect',
                'required' => true,
                'sort_order' => '15',
                'source' => \Fedex\ProductEngine\Model\Entity\Attribute\Source\MultiselectAttributes::class,
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => '',
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

    private function addQtyAttribute(EavSetup $eavSetup)
    {

        $eavSetup->addAttribute(
            Product::ENTITY,
            'quantity',
            [
                'type' => 'text',
                'label' => 'Quantity',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '20',
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
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
        $quantityAttrId = $eavSetup->getAttributeId(Product::ENTITY,'quantity');
        $qtyOptions = ['attribute_id' => $quantityAttrId, 'values' => [
            0 => '1',
            1 => '5',
            2 => '10',
            3 => '50',
            4 => '100',
        ]];
        $eavSetup->addAttributeOption($qtyOptions);
    }
}
