<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\TypeFactory;

class AddCustomerCanvasAttribute implements DataPatchInterface
{
    private $moduleDataSetup;
    private $eavSetupFactory;
    private $entityTypeFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        TypeFactory $entityTypeFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->entityTypeFactory = $entityTypeFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Add the new attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'is_customer_canvas',
            [
                'type' => 'int',
                'label' => 'Customer Canvas',
                'input' => 'boolean',
                'required' => false,
                'sort_order' => 27,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'user_defined' => true,
                'default' => 0,
                'searchable' => true,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => '',
                'group' => 'General',
                'mirakl_is_exportable'=> false,
            ]
        );

        // Update label for existing attribute
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'has_canva_design',
            'frontend_label',
            'Canva Design Templates'
        );

        // Get attribute ID for assignment
        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'is_customer_canvas');

        // Load all product attribute sets
        $entityType = $this->entityTypeFactory->create()->loadByCode(Product::ENTITY);
        $defaultAttributeSetId = $entityType->getDefaultAttributeSetId();
        $attributeSetCollection = $eavSetup->getAllAttributeSetIds(Product::ENTITY);

        foreach ($attributeSetCollection as $attributeSetId) {
            if ($attributeSetId == $defaultAttributeSetId) {
                continue; // Skip default set
            }

            $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'General');
            $eavSetup->addAttributeToSet(Product::ENTITY, $attributeSetId, $groupId, $attributeId);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
