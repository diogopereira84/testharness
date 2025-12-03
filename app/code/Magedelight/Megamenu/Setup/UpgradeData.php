<?php

namespace Magedelight\Megamenu\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magedelight\Megamenu\Model\Category\Attribute\Source\Width;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

class UpgradeData implements UpgradeDataInterface
{

     /**
      * Category setup factory
      *
      * @var CategorySetupFactory
      */
    private $categorySetupFactory;
    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(CategorySetupFactory $categorySetupFactory)
    {
         $this->categorySetupFactory = $categorySetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.13', '<')) {
            $installer = $setup;
            $installer->startSetup();
            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

            $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'md_submenu_width',
                [
                    'type' => 'varchar',
                    'label' => 'Submenu Width',
                    'input' => 'select',
                    'sort_order' => 10,
                    'source' => Width::class,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'Md Mega Menu',
                ]
            );

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'md_column_count',
                [
                    'type' => 'text',
                    'label' => 'Number of Columns with Subcategories',
                    'input' => 'text',
                    'sort_order' => 20,
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'Md Mega Menu',
                ]
            );

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'md_label',
                [
                    'type' => 'text',
                    'label' => 'Menu Label Text',
                    'input' => 'text',
                    'sort_order' => 30,
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'Md Mega Menu',
                ]
            );

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'md_label_text_color',
                [
                    'type' => 'text',
                    'label' => 'Text Color (hex)',
                    'input' => 'text',
                    'sort_order' => 40,
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'Md Mega Menu',
                ]
            );

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'md_label_background_color',
                [
                    'type' => 'text',
                    'label' => 'Background Color (hex)',
                    'input' => 'text',
                    'sort_order' => 50,
                    'source' => '',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'group' => 'Md Mega Menu',
                ]
            );

            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'md_category_editor',
                [
                    'type' => 'text',
                    'label' => 'Editor',
                    'input' => 'textarea',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'wysiwyg_enabled' => true,
                    'is_html_allowed_on_front' => true,
                    'required' => false,
                    'sort_order' => 60,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Md Mega Menu',
                ]
            );

            $installer->endSetup();
        }
    }
}
