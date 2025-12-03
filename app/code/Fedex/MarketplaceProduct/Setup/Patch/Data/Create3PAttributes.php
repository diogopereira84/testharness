<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Fedex\ProductEngine\Setup\AddOptionToAttribute;

class Create3PAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * New Attributes.
     */
    private const MIRAKL_ATTRIBUTES = [
        'unit_of_measure',
        'uom_quantity',
        'production_days',
        'shape'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     * @param AddOptionToAttribute $addOptionToAttribute
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger,
        private AddOptionToAttribute $addOptionToAttribute
    )
    {
    }

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
            $this->addUnitOfMeasure($eavSetupFactoryObject);
            $this->addUomQuantity($eavSetupFactoryObject);
            $this->addProductionDays($eavSetupFactoryObject);
            $this->addShape($eavSetupFactoryObject);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * Add unit of measure attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addUnitOfMeasure(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'unit_of_measure',
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'varchar',
                'label'                   => 'Unit of Measure',
                'input'                   => 'select',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => false,
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

        $attrId = $eavSetupFactoryObject->getAttributeId(Product::ENTITY, 'unit_of_measure');
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'box', 'choice_id' => '1448988601300'],
                ['value' => 'each', 'choice_id' => '1448988601301']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    /**
     * Add UOM quantity attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addUomQuantity(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'uom_quantity',
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'varchar',
                'label'                   => 'UOM Quantity',
                'input'                   => 'multiselect',
                'backend'                 => ArrayBackend::class,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'default'                 => null,
                'mirakl_is_exportable'       => true,
                'visible_in_advanced_search' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true,
            ]
        );

        $attrId = $eavSetupFactoryObject->getAttributeId(Product::ENTITY, 'uom_quantity');
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '25', 'choice_id' => '1448988601302'],
                ['value' => '50', 'choice_id' => '1448988601303'],
                ['value' => '250', 'choice_id' => '1448988601304']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    /**
     * Add production days attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addProductionDays(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'production_days',
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'varchar',
                'label'                   => 'Production Days',
                'input'                   => 'text',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'default'                 => 0,
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
     * Add shape attribute.
     *
     * @param EavSetup $eavSetupFactoryObject
     * @return void
     */
    private function addShape(EavSetup $eavSetupFactoryObject): void
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'shape',
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'varchar',
                'label'                   => 'Shape',
                'input'                   => 'text',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => true,
                'filterable'              => false,
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
