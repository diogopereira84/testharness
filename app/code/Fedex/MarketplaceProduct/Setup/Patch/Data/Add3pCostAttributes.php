<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Fedex\ProductEngine\Setup\AddOptionToAttribute;

class Add3pCostAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * New Attributes.
     */
    private const MIRAKL_ATTRIBUTES = [
        'unit_cost',
        'base_quantity',
        'base_price',
        'is_delivery_only'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     * @param AddOptionToAttribute $addOptionToAttribute
     * @param CollectionFactory $attributeFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger,
        private AddOptionToAttribute $addOptionToAttribute,
        private CollectionFactory $attributeFactory
    ) {}

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (self::MIRAKL_ATTRIBUTES as $code) {
            $eavSetupFactoryObject->removeAttribute(
                Product::ENTITY,
                $code
            );
        }

        try {
            $this->addUnitCost($eavSetupFactoryObject);
            $this->addBaseQuantity($eavSetupFactoryObject);
            $this->addBasePrice($eavSetupFactoryObject);
            $this->addDeliveryOnlyTag($eavSetupFactoryObject);
            $this->clearCreatedAttributeFromPrintOnDemandAttributeSet($eavSetupFactoryObject);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * @return void
     */
    private function clearCreatedAttributeFromPrintOnDemandAttributeSet($eavSetup)
    {
        $attributeInfo = $this->attributeFactory->create()
            ->addFieldToFilter('entity_type_id', '4')
            ->addFieldToFilter('attribute_code', ['IN' => ['unit_cost', 'base_quantity', 'base_price']]);

        $attributeSetId = $eavSetup->getAttributeSetId(
            \Magento\Catalog\Model\Product::ENTITY,
            'PrintOnDemand'
        );

        foreach ($attributeInfo as $attribute) {
            $this->moduleDataSetup->getConnection()->delete(
                $this->moduleDataSetup->getTable('eav_entity_attribute'),
                [
                    'attribute_id = ?' => $attribute->getId(),
                    'attribute_set_id = ?' => $attributeSetId,
                ]
            );
        }

    }

    /**
     * Add unit cost attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addUnitCost(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'unit_cost',
            [
                'group'                   => 'Mirakl Marketplace',
                'label'                   => 'Unit Cost',
                'type'                    => 'decimal',
                'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                'input'                   => 'price',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'default'                 => null,
                'mirakl_is_exportable'    => true,
                'visible_in_advanced_search' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true,
            ]
        );
    }

    /**
     * Add base quantity attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addBaseQuantity(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'base_quantity',
            [
                'group'                   => 'Mirakl Marketplace',
                'label'                   => 'Base Quantity',
                'type'                    => 'varchar',
                'input'                   => 'text',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'default'                 => null,
                'mirakl_is_exportable'    => true,
                'visible_in_advanced_search' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true,
            ]
        );
    }

    /**
     * Add base price attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addBasePrice(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'base_price',
            [
                'group'                   => 'Mirakl Marketplace',
                'label'                   => 'Base Price',
                'type'                    => 'decimal',
                'backend'                 => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                'input'                   => 'price',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'default'                 => null,
                'mirakl_is_exportable'    => true,
                'visible_in_advanced_search' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true,
            ]
        );
    }

    /**
     * Add Delivery Only Tag attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addDeliveryOnlyTag(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'is_delivery_only',
            [
                'type' => 'int',
                'label' => 'Display Delivery Only Tag',
                'input' => 'boolean',
                'group' => 'General',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => 0,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => '',
                'filterable_in_search' => true,
                'sort_order' => '501',
            ]
        );
    }

    /**
     * Revert
     *
     * @return void
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetupFactoryObject = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach (self::MIRAKL_ATTRIBUTES as $attributes) {
            $eavSetupFactoryObject->removeAttribute(
                Product::ENTITY,
                $attributes
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
