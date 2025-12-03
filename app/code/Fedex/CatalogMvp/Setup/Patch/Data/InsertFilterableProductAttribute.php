<?php

/**
 * @copyright Copyright (c) 2024 Fedex.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InsertFilterableProductAttribute implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }
   /**
     * apply function
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $textAttributes = [
            'product_created_date' => ['label' => 'Create Date', 'type' => 'datetime', 'input' => 'date','sort_order'=> '500'],
            'product_updated_date' => ['label' => 'Update Date', 'type' => 'datetime', 'input' => 'date','sort_order'=> '501'],
            'product_attribute_sets_id' => ['label' => 'Attribute Set Id', 'type' => 'int', 'input' => 'text', 'sort_order'=> '502']
        ];

        foreach ($textAttributes as $attributeCode => $attributeInfo) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                [
                    'group' => 'General',
                    'type' => $attributeInfo['type'],
                    'label' => $attributeInfo['label'],
                    'input' => $attributeInfo['input'],
                    'class' => '',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => null,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'is_html_allowed_on_front' => true,
                    'is_wysiwyg_enabled' => false,
                    'is_pagebuilder_enabled' => false,
                    'apply_to' => 'simple,grouped,bundle,configurable,virtual',
                    'sort_order' => $attributeInfo['sort_order']
                ]
            );

        }

        $this->moduleDataSetup->endSetup();
    }
    /**
     * getAliases function
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }
    /**
     * getDependencies function
     */
    public static function getDependencies()
    {
        return [];
    }
}
