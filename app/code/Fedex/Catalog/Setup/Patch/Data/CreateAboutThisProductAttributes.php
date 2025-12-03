<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product\Attribute\Frontend\Image as ImageFrontendModel;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class CreateAboutThisProductAttributes implements DataPatchInterface
{
    /**
     * CreateAboutThisProductAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private Config $eavConfig
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Create attribute group
        $this->createFedexAttributesGroup($eavSetup);

        // Define text type attributes to create
        $textAttributes = [
            'product_info' => ['label' => 'Product Info', 'is_wysiwyg_enabled' => 1, 'is_pagebuilder_enabled' => 0, 'group' => 'About The Product Attributes'],
            'product_ideas' => ['label' => 'Product Ideas', 'is_wysiwyg_enabled' => 1, 'is_pagebuilder_enabled' => 0, 'group' => 'About The Product Attributes'],
            'product_faqs' => ['label' => 'Faqs', 'is_wysiwyg_enabled' => 1, 'is_pagebuilder_enabled' => 1, 'group' => 'About The Product Attributes'],
            'product_options' => ['label' => 'Product Options', 'is_wysiwyg_enabled' => 1, 'is_pagebuilder_enabled' => 1, 'group' => 'About The Product Attributes'],
            'shipping_estimator_content_new' => ['label' => 'Shipping Estimate (New)', 'is_wysiwyg_enabled' => 1, 'is_pagebuilder_enabled' => 1, 'group' => 'Content'],
            'shipping_estimator_content_alert_new' => ['label' => ' Specific Product Information (New)', 'is_wysiwyg_enabled' => 1, 'is_pagebuilder_enabled' => 0, 'group' => 'Content'],
        ];

        // Define image type attributes to create
        $imageAttributes = [
            'product_info_image' => ['label' => 'Product Info'],
            'product_ideas_image' => ['label' => 'Product Ideas']
        ];

        foreach ($textAttributes as $attributeCode => $attributeInfo) {

            $attribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            if (!$attribute || !$attribute->getId()) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    [
                        'group' => $attributeInfo['group'],
                        'type' => 'text',
                        'label' => $attributeInfo['label'],
                        'input' => 'textarea',
                        'class' => '',
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => null,
                        'searchable' => true,
                        'filterable' => false,
                        'comparable' => true,
                        'visible_on_front' => false,
                        'used_in_product_listing' => false,
                        'unique' => false,
                        'is_html_allowed_on_front' => true,
                        'is_wysiwyg_enabled' => false,
                        'mirakl_is_exportable' => true,
                        'is_pagebuilder_enabled' => false
                    ]
                );

                $eavSetup->updateAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    'is_wysiwyg_enabled',
                    $attributeInfo['is_wysiwyg_enabled']
                );

                $eavSetup->updateAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    'mirakl_is_exportable',
                    1
                );

                $eavSetup->updateAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    'is_pagebuilder_enabled',
                    $attributeInfo['is_pagebuilder_enabled']
                );
            }
        }

        foreach ($imageAttributes as $attributeCode => $attributeInfo) {

            $attribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            if (!$attribute || !$attribute->getId()) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    [
                        'type' => 'varchar',
                        'label' => $attributeInfo['label'],
                        'input' => 'media_image',
                        'frontend' => ImageFrontendModel::class,
                        'required' => false,
                        'visible' => true,
                        'group' => "image-management",
                        'visible_on_front' => false,
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'is_used_in_grid' => false,
                        'is_visible_in_grid' => false,
                        'is_filterable_in_grid' => false,
                        'used_for_promo_rules' => false,
                        'used_in_product_listing' => false,
                        'user_defined' => true
                    ]
                );

                $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
                $eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    $attributeSetId,
                    'image-management',
                    $attribute->getId(),
                    10
                );
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    private function createFedexAttributesGroup(EavSetup $eavSetup)
    {
        $defaultAttrSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'FXOPrintProducts');
        $eavSetup->addAttributeGroup(
            Product::ENTITY,
            $defaultAttrSetId,
            'About The Product Attributes',
            3
        );
        $eavSetup->updateAttributeGroup(Product::ENTITY, $defaultAttrSetId, 'About The Product Attributes', 'tab_group_code', 'basic');
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
