<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceProduct
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class UpdateUnitOfMeasurementAttributes implements DataPatchInterface
{
    private const UNIT_OF_MEASURE_ATTRIBUTE = 'unit_of_measure';

    private const UNIT_OF_MEASURE_TO_REMOVE = [
        'unit_of_measure',
        'uom_quantity'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory,
        private Config                   $eavConfig
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Delete current unit of measure attributes
        foreach (self::UNIT_OF_MEASURE_TO_REMOVE as $attributeCode) {
            $eavSetup->removeAttribute(Product::ENTITY, $attributeCode);
        }

        // Re-create new unit of measure attribute
        $attribute = $this->eavConfig->getAttribute(
            Product::ENTITY,
            self::UNIT_OF_MEASURE_ATTRIBUTE
        );

        if (!$attribute || !$attribute->getId()) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                self::UNIT_OF_MEASURE_ATTRIBUTE,
                [
                    'group' => 'Mirakl Marketplace',
                    'type' => 'text',
                    'label' => 'Unit of Measure',
                    'input' => 'textarea',
                    'class' => '',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
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
                    'is_product_level_default' => true
                ]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
