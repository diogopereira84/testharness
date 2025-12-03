<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
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

class AddSwatchAttributes implements DataPatchInterface
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
            'product_uom' => [
                'label' => 'UOM',
                'values' => ['DZ', 'PK', 'ST']
            ],
            'ruling' => [
                'label' => 'Ruling',
                'values' => ['Narrow', 'Wide/Legal']
            ],
            'number_of_sheets' => [
                'label' => 'Number of Sheets',
                'values' => ['70', '200']
            ],
            'subject_number' => [
                'label' => 'Subject Number',
                'values' => ['1', '5']
            ],
            'pack_quantity' => [
                'label' => 'Pack Quantity',
                'values' => ['10 EA', '20 EA', '24 EA', '36 EA']
            ]
        ];

        foreach ($attributes as $code => $config) {
            $label = $config['label'];
            $values = $config['values'];

            $attributeId = $eavSetup->getAttributeId($entityType, $code);

            if (!$attributeId) {
                $eavSetup->addAttribute(
                    $entityType,
                    $code,
                    [
                        'type' => 'int',
                        'label' => $label,
                        'input' => 'swatch_text',
                        'source' => Table::class,
                        'required' => false,
                        'user_defined' => true,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'used_in_product_listing' => true,
                        'visible_on_front' => true,
                        'mirakl_is_exportable' => true,
                        'mirakl_is_variant' => true,
                        'group' => 'Mirakl Marketplace',
                    ]
                );
                $attributeId = $eavSetup->getAttributeId($entityType, $code);
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

            $connection->update(
                $connection->getTableName('catalog_eav_attribute'),
                ['additional_data' => json_encode(['swatch_input_type' => 'text'])],
                ['attribute_id = ?' => $attributeId]
            );

            $attribute = $this->eavConfig->getAttribute($entityType, $code);

            foreach ($values as $value) {
                $optionId = $this->getOptionIdByLabel($connection, (int)$attribute->getId(), $value);

                if (!$optionId) {
                    $option = $this->optionFactory->create();
                    $option->setLabel($value);
                    $this->optionManagement->add($entityType, $code, $option);
                    $optionId = $this->getLastInsertedOptionId($connection);
                }

                $connection->delete(
                    $connection->getTableName('eav_attribute_option_value'),
                    ['option_id = ?' => $optionId, 'store_id IN (?)' => $storeIds]
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

                $connection->delete(
                    $connection->getTableName('eav_attribute_option_swatch'),
                    ['option_id = ?' => $optionId, 'store_id IN (?)' => $storeIds]
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

        return (int)$connection->fetchOne($select);
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
