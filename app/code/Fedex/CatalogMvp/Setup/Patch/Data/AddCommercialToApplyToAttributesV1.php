<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;

class AddCommercialToApplyToAttributesV1 implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {}

   
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributes = [
            'price', 'special_price', 'special_from_date', 'special_to_date', 'cost', 'weight',
            'manufacturer', 'tier_price', 'color', 'minimal_price', 'published', 'country_of_manufacture',
            'msrp', 'msrp_display_actual_price_type', 'tax_class_id', 'gift_wrapping_available',
            'gift_wrapping_price', 'customizable', 'mirakl_shop_ids', 'mirakl_offer_state_ids',
            'mirakl_sync', 'mirakl_category_id', 'mirakl_authorized_shop_ids', 'mirakl_shops_skus',
            'mirakl_mcm_product_id', 'mirakl_mcm_is_operator_master', 'mirakl_mcm_variant_group_code',
            'production_days', 'shape', 'cta_value', 'unit_of_measure', 'category_punchout',
            'weight_unit', 'upload_file_search_action', 'customize_search_action',
            'product_created_date', 'product_updated_date', 'product_attribute_sets_id',
            'unit_cost', 'base_quantity', 'base_price'
        ];

        foreach ($attributes as $attributeCode) {
            $attribute = $eavSetup->getAttribute(Product::ENTITY, $attributeCode);
            if (!empty($attribute['apply_to'])) {
                $applyTo = array_filter(array_unique(explode(',', $attribute['apply_to'])));
                if (!in_array('commercial', $applyTo, true)) {
                    // Add 'commercial' at the end of the list
                    $applyTo[] = 'commercial';

                    $eavSetup->updateAttribute(
                        Product::ENTITY,
                        $attributeCode,
                        'apply_to',
                        implode(',', $applyTo)
                    );
                }
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
