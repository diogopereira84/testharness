<?php
/**
 * @category    Fedex
 * @package     Fedex_AllPrintProducts
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\AllPrintProducts\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        private CategorySetupFactory $categorySetupFactory
    ) {
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $attributeCode = 'product_detail_page_additional_information';
            $categorySetup->updateAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $attributeCode,
                'group',
                'Display Settings'
            );

            $categorySetup->updateAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $attributeCode,
                'sort_order',
                160
            );

            $categorySetup->removeAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'product_additional_information'
            );
        }

        $attributes = [
            'product_listing_heading' => [
                'type' => 'varchar',
                'label' => 'Product Listing Heading',
                'input' => 'text',
                'required' => false,
                'sort_order' => 100,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Display Settings',
            ],
            'product_listing_sub_heading' => [
                'type' => 'varchar',
                'label' => 'Product Listing Sub-heading',
                'input' => 'text',
                'required' => false,
                'sort_order' => 110,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Display Settings',
            ],
            'product_listing_banner_text' => [
                'type' => 'varchar',
                'label' => 'Product Listing Banner Text',
                'input' => 'text',
                'required' => false,
                'sort_order' => 120,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Display Settings',
            ],
            'product_listing_banner_icon' => [
                'type' => 'varchar',
                'label' => 'Product Listing Banner Icon',
                'input' => 'image',
                'required' => false,
                'sort_order' => 150,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Display Settings',
                'backend' => 'Magento\Catalog\Model\Category\Attribute\Backend\Image',
            ],
            'product_detail_page_additional_information' => [
                'type' => 'varchar',
                'label' => 'Product Detail Page Additional Information',
                'input' => 'text',
                'required' => false,
                'sort_order' => 140,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Custom Design',
            ]
        ];

        foreach ($attributes as $attributeCode => $attributeData) {
            $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $attributeCode, $attributeData);
        }
    }
}
