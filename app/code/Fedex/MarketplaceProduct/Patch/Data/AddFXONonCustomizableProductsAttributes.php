<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Model\Config;

class AddFXONonCustomizableProductsAttributes implements DataPatchInterface
{
    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private EavSetupFactory $eavSetupFactory,
        private ModuleDataSetupInterface $moduleDataSetup,
        private AttributeOptionManagementInterface $optionManagement,
        private AttributeOptionInterfaceFactory $optionFactory,
        private Config $eavConfig
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeSetId = $eavSetup->getAttributeSetId('catalog_product', 'FXONonCustomizableProducts');

        $attributeGroupId = $eavSetup->getAttributeGroupId('catalog_product', $attributeSetId, 'Mirakl Marketplace');

        $attributes = [
            'product_size' => 'Product Size',
            'qty_Sets' => 'Qty Sets',
            'thickness' => 'Thickness',
            'packaging_material_type' => 'Packaging Material Type',
            'color' => 'Color',
            'feature' => 'Feature',
            'capacity' => 'Capacity',
            'printer_type' => 'Printer Type',
            'style' => 'Style',
            'brand' => 'Brand',
        ];

        $attributeValues = [
            'product_size' => ['12in x 150ft', '12in x 30ft', '12in x 17.25in', '13.75in x 20in', '2in x 4in', '3.33in x 4in', '5.5in x 8.5in', 'Wide/Legal Rule', 'Medium/College Rule', '9in x 11.5in', '9.75in x 12.5in', '11in x 13.5in', '6in x 10in', '8.5in x 12in', '10.5in x 16in', '6in x 9in', '9in x 12in', 'Mini', 'Small', 'Medium', 'Large', '#3', '#1', 'Jumbo', 'Up to 6 lbs per Pair', 'Up to 12 lbs', 'Up to 4 lbs per Pair', 'Up to 16 lbs', 'Up to 15 lbs', 'Up to 2 lbs', 'Up to 5 lbs', '0.75in x 60yds', '2in x 60yds', '14in x 19in', '10in x 13in', '12in x 15.5in', '1in x 2.63in', '0.5in x 1.75in', '3.75in x 3in', '2.68in x 4.38in', 'Wide/Legal Rule - 8.5in x 11in', 'Narrow Rule - 5in x 8in', '12mm x 55m', '48mm x 55m', '33', '64', '12in x 10ft', 'Bold', 'Extra-Fine', 'Broad Chisel Tip'],
            'qty_Sets' => ['1', '3', '4', '5', '6', '8', '10', '11', '12', '15', '16', '18', '24', '25', '26', '31', '36', '48', '50', '100', '150', '200', '250', '425', '750', '850', '2000', '3000', '12 Pairs', '10 Rolls', '6 Rolls', '12 Sleeves', '24 Sleeves', '120 Pairs', '2 Hooks 4 Strips', '20 Hooks, 24 Strips', '3 Hooks, 6 Strips', '4 Pairs', '50 Sets', '6 Hooks 8 Strips'],
            'thickness' => ['0.05in'],
            'packaging_material_type' => ['Cushioning Material'],
            'color' => ['Aquamarine', 'Assorted', 'Assorted Bright', 'Assorted Primary', 'Black', 'Blue', 'Burgundy', 'Chrome', 'Clear/Black', 'Clear/Blue', 'Clear/Dark Blue', 'Clear/Red', 'Contemporary Color Tabs', 'Dark Blue', 'Four Colors', 'Gold-Silver-Bronze', 'Light Blue', 'Metallic Silver', 'Navy Blue', 'Pink', 'Red', 'Tan', 'Traditional Color Tabs'],
            'feature' => ['Non-Locking Rings', 'Locking Rings'],
            'capacity' => ['1in', '3in', '0.5in', '1.5in', '2in'],
            'printer_type' => ['Laser', 'Inkjet'],
            'style' => ['Allstate', 'Avery'],
            'brand' => ['3M™', 'ACCO', 'Advantus', 'Alliance®', 'Avery®', 'BIC®', 'Blue Sky®', 'Cambridge®', 'Canon®', 'Cardinal®', 'Champion Sports', 'C-Line®', 'Command™', 'Crayola®', 'deflecto®', 'Dri-Mark®', 'Durable®', 'Elementree®', "Elmer's®", 'EXPO®', 'Five Star®', 'GBC®', 'Gorilla®', 'Hammermill®', 'HP Papers', 'Mead®', 'Neenah Paper', 'NuDell™', 'Office Impressions®', 'Oxford', 'Oxford™', 'Paper Mate®', 'Paper Mate® Liquid Paper®', 'Pendaflex®', 'Pentel®', 'Pilot®', 'Post-it®', 'Post-it® Dispenser Notes', 'Post-it® Easel Pads Super Sticky', 'Post-it® Flags', 'Post-it® Notes', 'Post-it® Notes Super Sticky', 'Post-it® Pop-up Notes', 'Post-it® Pop-up Notes Super Sticky', 'Post-it® Tabs', 'Prismacolor®', 'Quality Park™', 'Quartet®', 'Roaring Spring®', 'Safco®', 'Samsill®', 'Saunders', 'Scotch®', 'Scotch™', 'Sealed Air', 'Sharpie®', 'Sharpie® Roller', 'Sharpie® S-Gel™', 'SICURIX®', 'slice®', 'Smead', 'Stanley®', 'Survivor®', 'Swingline®', 'Tatco', 'Tombow®', 'TOPS™', 'TPG Creations™', 'U Brands', 'uniball®', 'Universal®', 'Victor®', 'Zebra®'],
        ];

        foreach ($attributes as $code => $label) {
            if (!$eavSetup->getAttributeId('catalog_product', $code)) {
                $eavSetup->addAttribute(
                    'catalog_product',
                    $code,
                    [
                        'type' => 'varchar',
                        'label' => $label,
                        'input' => 'select',
                        'required' => false,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'user_defined' => true,
                        'source' => Table::class,
                        'mirakl_is_exportable' => true,
                    ]
                );
            }

            $eavSetup->addAttributeToSet(
                'catalog_product',
                (int) $attributeSetId,
                (int) $attributeGroupId,
                $code
            );

            if (isset($attributeValues[$code])) {
                $attributeObj = $this->eavConfig->getAttribute('catalog_product', $code);
                $existingOptions = [];
                if ($attributeObj && $attributeObj->getSource()) {
                    $existingOptions = $attributeObj->getSource()->getAllOptions();
                }

                foreach ($attributeValues[$code] as $value) {
                    $optionExists = false;
                    foreach ($existingOptions as $existingOption) {
                        if (isset($existingOption['label']) && $existingOption['label'] === $value) {
                            $optionExists = true;
                            break;
                        }
                    }

                    if ($optionExists) {
                        continue;
                    }

                    $option = $this->optionFactory->create();
                    $option->setLabel($value);

                    try {
                        $this->optionManagement->add('catalog_product', $code, $option);
                    } catch (\Exception $e) {
                        throw new \Exception('Attribute error: ' . $code . ' - ' . $e->getMessage());
                    }
                }
            }
        }

        $this->moduleDataSetup->endSetup();
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
