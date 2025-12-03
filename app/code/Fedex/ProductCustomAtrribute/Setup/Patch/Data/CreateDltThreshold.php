<?php
declare(strict_types=1);

namespace Fedex\ProductCustomAtrribute\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
 
class CreateDltThreshold implements DataPatchInterface
{
    const ATTRIBUTE_GROUP = 'General';
 
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
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'dlt_thresholds',
            [
                'type' => 'text',
                'label' => 'DLT Threshold',
                'input' => 'text',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => null,
                'group' => 'General',
                'sort_order' => 90,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        
        try {
            $eavSetup->getAttributeSetId(
                Product::ENTITY,
                'PrintOnDemand'
            );
        } catch (\Exception $e) {
            $eavSetup->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
        }
        $eavSetup->addAttributeToGroup(
            $entityTypeId,
            'PrintOnDemand',
            'General',
            'dlt_thresholds',
            22
        );

        $this->moduleDataSetup->endSetup();
    }
 
    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
 
    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
