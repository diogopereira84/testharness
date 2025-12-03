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
class ProductAttributes implements DataPatchInterface, PatchRevertableInterface
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

        $this->removeDumbyAttributes($eavSetup);

        $this->addPrintColorAttribute($eavSetup);
        $this->addLaminationAttribute($eavSetup);
        $this->addPrintsPerPageAttribute($eavSetup);
        $this->addPaperTypeAttribute($eavSetup);
        $this->addCuttingAttribute($eavSetup);
        $this->addBackBindingCoversAttribute($eavSetup);
        $this->addSidesAttribute($eavSetup);
        $this->addBinderSpineAttribute($eavSetup);
        $this->addCollationAttribute($eavSetup);
        $this->addFoldingAttribute($eavSetup);
        $this->addHolePunchingAttribute($eavSetup);
        $this->addFrontBindingCoversAttribute($eavSetup);
        $this->addPrintFirstPageonCoverAttribute($eavSetup);
        $this->addBindingAttribute($eavSetup);
        $this->addPaperSizeAttribute($eavSetup);
        $this->addOrientationAttribute($eavSetup);
        $this->addEnvelopeAttribute($eavSetup);
        $this->addGrommetsAttribute($eavSetup);
        $this->addMaterialTypeAttribute($eavSetup);
        $this->addDecalTypeAttribute($eavSetup);
        $this->addReadabilityAttribute($eavSetup);
        $this->addSizeAttribute($eavSetup);
        $this->addBleedsAttribute($eavSetup);
        $this->addImpositionAttribute($eavSetup);
        $this->addColorBlackWhiteAttribute($eavSetup);
        $this->addProductTypeAttribute($eavSetup);
        $this->addFrameAttribute($eavSetup);
        $this->addSignTypeAttribute($eavSetup);
        $this->addMountingAttribute($eavSetup);
        $this->addFinishingAttribute($eavSetup);
        $this->addLetteringHeightAttribute($eavSetup);
        $this->addLetteringFontAttribute($eavSetup);
        $this->addLetteringColorAttribute($eavSetup);
        $this->addTabsAttribute($eavSetup);
        $this->addFrontBackBindingCoversAttribute($eavSetup);
        $this->addDrillingAttribute($eavSetup);
        $this->addLedLightDisplayAttribute($eavSetup);
        $this->addBannerTypeAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'print_color');
        $eavSetup->removeAttribute(Product::ENTITY, 'lamination');
        $eavSetup->removeAttribute(Product::ENTITY, 'prints_per_page');
        $eavSetup->removeAttribute(Product::ENTITY, 'paper_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'cutting');
        $eavSetup->removeAttribute(Product::ENTITY, 'back_binding_covers');
        $eavSetup->removeAttribute(Product::ENTITY, 'sides');
        $eavSetup->removeAttribute(Product::ENTITY, 'binder_spine');
        $eavSetup->removeAttribute(Product::ENTITY, 'collation');
        $eavSetup->removeAttribute(Product::ENTITY, 'folding');
        $eavSetup->removeAttribute(Product::ENTITY, 'hole_punching');
        $eavSetup->removeAttribute(Product::ENTITY, 'front_binding_covers');
        $eavSetup->removeAttribute(Product::ENTITY, 'print_first_page_on_cover');
        $eavSetup->removeAttribute(Product::ENTITY, 'binding');
        $eavSetup->removeAttribute(Product::ENTITY, 'paper_size');
        $eavSetup->removeAttribute(Product::ENTITY, 'orientation');
        $eavSetup->removeAttribute(Product::ENTITY, 'envelope');
        $eavSetup->removeAttribute(Product::ENTITY, 'grommets');
        $eavSetup->removeAttribute(Product::ENTITY, 'material_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'decal_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'readability');
        $eavSetup->removeAttribute(Product::ENTITY, 'size');
        $eavSetup->removeAttribute(Product::ENTITY, 'bleeds');
        $eavSetup->removeAttribute(Product::ENTITY, 'imposition');
        $eavSetup->removeAttribute(Product::ENTITY, 'color_black_white');
        $eavSetup->removeAttribute(Product::ENTITY, 'product_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'frame');
        $eavSetup->removeAttribute(Product::ENTITY, 'sign_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'mounting');
        $eavSetup->removeAttribute(Product::ENTITY, 'finishing');
        $eavSetup->removeAttribute(Product::ENTITY, 'lettering_height');
        $eavSetup->removeAttribute(Product::ENTITY, 'lettering_font');
        $eavSetup->removeAttribute(Product::ENTITY, 'lettering_color');
        $eavSetup->removeAttribute(Product::ENTITY, 'tabs');
        $eavSetup->removeAttribute(Product::ENTITY, 'front_back_binding_covers');
        $eavSetup->removeAttribute(Product::ENTITY, 'drilling');
        $eavSetup->removeAttribute(Product::ENTITY, 'led_light_display');
        $eavSetup->removeAttribute(Product::ENTITY, 'banner_type');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    private function removeDumbyAttributes(EavSetup $eavSetup) {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'size');
        $eavSetup->removeAttribute(Product::ENTITY, 'paper_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'lamination');
        $eavSetup->removeAttribute(Product::ENTITY, 'mounting');
        $eavSetup->removeAttribute(Product::ENTITY, 'orientation');
        $eavSetup->removeAttribute(Product::ENTITY, 'paper_size');
        $eavSetup->removeAttribute(Product::ENTITY, 'print_color');
    }

    private function addPrintColorAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'print_color',
            [
                'type' => 'varchar',
                'label' => 'Print Color',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '50',
                'position' => '50',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'print_color');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Full Color', 'choice_id' => '1448988600611'],
                ['value' => 'First Page Color, remaining pages Black and White', 'choice_id' => '1448988601203'],
                ['value' => 'Black and White', 'choice_id' => '1448988600931'],
                ['value' => 'Black & White', 'choice_id' => '1448988600931']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addLaminationAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'lamination',
            [
                'type' => 'varchar',
                'label' => 'Lamination',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '55',
                'position' => '55',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'lamination');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Glossy with 1/4" border', 'choice_id' => '1448999458793'],
                ['value' => 'None', 'choice_id' => '1448999458409'],
                ['value' => 'Glossy with No Border', 'choice_id' => '1448999458617'],
                ['value' => 'Glossy', 'choice_id' => '1449001988858'],
                ['value' => 'Matte', 'choice_id' => '1449001989378'],
                ['value' => 'Textured', 'choice_id' => '1449002468658'],
                ['value' => 'Dry Erase', 'choice_id' => '1449002469250']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addPrintsPerPageAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'prints_per_page',
            [
                'type' => 'varchar',
                'label' => 'Prints Per Page',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '60',
                'position' => '60',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'prints_per_page');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'One', 'choice_id' => '1448990257151'],
                ['value' => 'Four', 'choice_id' => '1448999967979'],
                ['value' => 'Two', 'choice_id' => '1448999967802']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addPaperTypeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'paper_type',
            [
                'type' => 'varchar',
                'label' => 'Paper Type',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '65',
                'position' => '65',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'paper_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Antique Gray', 'choice_id' => '1448988900543'],
                ['value' => 'Laser Recycled(24 lb.)', 'choice_id' => '1448988665655'],
                ['value' => 'Tan', 'choice_id' => '1448989264697'],
                ['value' => '100% Recycled', 'choice_id' => '1448988666494'],
                ['value' => 'Laser(80 lb.)', 'choice_id' => '1448988677979'],
                ['value' => 'Ultra Bright White', 'choice_id' => '1448988908007'],
                ['value' => 'Canary', 'choice_id' => '1450369912077'],
                ['value' => 'Salmon', 'choice_id' => '1448988668414'],
                ['value' => 'Soft Pink', 'choice_id' => '1448988901783'],
                ['value' => 'Ultra Lemon', 'choice_id' => '1448988670926'],
                ['value' => 'Transparency', 'choice_id' => '1448989266353'],
                ['value' => 'Ultra Orange', 'choice_id' => '1448988671822'],
                ['value' => 'Natural', 'choice_id' => '1448988899424'],
                ['value' => 'Gloss Text', 'choice_id' => '1448988666879'],
                ['value' => '30% Recycled', 'choice_id' => '1448988666102'],
                ['value' => 'Green', 'choice_id' => '1450369968939'],
                ['value' => 'Water Resistant', 'choice_id' => '1559837164426'],
                ['value' => 'Laser(24 lb.)', 'choice_id' => '1448988661630'],
                ['value' => '110lb. Index', 'choice_id' => '1448988675190'],
                ['value' => 'Willow', 'choice_id' => '1448989263354'],
                ['value' => 'Laser(32 lb.)', 'choice_id' => '1448988664295'],
                ['value' => 'Red', 'choice_id' => '1448988672470'],
                ['value' => 'Ultra Fuchsia', 'choice_id' => '1448988672870'],
                ['value' => 'Sun Yellow', 'choice_id' => '1450369982338'],
                ['value' => 'Blue', 'choice_id' => '1450369954307'],
                ['value' => 'Laser (60 lb.)', 'choice_id' => '1448988665015'],
                ['value' => 'Sea Spray', 'choice_id' => '1450370097408'],
                ['value' => 'Bright Blue', 'choice_id' => '1448988673318'],
                ['value' => 'Orchid', 'choice_id' => '1448988668815'],
                ['value' => 'Ultra Lime', 'choice_id' => '1450370065770'],
                ['value' => 'Standard White', 'choice_id' => '1448988667238'],
                ['value' => 'Sand Stone', 'choice_id' => '1448988899767'],
                ['value' => 'Ivory', 'choice_id' => '1448988667606'],
                ['value' => 'Antique Parchment', 'choice_id' => '1448989264241'],
                ['value' => 'Ultra Bright White (Card)', 'choice_id' => '1448988674174'],
                ['value' => 'Gloss Cover', 'choice_id' => '1448988895624'],
                ['value' => 'Pure White(100% Cotton)', 'choice_id' => '1448988908744'],
                ['value' => 'Matte Cover (100 lb.)', 'choice_id' => '1535620925780'],
                ['value' => 'Gloss Cover (87 lb.)', 'choice_id' => '1448997301634'],
                ['value' => 'Glossy Cover (100 lb.)', 'choice_id' => '1535621259892'],
                ['value' => '3-part Carbonless', 'choice_id' => '1468255839467'],
                ['value' => 'Pastel Blue', 'choice_id' => '1448988669318'],
                ['value' => '2-part Carbonless', 'choice_id' => '1468255054755'],
                ['value' => 'Ultra Blue', 'choice_id' => '1450370050989'],
                ['value' => '4-part Carbonless', 'choice_id' => '1468255963714'],
                ['value' => 'Laser (32 lb.)', 'choice_id' => '1448988664295'],
                ['value' => 'Premium Matte (130 lb.)', 'choice_id' => '1535621107017'],
                ['value' => 'Standard Matte (110 lb.)', 'choice_id' => '1535621093711'],
                ['value' => 'Standard Glossy (110 lb.)', 'choice_id' => '1535621006524'],
                ['value' => 'Premium Glossy (130 lb.)', 'choice_id' => '1538617908107'],
                ['value' => 'Glossy Photo Paper', 'choice_id' => '1466458729632'],
                ['value' => 'Satin Photo Paper', 'choice_id' => '1453908436618'],
                ['value' => 'Canvas Paper', 'choice_id' => '1448989268401'],
                ['value' => 'Glossy Paper', 'choice_id' => '1448989268961'],
                ['value' => 'Backlit Paper', 'choice_id' => '1449002119257'],
                ['value' => 'Matte Paper', 'choice_id' => '1448989269489'],
                ['value' => 'Laser (80 lb.)', 'choice_id' => '1448988677979'],
                ['value' => 'Magnet', 'choice_id' => '1535621238129'],
                ['value' => 'Luxury Weight (240 lb.)', 'choice_id' => '1535621254468'],
                ['value' => 'Linen (100 lb.)', 'choice_id' => '1537804645283'],
                ['value' => 'CC4 100lb Matte', 'choice_id' => '1535620925780'],
                ['value' => 'CC3 100lb Gloss', 'choice_id' => '1535621259892'],
                ['value' => 'Standard White Bond Paper', 'choice_id' => '1551431938833'],
                ['value' => 'Gloss Paper', 'choice_id' => '1602863860170'],
                ['value' => 'Ultra Bright White(Card)', 'choice_id' => '1448988674174'],
                ['value' => 'Magnetic Material', 'choice_id' => '1617031967176'],
                ['value' => 'Heavy Weight Coated', 'choice_id' => '1568389109070']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addCuttingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'cutting',
            [
                'type' => 'varchar',
                'label' => 'Cutting',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '70',
                'position' => '70',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'cutting');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '1/2 Horizontal', 'choice_id' => '1448999393914'],
                ['value' => 'None', 'choice_id' => '1448999392195'],
                ['value' => '1/2 Vertical', 'choice_id' => '1448999392595'],
                ['value' => '1/3 Horizontal', 'choice_id' => '1448999394330'],
                ['value' => '1/3 Vertical', 'choice_id' => '1448999393051'],
                ['value' => '1/4 Cut', 'choice_id' => '1448999393482']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addBackBindingCoversAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'back_binding_covers',
            [
                'type' => 'varchar',
                'label' => 'Back Binding Covers',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '75',
                'position' => '75',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'back_binding_covers');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Green', 'choice_id' => '1449086719498'],
                ['value' => 'Ultra Bright White (Card)', 'choice_id' => '1449086712195'],
                ['value' => 'Ultra Fuchsia', 'choice_id' => '1449086721474'],
                ['value' => 'Canary', 'choice_id' => '1449086718634'],
                ['value' => 'Blue Vinyl', 'choice_id' => '1449086650000'],
                ['value' => '110 lb. Index', 'choice_id' => '1449086714739'],
                ['value' => 'Ultra Lime', 'choice_id' => '1449086722626'],
                ['value' => 'Natural', 'choice_id' => '1449086715995'],
                ['value' => 'Antique Gray', 'choice_id' => '1449086717386'],
                ['value' => 'Sea Spray', 'choice_id' => '1449086716947'],
                ['value' => 'Sun Yellow', 'choice_id' => '1449086719962'],
                ['value' => 'No Cover', 'choice_id' => '1449086648768'],
                ['value' => 'Frosted Cover', 'choice_id' => '1449086651160'],
                ['value' => 'Laser (80lb.)', 'choice_id' => '1449086715211'],
                ['value' => 'Bright Blue', 'choice_id' => '1449086722034'],
                ['value' => 'Blue', 'choice_id' => '1449086719003'],
                ['value' => 'Soft Pink', 'choice_id' => '1449086717819'],
                ['value' => 'Gloss Cover', 'choice_id' => '1449086715635'],
                ['value' => 'Clear Cover', 'choice_id' => '1449086649568'],
                ['value' => 'Red', 'choice_id' => '1449086720946'],
                ['value' => 'Ultra Orange', 'choice_id' => '1449086720467'],
                ['value' => 'Black Vinyl', 'choice_id' => '1449086650424'],
                ['value' => 'Ivory', 'choice_id' => '1449086718251'],
                ['value' => 'Sand Stone', 'choice_id' => '1449086716523']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addSidesAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'sides',
            [
                'type' => 'varchar',
                'label' => 'Sides',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '80',
                'position' => '80',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'sides');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Double-Sided', 'choice_id' => '1448988124807'],
                ['value' => 'Single-Sided', 'choice_id' => '1448988124560']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addBinderSpineAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'binder_spine',
            [
                'type' => 'varchar',
                'label' => 'Binder Spine',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '85',
                'position' => '85',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'binder_spine');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'None', 'choice_id' => '1448998704181'],
                ['value' => 'Print on Binder Spine', 'choice_id' => '1448999902230']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addCollationAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'collation',
            [
                'type' => 'varchar',
                'label' => 'Collation',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '90',
                'position' => '90',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'collation');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'UnCollated', 'choice_id' => '1448986645784'],
                ['value' => 'Collated', 'choice_id' => '1448986654687']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addFoldingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'folding',
            [
                'type' => 'varchar',
                'label' => 'Folding',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '95',
                'position' => '95',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'folding');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Tri-Fold', 'choice_id' => '1448999722762'],
                ['value' => 'Z-Fold', 'choice_id' => '1448999722058'],
                ['value' => 'None', 'choice_id' => '1448999720595'],
                ['value' => '1/2 Fold', 'choice_id' => '1448999721114']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addHolePunchingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'hole_punching',
            [
                'type' => 'varchar',
                'label' => 'Hole Punching',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '100',
                'position' => '100',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'hole_punching');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '3 Hole Punch Left Side', 'choice_id' => '1448998704309'],
                ['value' => '2 Hole Top', 'choice_id' => '1453258712001'],
                ['value' => 'None', 'choice_id' => '1448999902070'],
                ['value' => '2 Hole Left', 'choice_id' => '1460564910077'],
                ['value' => '3 Hole Punch Top Side', 'choice_id' => '1460564256737']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addFrontBindingCoversAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'front_binding_covers',
            [
                'type' => 'varchar',
                'label' => 'Front Binding Covers',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '105',
                'position' => '105',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'front_binding_covers');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Ultra Lime', 'choice_id' => '1448997457874'],
                ['value' => 'Sea Spray', 'choice_id' => '1448997303450'],
                ['value' => 'No Cover', 'choice_id' => '1448997222713'],
                ['value' => 'Bright Blue', 'choice_id' => '1448997313809'],
                ['value' => 'Antique Gray', 'choice_id' => '1448997303858'],
                ['value' => 'Ultra Fuchsia', 'choice_id' => '1448997313401'],
                ['value' => 'Blue Vinyl', 'choice_id' => '1449086923828'],
                ['value' => 'Ultra Bright White(Card)', 'choice_id' => '1448997300162'],
                ['value' => 'Blue', 'choice_id' => '1448997310993'],
                ['value' => 'Soft Pink', 'choice_id' => '1448997304322'],
                ['value' => 'Sun Yellow', 'choice_id' => '1448997311665'],
                ['value' => 'Red', 'choice_id' => '1448997312649'],
                ['value' => 'Sand Stone', 'choice_id' => '1448997302938'],
                ['value' => 'Green', 'choice_id' => '1448997311329'],
                ['value' => 'Ivory', 'choice_id' => '1448997304690'],
                ['value' => 'Canary', 'choice_id' => '1448997305074'],
                ['value' => 'Frosted Cover', 'choice_id' => '1449086924668'],
                ['value' => '110 lb. Index', 'choice_id' => '1448997300522'],
                ['value' => 'Gloss Cover', 'choice_id' => '1448997301634'],
                ['value' => 'Natural', 'choice_id' => '1448997302210'],
                ['value' => 'Ultra Orange', 'choice_id' => '1448997312009'],
                ['value' => 'Black Vinyl', 'choice_id' => '1449086924220'],
                ['value' => 'Laser(80 lb.)', 'choice_id' => '1448997300922'],
                ['value' => 'Clear Cover', 'choice_id' => '1449086923133'],
                ['value' => 'Ultra Bright White (Card)', 'choice_id' => '1448997300162']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addPrintFirstPageonCoverAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'print_first_page_on_cover',
            [
                'type' => 'varchar',
                'label' => 'Print First Page on Cover',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '110',
                'position' => '110',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'print_first_page_on_cover');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'No', 'choice_id' => '1465411323801'],
                ['value' => 'Yes', 'choice_id' => '1465411357705']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addBindingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'binding',
            [
                'type' => 'varchar',
                'label' => 'Binding',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '115',
                'position' => '115',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'binding');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '3-Ring Binder', 'choice_id' => '1452632224826'],
                ['value' => 'Coil', 'choice_id' => '1448997199786'],
                ['value' => 'Comb', 'choice_id' => '1448997200033'],
                ['value' => 'Staple', 'choice_id' => '1452632212741'],
                ['value' => 'None', 'choice_id' => '1448997199553'],
                ['value' => 'LEFT_EDGE', 'choice_id' => '1552418968266'],
                ['value' => 'TOP_EDGE', 'choice_id' => '1552418970266']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addPaperSizeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'paper_size',
            [
                'type' => 'varchar',
                'label' => 'Paper Size',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '120',
                'position' => '120',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'paper_size');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '11x17', 'choice_id' => '1448986651164'],
                ['value' => '8.5x14', 'choice_id' => '1448986650652'],
                ['value' => '8.5x11', 'choice_id' => '1448986650332'],
                ['value' => '4.24x8.24', 'choice_id' => '1533111912041'],
                ['value' => '5.24x7.24', 'choice_id' => '1533111699862'],
                ['value' => '36 x 72', 'choice_id' => '1449001524486'],
                ['value' => '36 x 60', 'choice_id' => '1449000061467'],
                ['value' => '5.74x8.74', 'choice_id' => '1538130156765'],
                ['value' => '8.74x11.24', 'choice_id' => '1568922338975'],
                ['value' => '4.5x11.25', 'choice_id' => '1559890231136'],
                ['value' => '5.75x8.75', 'choice_id' => '1538130156765'],
                ['value' => '4.5x5.75', 'choice_id' => '1559889602193'],
                ['value' => '12x18', 'choice_id' => '1449002214146'],
                ['value' => '18x24', 'choice_id' => '1449002053710'],
                ['value' => '3.74x2.24', 'choice_id' => '1595603020956'],
                ['value' => '3.75x2.25', 'choice_id' => '1533241903556'],
                ['value' => '4.25x8.25', 'choice_id' => '1533111912041'],
                ['value' => '5.25x7.25', 'choice_id' => '1533111699862'],
                ['value' => '5.75x4.5', 'choice_id' => '1534756174767'],
                ['value' => '4.25x5.5', 'choice_id' => '1463685411561'],
                ['value' => '8.5x5.5', 'choice_id' => '1463685362954'],
                ['value' => '24x36', 'choice_id' => '1449002054022'],
                ['value' => '36x48', 'choice_id' => '1449002232746'],
                ['value' => '6.24x8.24', 'choice_id' => '1602863107648'],
                ['value' => '10.24x7.24', 'choice_id' => '1602175852705'],
                ['value' => 'custom', 'choice_id' => '1562221358278'],
                ['value' => '4.74x5.74', 'choice_id' => '1559889602193'],
                ['value' => '24x6', 'choice_id' => '1617312340777'],
                ['value' => '4.49x5.79', 'choice_id' => '1612894237705'],
                ['value' => '4.25x11', 'choice_id' => '1463685462651']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addOrientationAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'orientation',
            [
                'type' => 'varchar',
                'label' => 'Orientation',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '125',
                'position' => '125',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'orientation');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Horizontal', 'choice_id' => '1449000016327'],
                ['value' => 'Vertical', 'choice_id' => '1449000016192'],
                ['value' => 'Landscape', 'choice_id' => '1449000016327'],
                ['value' => 'Portrait', 'choice_id' => '1449000016192']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addEnvelopeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'envelope',
            [
                'type' => 'varchar',
                'label' => 'Envelope',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '130',
                'position' => '130',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'envelope');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Standard White Envelope', 'choice_id' => '1534920308259']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addGrommetsAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'grommets',
            [
                'type' => 'varchar',
                'label' => 'Grommets',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '135',
                'position' => '135',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'grommets');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Grommets', 'choice_id' => '1449001942802'],
                ['value' => 'None', 'choice_id' => '1449001942938']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addMaterialTypeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'material_type',
            [
                'type' => 'varchar',
                'label' => 'Material Type',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '140',
                'position' => '140',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'material_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Outdoor Banners', 'choice_id' => '1449001611674'],
                ['value' => 'Premium Outdoor Banners', 'choice_id' => '1449001612602'],
                ['value' => 'Indoor', 'choice_id' => '1449001610546'],
                ['value' => 'Mesh', 'choice_id' => '1449001613250'],
                ['value' => 'Recyclable', 'choice_id' => '1449001613114'],
                ['value' => 'Corrugated Plastic', 'choice_id' => '1449002257998'],
                ['value' => '6mil PVC', 'choice_id' => '1617312404817'],
                ['value' => 'Aluminum', 'choice_id' => '1449002257838']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addDecalTypeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'decal_type',
            [
                'type' => 'varchar',
                'label' => 'Decal Type',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '145',
                'position' => '145',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'decal_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Permanent', 'choice_id' => '1453760104434'],
                ['value' => 'Removable', 'choice_id' => '1453760115084']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addReadabilityAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'readability',
            [
                'type' => 'varchar',
                'label' => 'Readability',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '150',
                'position' => '150',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'readability');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Reverse', 'choice_id' => '1453749222306'],
                ['value' => 'Straight', 'choice_id' => '1453749211960']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addSizeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'size',
            [
                'type' => 'varchar',
                'label' => 'Size',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '155',
                'position' => '155',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'size');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Custom', 'choice_id' => '1453760132481'],
                ['value' => '4x10', 'choice_id' => '1449001528453'],
                ['value' => '4x6', 'choice_id' => '1453753163807'],
                ['value' => '4x8', 'choice_id' => '1449001528165'],
                ['value' => '2x10', 'choice_id' => '1554756386776'],
                ['value' => '3x10', 'choice_id' => '1449001527069'],
                ['value' => '3x8', 'choice_id' => '1449001525933'],
                ['value' => '2x4', 'choice_id' => '1449000061315'],
                ['value' => '3x6', 'choice_id' => '1449001524486'],
                ['value' => '2x6', 'choice_id' => '1554756394779'],
                ['value' => '3x5', 'choice_id' => '1453753154462'],
                ['value' => '1.6x3', 'choice_id' => '1554756382774'],
                ['value' => '2x8', 'choice_id' => '1554756384775'],
                ['value' => '18x18', 'choice_id' => '1584636929055'],
                ['value' => '16x36', 'choice_id' => '1584636929054'],
                ['value' => '16x24', 'choice_id' => '1584636929053'],
                ['value' => '16x18', 'choice_id' => '1584636929052'],
                ['value' => '16x16', 'choice_id' => '1584636929051'],
                ['value' => '11x36', 'choice_id' => '1584636929050'],
                ['value' => '11x24', 'choice_id' => '1584636929049'],
                ['value' => '11x20', 'choice_id' => '1584636929048'],
                ['value' => '11x18', 'choice_id' => '1584636929047'],
                ['value' => '11x16', 'choice_id' => '1584636929046'],
                ['value' => '11x11', 'choice_id' => '1584636929045'],
                ['value' => '20x36', 'choice_id' => '1584638639411'],
                ['value' => '10x10', 'choice_id' => '1453908447605'],
                ['value' => '18x24', 'choice_id' => '1449002053710'],
                ['value' => '16x20', 'choice_id' => '1449002053197'],
                ['value' => '22x28', 'choice_id' => '1449002216146'],
                ['value' => '36x36', 'choice_id' => '1584636929060'],
                ['value' => '24x36', 'choice_id' => '1449002054022'],
                ['value' => '36x48', 'choice_id' => '1449002232746'],
                ['value' => '20x24', 'choice_id' => '1453908464424'],
                ['value' => '18x20', 'choice_id' => '1584636929056'],
                ['value' => '18x36', 'choice_id' => '1584636929057'],
                ['value' => '20x20', 'choice_id' => '1584636929058'],
                ['value' => '24x24', 'choice_id' => '1584636929059'],
                ['value' => '2x3.5', 'choice_id' => '1453753139386'],
                ['value' => '5x7', 'choice_id' => '1453753169811'],
                ['value' => '12x24', 'choice_id' => '1453756650822'],
                ['value' => '12x18', 'choice_id' => '1449002214146'],
                ['value' => '16.24x20.24', 'choice_id' => '1568388786549'],
                ['value' => '11.24x17.24', 'choice_id' => '1568388837841'],
                ['value' => '24.24x36.24', 'choice_id' => '1568388708178'],
                ['value' => '18.24x24.24', 'choice_id' => '1568388774706'],
                ['value' => '32x80', 'choice_id' => '1453407257978'],
                ['value' => '11x17', 'choice_id' => '1453407297850'],
                ['value' => '24x70', 'choice_id' => '1453407280495'],
                ['value' => '20.24x24.24', 'choice_id' => '1615397291221'],
                ['value' => '20.24x20.24', 'choice_id' => '1615397621956']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addBleedsAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'bleeds',
            [
                'type' => 'varchar',
                'label' => 'Bleeds',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '160',
                'position' => '160',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'bleeds');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Full Bleed', 'choice_id' => '1595519381716']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addImpositionAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'imposition',
            [
                'type' => 'varchar',
                'label' => 'Imposition',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '165',
                'position' => '1655',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'imposition');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Yes', 'choice_id' => '1540241296520'],
                ['value' => 'No', 'choice_id' => '1596469171689']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addColorBlackWhiteAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'color_black_white',
            [
                'type' => 'varchar',
                'label' => 'Color/Black & White',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '170',
                'position' => '170',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'color_black_white');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Full Color', 'choice_id' => '1448988600611']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addProductTypeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'fxo_product_type',
            [
                'type' => 'varchar',
                'label' => 'Product Type',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '175',
                'position' => '175',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'fxo_product_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Quick Postcards', 'choice_id' => '1559887158619'],
                ['value' => 'Premium Postcards', 'choice_id' => '1569302456940'],
                ['value' => 'Backlit Poster', 'choice_id' => '1464884466419'],
                ['value' => 'Photo Poster', 'choice_id' => '1465832356069'],
                ['value' => 'Mounted Poster', 'choice_id' => '1464882780165'],
                ['value' => 'Poster Print', 'choice_id' => '1464884233958'],
                ['value' => 'Canvas Prints', 'choice_id' => '1464884397179'],
                ['value' => 'Quick Business Cards', 'choice_id' => '1531980128149'],
                ['value' => 'Premium Business Cards', 'choice_id' => '1535620654436'],
                ['value' => 'Quick Holiday Cards', 'choice_id' => '1533112665437'],
                ['value' => 'Premium Holiday Cards', 'choice_id' => '1537804599894'],
                ['value' => 'Note Cards', 'choice_id' => '1611675634174'],
                ['value' => 'Premium Note Cards', 'choice_id' => '1537806262156'],
                ['value' => 'Menu', 'choice_id' => '1591961360619'],
                ['value' => 'Architectural Prints', 'choice_id' => '1551431719214'],
                ['value' => 'Premium Invitations & Announcements', 'choice_id' => '1537804629252'],
                ['value' => 'Quick Invitations & Announcements', 'choice_id' => '1533112686585']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addFrameAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'frame',
            [
                'type' => 'varchar',
                'label' => 'Frame',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '180',
                'position' => '180',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'frame');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Wire H Stake', 'choice_id' => '1458662106342'],
                ['value' => 'Metal H Frame', 'choice_id' => '1458662118443']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addSignTypeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'sign_type',
            [
                'type' => 'varchar',
                'label' => 'Sign Type',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '185',
                'position' => '185',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'sign_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Short-Term Outdoor', 'choice_id' => '1458157374679'],
                ['value' => 'Medium-Term Outdoor', 'choice_id' => '1458157429913'],
                ['value' => 'Long-Term Outdoor', 'choice_id' => '1458157442345']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addMountingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'mounting',
            [
                'type' => 'varchar',
                'label' => 'Mounting',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '190',
                'position' => '190',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'mounting');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Gator Board', 'choice_id' => '1449002428163'],
                ['value' => '1 1/2 Rigid Black Board', 'choice_id' => '1572537359134'],
                ['value' => 'Black Edge Board', 'choice_id' => '1465917586894'],
                ['value' => 'None', 'choice_id' => '1449002427826'],
                ['value' => 'Foam Board', 'choice_id' => '1449002427994'],
                ['value' => '1 1/2 Wooden Frame', 'choice_id' => '1466532051072']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addFinishingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'finishing',
            [
                'type' => 'varchar',
                'label' => 'Finishing',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '195',
                'position' => '195',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'finishing');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Raised Print', 'choice_id' => '1535621917179'],
                ['value' => 'Metallic', 'choice_id' => '1535621642848'],
                ['value' => 'None', 'choice_id' => '1535621650907']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addLetteringHeightAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'lettering_height',
            [
                'type' => 'varchar',
                'label' => 'Lettering Height',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '200',
                'position' => '200',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'lettering_height');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Lettering Height', 'choice_id' => '1453750808592']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addLetteringFontAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'lettering_font',
            [
                'type' => 'varchar',
                'label' => 'Lettering Font',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '205',
                'position' => '205',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'lettering_font');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Burgress Brush', 'choice_id' => '1453749280095'],
                ['value' => 'Rodeo', 'choice_id' => '1470929267154'],
                ['value' => 'Garamond Bold', 'choice_id' => '1453749350372'],
                ['value' => 'Copper Black', 'choice_id' => '1453749302080'],
                ['value' => 'Horatio', 'choice_id' => '1470929228283'],
                ['value' => 'Candice', 'choice_id' => '1453749290199'],
                ['value' => 'Dom Casual', 'choice_id' => '1453749313249'],
                ['value' => 'Bookman Bold', 'choice_id' => '1453749258161'],
                ['value' => 'Export', 'choice_id' => '1453749330484'],
                ['value' => 'Helvetica Bold', 'choice_id' => '1470929213955'],
                ['value' => 'Military Block', 'choice_id' => '1470929245766'],
                ['value' => 'Freestyle Script Bold', 'choice_id' => '1453749337424'],
                ['value' => 'Antique Olive Bold', 'choice_id' => '1453749245373'],
                ['value' => 'Rickshaw', 'choice_id' => '1470929256333']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addLetteringColorAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'lettering_color',
            [
                'type' => 'varchar',
                'label' => 'Lettering Color',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '210',
                'position' => '210',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'lettering_color');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'White', 'choice_id' => '1453749395779'],
                ['value' => 'Gentian Blue', 'choice_id' => '1453749432046'],
                ['value' => 'Yellow', 'choice_id' => '1453749423031'],
                ['value' => 'Light Blue', 'choice_id' => '1453749406405'],
                ['value' => 'Brown', 'choice_id' => '1453749577110'],
                ['value' => 'Gold(Metallic)', 'choice_id' => '1453749546049'],
                ['value' => 'Orange', 'choice_id' => '1453749561156'],
                ['value' => 'King Blue', 'choice_id' => '1453749461878'],
                ['value' => 'Grass Green', 'choice_id' => '1453749594808'],
                ['value' => 'Black', 'choice_id' => '1453749443968'],
                ['value' => 'Purple', 'choice_id' => '1453749587521'],
                ['value' => 'Silver(Metallic)', 'choice_id' => '1453749475768'],
                ['value' => 'Burgundy', 'choice_id' => '1453749490811'],
                ['value' => 'Red', 'choice_id' => '1453749451981'],
                ['value' => 'Dark Green', 'choice_id' => '1453749568258'],
                ['value' => 'Pink', 'choice_id' => '1453749414969'],
                ['value' => 'Deep Sea', 'choice_id' => '1453749537375']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addTabsAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'tabs',
            [
                'type' => 'varchar',
                'label' => 'Tabs',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '215',
                'position' => '215',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'tabs');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Add Tab', 'choice_id' => '1448998735989']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addFrontBackBindingCoversAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'front_back_binding_covers',
            [
                'type' => 'varchar',
                'label' => 'Front/Back Binding Covers',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '220',
                'position' => '220',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'front_back_binding_covers');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Soft Cover Gloss', 'choice_id' => '1602865677595'],
                ['value' => 'Hard Cover Soft Touch', 'choice_id' => '1602865677598'],
                ['value' => 'Soft Cover Soft Touch', 'choice_id' => '1602865677596'],
                ['value' => 'Hard Cover Gloss', 'choice_id' => '1602865677597']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addDrillingAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'drilling',
            [
                'type' => 'varchar',
                'label' => 'Drilling',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '225',
                'position' => '225',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'drilling');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'None', 'choice_id' => '1618930174454'],
                ['value' => '2 Hole Top', 'choice_id' => '1617890632951']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addLedLightDisplayAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'led_light_display',
            [
                'type' => 'varchar',
                'label' => 'LED Light Display',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '230',
                'position' => '230',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'led_light_display');

        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'None', 'choice_id' => '1453407506022'],
                ['value' => 'LED Lights', 'choice_id' => '1453407511417']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addBannerTypeAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'banner_type',
            [
                'type' => 'varchar',
                'label' => 'Banner Type',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '235',
                'position' => '235',
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
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'banner_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'Large Tripod', 'choice_id' => '1453407234137'],
                ['value' => 'Standard Retractable Banner', 'choice_id' => '1453407124324'],
                ['value' => 'Versatile Two Banner', 'choice_id' => '1453407195705'],
                ['value' => 'Small Tripod', 'choice_id' => '1453407211117'],
                ['value' => 'Versatile One Banner', 'choice_id' => '1453407166716'],
                ['value' => 'Deluxe Retractable Banner', 'choice_id' => '1453407152634']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

}
