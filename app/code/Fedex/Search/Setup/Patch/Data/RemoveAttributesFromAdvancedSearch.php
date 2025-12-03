<?php

declare(strict_types=1);

namespace Fedex\Search\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class RemoveAttributesFromAdvancedSearch implements DataPatchInterface, PatchRevertableInterface
{
    const VISIBLE_IN_ADVANCED_SEARCH = 'is_visible_in_advanced_search';
    const IS_NOT_VISIBLE = 0;

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    /**
     * Remove all attributes, except ['name', 'description', 'related_keywords'], from the advanced search.
     *
     * @return $this|RemoveAttributesFromAdvancedSearch
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributesList = $this->getAttributesList();
        foreach ($attributesList as $attribute) {

            $eavSetup->updateAttribute(
                Product::ENTITY,
                $attribute['attribute_id'],
                self::VISIBLE_IN_ADVANCED_SEARCH,
                self::IS_NOT_VISIBLE
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }


    public function revert()
    {
    }

    public function getAliases() : array
    {
        return [];
    }

    public static function getDependencies() : array
    {
        return [];
    }

    /**
     * @return array
     */
    private function getAttributesList() {
        $selectEavList = $this->moduleDataSetup->getConnection()->select()->from(
            ['ca' => $this->moduleDataSetup->getTable('catalog_eav_attribute')],
            'attribute_id'
        )->join(
            ['ea' => $this->moduleDataSetup->getTable('eav_attribute')],
            'ca.attribute_id = ea.attribute_id',
            []
        )->where(
            'ea.attribute_code NOT IN (?)',
            ['name', 'description', 'related_keywords']
        )->where('ca.is_visible_in_advanced_search = ?', 1);

        return $this->moduleDataSetup->getConnection()->fetchAll($selectEavList);
    }
}
