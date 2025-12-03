<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Fedex\ProductEngine\Setup\AddOptionToAttribute;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class AddApparelSizeAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AddOptionToAttribute $addOptionToAttribute
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private AddOptionToAttribute $addOptionToAttribute
    )
    {
    }

    /**
     * @inheirtdoc
     *
     * @return $this|AddApparelSizeAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->addApparealSizeAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @inheirtdoc
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'apparel_size');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheirtdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheirtdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Create Apparel Size attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function addApparealSizeAttribute(EavSetup $eavSetup): void
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'apparel_size',
            [
                'type' => 'varchar',
                'label' => 'Apparel Size',
                'input' => 'multiselect',
                'required' => false,
                'sort_order' => '195',
                'position' => '195',
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default' => null,
                'visible' => true,
                'user_defined' => true,
                'searchable' => true,
                'visible_in_advanced_search' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'group' => 'Product Engine Attributes',
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_product_level_default' => true,
                'mirakl_is_exportable' => true
            ]
        );
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'apparel_size');
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'S', 'choice_id' => '5170536201600'],
                ['value' => 'M', 'choice_id' => '6611536202357'],
                ['value' => 'L', 'choice_id' => '5530536204340'],
                ['value' => 'XL', 'choice_id' => '9635536206060'],
                ['value' => '2XL', 'choice_id' => '2080536207791'],
                ['value' => '3XL', 'choice_id' => '2120536209470'],
                ['value' => '4XL', 'choice_id' => '3120536211063'],
                ['value' => '5XL', 'choice_id' => '9310536212664'],
                ['value' => 'XS', 'choice_id' => '2390536214410'],
                ['value' => 'One size', 'choice_id' => '7931536216120'],
                ['value' => '3-6m', 'choice_id' => '5691536217829'],
                ['value' => '6-12m', 'choice_id' => '2460536219435'],
                ['value' => '12-18m', 'choice_id' => '8310536221095'],
                ['value' => '18-24m', 'choice_id' => '2321536222783'],
                ['value' => 'XXL', 'choice_id' => '9831536224470']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }
}
