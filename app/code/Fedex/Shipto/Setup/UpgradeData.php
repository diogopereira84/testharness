<?php

namespace Fedex\Shipto\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * {​​​​​@inheritdoc}​​​​​
     * @codeCoverageIgnore
     */
    public function __construct(
        private EavSetupFactory $eavSetupFactory
    )
    {
    }
    
    /**
     * {​​​​​@inheritdoc}​​​​​
     * @codeCoverageIgnore
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'admin_user_id',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Admin User ID',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
        }
        
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
            foreach ($attributeSetIds as $attributeSetId) {
                
                $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, "General");
                $eavSetup->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $groupId,
                    'admin_user_id',
                    null
                );
            }
        }
    }
}
