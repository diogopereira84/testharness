<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Manuel Rosario <manuel.rosario.osv@fedex.com>
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

class CreateWeightUnitAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * New Attributes.
     */
    private const MIRAKL_ATTRIBUTES = [
        'weight_unit'
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
            $this->addWeightUnit($eavSetupFactoryObject);

        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    private function addWeightUnit(EavSetup $eavSetupFactoryObject)
    {
        $eavSetupFactoryObject->addAttribute(
            Product::ENTITY,
            'weight_unit',
            [
                'group' => 'Mirakl Marketplace',
                'type' => 'varchar',
                'label' => 'Weight Unit',
                'input' => 'select',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => 'simple',
                'is_configurable' => false,
                'used_in_product_listing' => true,
                'default' => null,
                'mirakl_is_exportable' => true,
                'visible_in_advanced_search' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true,
            ]
        );

        $attrId = $eavSetupFactoryObject->getAttributeId(Product::ENTITY, 'weight_unit');
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'ounce', 'choice_id' => null],
                ['value' => 'pound', 'choice_id' => null]
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
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
