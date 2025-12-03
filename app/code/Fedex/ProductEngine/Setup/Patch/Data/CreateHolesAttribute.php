<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Setup\Patch\Data;

use Fedex\ProductEngine\Setup\AddOptionToAttribute;
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
class CreateHolesAttribute implements DataPatchInterface, PatchRevertableInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private AddOptionToAttribute $addOptionToAttribute
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->addPrintColorAttribute($eavSetup);
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'holes');
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    private function addPrintColorAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'holes',
            [
                'type' => 'varchar',
                'label' => 'Holes',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '240',
                'position' => '240',
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
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
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true
            ]
        );
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'holes');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'None', 'choice_id' => '1448999902070'],
                ['value' => 'Holes', 'choice_id' => '1652978102614']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }
}
