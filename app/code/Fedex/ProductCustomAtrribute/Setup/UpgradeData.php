<?php

namespace Fedex\ProductCustomAtrribute\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    public function __construct(
        private EavSetupFactory $eavSetupFactory
    )
    {
    }
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         *checking the current version of the module
         *this function is implemented from  ModuleContextInterface
         */
        if(version_compare((string)$context->getVersion(), '1.0.1', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'marketing_description',
                [
                    'type' => 'text',
                    'label' => 'Marketing Description',
                    'input' => 'textarea',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'searchable' => true,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'is_wysiwyg_enabled' => true,
                    'is_html_allowed_on_front' => true,
                    'unique' => false,
                    'group' => 'Content',
                    "default" => "",
                    "class" => "",
                    "note" => ""
                ]
            );
            $eavSetup->updateAttribute(\Magento\Catalog\Model\Product::ENTITY,
                'marketing_description', 'is_wysiwyg_enabled', 1);
        }
    }
}
