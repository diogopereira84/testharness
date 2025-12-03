<?php
declare (strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateDltThreshold implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface
     * @param EavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Remove the attribute from all attribute sets except 1:
        $attributeSetIdToKeep = $eavSetup->getAttributeSetId(
            \Magento\Catalog\Model\Product::ENTITY,
            'PrintOnDemand'
        );
        $attributeId = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Product::ENTITY,
            'dlt_thresholds'
        );

        $setup->getConnection()->delete(
            $setup->getTable('eav_entity_attribute'),
            [
                'attribute_id = ?' => $attributeId,
                'attribute_set_id != ?' => $attributeSetIdToKeep,
            ]
        );

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [\Fedex\ProductCustomAtrribute\Setup\Patch\Data\CreateDltThreshold::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
