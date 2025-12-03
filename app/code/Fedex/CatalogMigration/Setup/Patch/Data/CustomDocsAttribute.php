<?php
declare (strict_types = 1);

namespace Fedex\CatalogMigration\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CustomDocsAttribute implements DataPatchInterface
{

    /**
     * @param ModuleDataSetupInterface
     * @param EavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'customization_data',
            [
                'type' => 'text',
                'label' => 'Custom Document Setup Data',
                'input' => 'textarea',
                'source' => null,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => null,
                'group' => 'General',
                'sort_order' => 90,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => '',
            ]
        );

        $attributeSetIdToKeep = $eavSetup->getAttributeSetId(
            \Magento\Catalog\Model\Product::ENTITY,
            'PrintOnDemand'
        );
        $attributeId = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            'customization_data'
        );

        $setup->getConnection()->delete(
            $setup->getTable('eav_entity_attribute'),
            [
                'attribute_id = ?' => $attributeId,
                'attribute_set_id != ?' => $attributeSetIdToKeep,
            ]
        );

        // Customization_fields attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'customization_fields',
            [
                'type' => 'text',
                'label' => 'Custom Document Setup Fields',
                'input' => 'textarea',
                'source' => null,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => null,
                'group' => 'General',
                'sort_order' => 90,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => '',
            ]
        );

        $attributeSetIdToKeep = $eavSetup->getAttributeSetId(
            \Magento\Catalog\Model\Product::ENTITY,
            'PrintOnDemand'
        );
        $attributeId = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            'customization_fields'
        );

        $setup->getConnection()->delete(
            $setup->getTable('eav_entity_attribute'),
            [
                'attribute_id = ?' => $attributeId,
                'attribute_set_id != ?' => $attributeSetIdToKeep,
            ]
        );

        $this->moduleDataSetup->endSetup();

    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
