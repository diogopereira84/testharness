<?php

namespace Fedex\LiveSearch\Setup\Patch\Data;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateProductCustomizeSearchActionAttributeValuesFixed implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /**
         * Get attribute set ID
         */
        $attributeSetId = $this->moduleDataSetup->getConnection()->fetchOne(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute_set'),
                    ['attribute_set_id']
                )
                ->where('attribute_set_name = ?', 'PrintOnDemand')
        );

        /**
         * Get products having attribute set PrintOnDemand
         */
        $productsWithAttributeSet = $this->moduleDataSetup->getConnection()->fetchCol(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('catalog_product_entity'),
                    ['row_id']
                )
                ->where('attribute_set_id = ?', $attributeSetId)
        );

        /**
         * Get customize_search_action attribute details
         */
        $customizeSearchActionAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where('attribute_code = ?', 'customize_search_action')
        );

        /**
         * Get visibility attribute details
         */
        $visibilityAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where('attribute_code = ?', 'visibility')
        );

        /**
         * Reset all customize_search_action values
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $customizeSearchActionAttribute['backend_type']
            ),
            ['value' => 0],
            ['attribute_id = ?' => $customizeSearchActionAttribute['attribute_id']]
        );

        /**
         * Set customize_search_action value 1 for all products in PrintOnDemand attribute set
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $customizeSearchActionAttribute['backend_type']
            ),
            ['value' => 1],
            [
                'row_id IN (?)' => $productsWithAttributeSet,
                'attribute_id = ?' => $customizeSearchActionAttribute['attribute_id']
            ]
        );

        /**
         * Set visibility value 4 for all products in PrintOnDemand attribute set to prevent issue from old version of this file
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $customizeSearchActionAttribute['backend_type']
            ),
            ['value' => Visibility::VISIBILITY_BOTH],
            [
                'row_id IN (?)' => $productsWithAttributeSet,
                'attribute_id = ?' => $visibilityAttribute['attribute_id']
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }


    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
