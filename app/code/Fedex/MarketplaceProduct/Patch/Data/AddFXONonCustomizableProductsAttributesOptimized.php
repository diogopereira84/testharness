<?php
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddFXONonCustomizableProductsAttributesOptimized implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param EavConfig $eavConfig
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private EavConfig $eavConfig,
        private AttributeOptionManagementInterface $optionManagement,
        private AttributeOptionInterfaceFactory $optionFactory,
        private StoreManagerInterface $storeManager
    ) {}

    /**
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Validator\ValidateException
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $connection = $this->moduleDataSetup->getConnection();

        $entityType = 'catalog_product';
        $attributeSetName = 'FXONonCustomizableProducts';

        if (!$eavSetup->getAttributeSetId($entityType, $attributeSetName)) {
            $eavSetup->addAttributeSet($entityType, $attributeSetName);
        }

        $storeIds = array_map(fn($store) => (int)$store->getId(), $this->storeManager->getStores());
        array_unshift($storeIds, 0);

        $attributes = [
            'product_size' => [
                'label' => 'Product Size',
                'values' => ['12in x 150ft', '12in x 30ft', '12in x 17.25in', '13.75in x 20in', '2in x 4in', '3.33in x 4in', '5.5in x 8.5in', 'Wide/Legal Rule', 'Medium/College Rule', '9in x 11.5in', '9.75in x 12.5in', '11in x 13.5in', '6in x 10in', '8.5in x 12in', '10.5in x 16in', '6in x 9in', '9in x 12in', 'Mini', 'Small', 'Medium', 'Large', '#3', '#1', 'Jumbo', 'Up to 6 lbs per Pair', 'Up to 12 lbs', 'Up to 4 lbs per Pair', 'Up to 16 lbs', 'Up to 15 lbs', 'Up to 2 lbs', 'Up to 5 lbs', '0.75in x 60yds', '2in x 60yds', '14in x 19in', '10in x 13in', '12in x 15.5in', '1in x 2.63in', '0.5in x 1.75in', '3.75in x 3in', '2.68in x 4.38in', 'Wide/Legal Rule - 8.5in x 11in', 'Narrow Rule - 5in x 8in', '12mm x 55m', '48mm x 55m', '33', '64', '12in x 10ft', 'Bold', 'Extra-Fine', 'Broad Chisel Tip', '7 x 11.25', 'Legal', 'Letter',
                '8in x 8in x 8in', '13in x 9in x 11in', '12in x 12in x 18in', '12in x 9in x 6in', '12in x 3in x 17.5in',
                '14in x 14in x 14in', '11in x 11in x 11in', '18in x 13in x 11.75in', '17in x 17in x 7in', '16in x 16in x 16in',
                '23in x 17in x 12in', '20in x 8in x 50in', '10in x 6in x 57in', '50in x 9in x 9in', '20in x 20in x 12in',
                '24in x 24in x 24in', '54in x 8in x 28in', '22in x 22in x 22in', '28in x 28in x 28in', '56in x 8in x 36in',
                '46in x 8in x 30in', '15in x 15in x 48in', '20in x 20in x 20in', '11in x 8.5in', 'Assorted', '11in x 0.19in',
                '0.44in', 'Standard', '1.5in', '0.69in x 0.69in', '0.63in x 1.75in', '1in x 1in', '0.13in x 2.75in', '0.25in',
                '10in x 15in', '6.5in x 9.5in', '12.5in x 19in', '13.5in x 15.37in x 2.5in', '6in x 8.63in', '3.63in x 6.5in',
                '0.75in', '3in x 5in', '12 mL', '8 mL', '0.5in x 36yds', '0.5in x 75ft', '48mm x 54.8m', '0.77 oz', '0.28 oz',
                '10.25 oz', '11 oz', '14 oz', '1in x 700in', '0.17in x 394in', '0.88 oz', '0.85 oz', '0.75in x 54.17ft',
                'Chisel', '8in', '4.87in x 6.87in', '3.5in x 4.4in', '2.5in', '2.25in', '0.28in', '6in', '9in x 12.5in',
                '12.5in', '9in x 14in', '14.75in x 4in x 4in', '8.5in x 11in', '11in x 17in', '1.5in x 1.75in', '0.7mm',
                '0.5mm', '20in', '36in', '4.75in x 10.25in', '3.88in x 1.38in x 1.81in', '4in x 3in', '3.38in x 4.25in',
                '3.38in x 2.33in', '30in', '4in x 3in x 37.5in', '1.88in x 10yds', '12in x 100ft', '15.5in x 43in x 36in',
                '1.88in x 66.66ft', '1.88in x 75ft', '1.88in x 58.33ft', '1.88in x 22.2yds', '2.88in x 35yds',
                '1.88in x 25yds', '7in', '4in x 48in', '3in x 36n', '2in x 24in'
                ]
            ],
            'qty_sets' => [
                'label' => 'Quantity',
                'values' => [
                    '1',
                    '3',
                    '4',
                    '5',
                    '6',
                    '8',
                    '10',
                    '11',
                    '12',
                    '15',
                    '16',
                    '18',
                    '24',
                    '25',
                    '26',
                    '31',
                    '36',
                    '48',
                    '50',
                    '100',
                    '150',
                    '200',
                    '250',
                    '425',
                    '750',
                    '850',
                    '2000',
                    '3000',
                    '12 Pairs',
                    '10 Rolls',
                    '6 Rolls',
                    '12 Sleeves',
                    '24 Sleeves',
                    '120 Pairs',
                    '2 Hooks 4 Strips',
                    '20 Hooks, 24 Strips',
                    '3 Hooks, 6 Strips',
                    '4 Pairs',
                    '50 Sets',
                    '6 Hooks 8 Strips',
                    '8 per Dozen',
                    '75 per Dozen',
                    '500',
                    '20',
                    '35',
                    '9',
                    '260',
                    '5000',
                    '1 Set',
                    '25 Sets',
                    '6 Sets',
                    '1008',
                    '1500',
                    '2500',
                    '7',
                    '60',
                    '400',
                    "1 Each", "1 Pack", "1 Roll", "10 Pack", "10 per Box", "10 per Carton", "100 Pack", "100 per Box", "100 per Carton", "1000 Pack", "1008 Pack", "12 Pack", "12 per Carton", "150 Labels", "1500 Sheets", "16 Pack", "18 Pack", "2 Pack", "20 Pack", "20 per Box", "20 per Carton", "200 Pack", "2000 Pack", "24 Pack", "25 Pack", "25 per Box", "25 per Carton", "250 Labels", "250 per Carton", "2500 Sheets", "260 Pack", "3 Pack", "3000 per Box", "35 Pack", "36 Pack", "36 per Box", "4 Pack", "4 per Set", "400 per Box", "425 per Box", "48 Pack", "5 Pack", "5 per Carton", "5 per Set", "50 Labels", "50 Pack", "50 per Box", "50 per Carton", "500 Pack", "500 per Box", "500 Sheets", "5000 per Box", "6 Pack", "6 per Carton", "6 per Set", "60 per Box", "7 Pack", "7 per Carton", "750 Labels", "750 Pack", "8 Pack", "8 per Set", "850 per Box", "9 Pack", "Dozen"
                ]
            ],
            'thickness' => [
                'label' => 'Thickness',
                'values' => [
                    '0.05in',
                    '0.19"',
                    '0.5"',
                    'N/A',
                    '0.31in',
                    '5.3mil',
                    '3.1mil',
                    '2.6mil'
                ]
            ],
            'packaging_material_type' => [
                'label' => 'Packaging Material Type',
                'values' => [
                    'Cushioning Material',
                    'Corrugated fiberboard box',
                    'Corrugated box with die cut corrugated insert and poly bag',
                    'Cardboard',
                    'Paper',
                    'Metal',
                    'Plastic',
                    'Metal/Rubber',
                    'Stainless Steel'
                ]
            ],
            'color' => ['label' => 'Color',
                'values' => [
                    'Aquamarine',
                    'Assorted',
                    'Assorted Bright',
                    'Assorted Primary',
                    'Assorted Color',
                    'Black',
                    'Blue',
                    'Burgundy',
                    'Chrome',
                    'Clear/Black',
                    'Clear/Blue',
                    'Clear/Dark Blue',
                    'Clear/Red',
                    'Contemporary Color Tabs',
                    'Dark Blue',
                    'Four Colors',
                    'Gold-Silver-Bronze',
                    'Light Blue',
                    'Metallic Silver',
                    'Navy Blue',
                    'Pink',
                    'Red',
                    'Tan',
                    'Traditional Color Tabs',
                    'Purple',
                    'Yellow',
                    'White',
                    'Canary Yellow',
                    'Fluorescent Yellow',
                    'Clear',
                    'Frosted/Black',
                    'Clear/Gray',
                    'Marrakesh Rio de Janeiro',
                    'Floral Fantasy Colors',
                    'Beachside Cafe Colors',
                    'Supernova Neon Colors',
                    'Wanderlust Pastels'
                ]
            ],
            'feature' => [
                'label' => 'Feature',
                'values' => ['Non-Locking Rings', 'Locking Rings', 'With Dispenser', 'No Dispenser', 'Quick Dry', 'Non-Refillable', 'Removable', 'Dries Clear']
            ],
            'capacity' => [
                'label' => 'Capacity',
                'values' => ['1in', '3in', '0.5in', '1.5in', '2in', '5 lb', '3 lb', '4in']
            ],
            'printer_type' => [
                'label' => 'Printer Type',
                'values' => ['Laser', 'Inkjet', 'None', 'Inkjet and Laser']
            ],
            'style' => [
                'label' => 'Style',
                'values' => ['Allstate', 'Avery', 'TOC Ready']
            ],
            'brand' => [
                'label' => 'Brand',
                'values' => ['3M', 'ACCO', 'Advantus', 'Alliance', 'Avery', 'BIC', 'Blue Sky', 'Cambridge', 'Canon', 'Cardinal', 'Champion Sports', 'C-Line', 'Command', 'Crayola', 'deflecto', 'Dri-Mark', 'Durable', 'Elementree', "Elmer's", 'EXPO', 'Five Star', 'GBC', 'Gorilla', 'Hammermill', 'HP Papers', 'Mead', 'Neenah Paper', 'NuDell', 'Office Impressions', 'Oxford', 'Paper Mate', 'Paper Mate Liquid Paper', 'Pendaflex', 'Pentel', 'Pilot', 'Post-it', 'Post-it Dispenser Notes', 'Post-it Easel Pads Super Sticky', 'Post-it Flags', 'Post-it Notes', 'Post-it Notes Super Sticky', 'Post-it Pop-up Notes', 'Post-it Pop-up Notes Super Sticky', 'Post-it Tabs', 'Prismacolor', 'Quality Park', 'Quartet', 'Roaring Spring', 'Safco', 'Samsill', 'Saunders', 'Scotch', 'Sealed Air', 'Sharpie', 'Sharpie Roller', 'Sharpie S-Gel', 'SICURIX', 'slice', 'Smead', 'Stanley', 'Survivor', 'Swingline', 'Tatco', 'Tombow', 'TOPS', 'TPG Creations', 'U Brands', 'uniball', 'Universal', 'Victor', 'Zebra']
            ]
        ];

        foreach ($attributes as $code => $data) {
            $attributeId = $eavSetup->getAttributeId($entityType, $code);

            if (!$attributeId) {
                $eavSetup->addAttribute(
                    $entityType,
                    $code,
                    [
                        'type' => 'int',
                        'label' => $data['label'],
                        'input' => 'swatch_text',
                        'source' => Table::class,
                        'required' => false,
                        'user_defined' => true,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'mirakl_is_exportable' => true,
                        'mirakl_is_variant' => true,
                        'group' => 'Mirakl Marketplace',
                    ]
                );
            } else {
                $attributeTable = $this->moduleDataSetup->getTable('eav_attribute');
                $currentType = $connection->fetchOne(
                    $connection->select()
                        ->from($attributeTable, ['backend_type'])
                        ->where('attribute_id = ?', $attributeId)
                );

                if ($currentType !== 'int') {
                    $connection->update(
                        $attributeTable,
                        ['backend_type' => 'int'],
                        ['attribute_id = ?' => $attributeId]
                    );
                }
            }

            $attribute = $this->eavConfig->getAttribute($entityType, $code);
            $attributeData = json_decode((string) $attribute->getData('additional_data'), true);
            $isTextSwatch = isset($attributeData['swatch_input_type']) && $attributeData['swatch_input_type'] === 'text';

            foreach ($data['values'] as $value) {
                $optionId = $this->getOptionIdByLabel($connection, (int)$attribute->getId(), $value);

                if (!$optionId) {
                    $option = $this->optionFactory->create();
                    $option->setLabel($value);
                    $this->optionManagement->add($entityType, $code, $option);
                    $optionId = $this->getLastInsertedOptionId($connection);
                }

                $connection->delete(
                    $connection->getTableName('eav_attribute_option_value'),
                    [
                        'option_id = ?' => $optionId,
                        'store_id IN (?)' => $storeIds
                    ]
                );

                $valueRows = [];
                foreach ($storeIds as $storeId) {
                    $valueRows[] = [
                        'option_id' => $optionId,
                        'store_id' => $storeId,
                        'value' => $value
                    ];
                }

                $connection->insertMultiple(
                    $connection->getTableName('eav_attribute_option_value'),
                    $valueRows
                );

                if ($isTextSwatch) {
                    $connection->delete(
                        $connection->getTableName('eav_attribute_option_swatch'),
                        [
                            'option_id = ?' => $optionId,
                            'store_id IN (?)' => $storeIds
                        ]
                    );

                    $swatchRows = [];
                    foreach ($storeIds as $storeId) {
                        $swatchRows[] = [
                            'option_id' => $optionId,
                            'store_id' => $storeId,
                            'type' => 1,
                            'value' => $value
                        ];
                    }

                    $connection->insertMultiple(
                        $connection->getTableName('eav_attribute_option_swatch'),
                        $swatchRows
                    );
                }
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @param AdapterInterface $connection
     * @return int
     */
    private function getLastInsertedOptionId(AdapterInterface $connection): int
    {
        $select = $connection->select()
            ->from($connection->getTableName('eav_attribute_option'), ['option_id'])
            ->order('option_id DESC')
            ->limit(1);

        return (int) $connection->fetchOne($select);
    }

    /**
     * @param AdapterInterface $connection
     * @param int $attributeId
     * @param string $label
     * @return int|null
     */
    private function getOptionIdByLabel(AdapterInterface $connection, int $attributeId, string $label): ?int
    {
        $select = $connection->select()
            ->from(['eao' => 'eav_attribute_option'], 'eao.option_id')
            ->join(
                ['eaov' => 'eav_attribute_option_value'],
                'eao.option_id = eaov.option_id AND eaov.store_id = 0',
                []
            )
            ->where('eao.attribute_id = ?', $attributeId)
            ->where('eaov.value = ?', $label)
            ->limit(1);

        return (int) $connection->fetchOne($select) ?: null;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
