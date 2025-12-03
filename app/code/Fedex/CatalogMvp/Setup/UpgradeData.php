<?php

namespace Fedex\CatalogMvp\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(
        private EavSetupFactory $eavSetupFactory,
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.0.2', '<')) {

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'start_date_pod',
                [
                    'type' => 'datetime',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Start Date',
                    'input' => 'datetime',
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
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'end_date_pod',
                [
                    'type' => 'datetime',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'End Date',
                    'input' => 'datetime',
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

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            try {
                $eavSetup->getAttributeSetId(
                    \Magento\Catalog\Model\Product::ENTITY,
                    'PrintOnDemand'
                );
            } catch (\Exception $e) {
                $eavSetup->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
            }
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                'PrintOnDemand',
                'General',
                'start_date_pod',
                6
            );
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                'PrintOnDemand',
                'General',
                'end_date_pod',
                7
            );
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {

            $eavSetup->addAttribute(
                Product::ENTITY,
                'catalog_description',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Description',
                    'input' => 'textarea',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'wysiwyg_enabled' => false,
                    'is_html_allowed_on_front' => true,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => ''
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            try {
                $eavSetup->getAttributeSetId(
                    \Magento\Catalog\Model\Product::ENTITY,
                    'PrintOnDemand'
                );
            } catch (\Exception $e) {
                $eavSetup->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
            }
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                'PrintOnDemand',
                'content',
                'catalog_description',
                2
            );
        }

        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            try {
                $eavSetup->getAttributeSetId(
                    \Magento\Catalog\Model\Product::ENTITY,
                    'PrintOnDemand'
                );
            } catch (\Exception $e) {
                $eavSetup->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
            }
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                'PrintOnDemand',
                'search-engine-optimization',
                'related_keywords',
                2
            );
        }

        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            try {
                $printOnDemandAttrSetId =  $eavSetup->getAttributeSetId(
                    \Magento\Catalog\Model\Product::ENTITY,
                    'PrintOnDemand'
                );
                $attributeId = $eavSetup->getAttributeId($entityTypeId, 'meta_keyword');
                if ($attributeId) {
                    $this->moduleDataSetup->getConnection()->delete(
                        $this->moduleDataSetup->getTable('eav_entity_attribute'),
                        [
                            'attribute_id = ?' => $attributeId,
                            'attribute_set_id = ?' => $printOnDemandAttrSetId,
                        ]
                    );
                }
            } catch (\Exception $e) {
                $eavSetup->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
            }
        }
        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'related_keywords','is_global',
                 \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL);
        }
    }
}
