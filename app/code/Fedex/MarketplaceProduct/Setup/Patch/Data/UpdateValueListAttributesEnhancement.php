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
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class UpdateValueListAttributesEnhancement implements DataPatchInterface, PatchRevertableInterface
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
     * @return UpdateValueListAttributesEnhancement|$this
     */
    public function apply(): UpdateValueListAttributesEnhancement|static
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Update finishing attribute
        $this->updateFinishingAttribute($eavSetup);

        // Update fxo_product_type attribute
        $this->updateFxoProductTypeAttribute($eavSetup);

        // Update material_type attribute
        $this->updateMaterialTypeAttribute($eavSetup);

        // Update sides attribute
        $this->updateSidesAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @return void
     */
    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
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

    private function updateFinishingAttribute(EavSetup $eavSetup): void
    {
        $this->updateAttributeWithOptions($eavSetup, 'finishing', [
            'Direct to Garment' => '5170536201600',
            'Embroidery' => '6611536202357'
        ]);
    }

    /**
     * Update fxo_product_type attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function updateFxoProductTypeAttribute(EavSetup $eavSetup): void
    {
        $this->updateAttributeWithOptions($eavSetup, 'fxo_product_type', [
            'T-shirts' => '3147202168115',
            'Hoodies & Sweatshirts' => '9192189630272',
            'Kids & Baby Clothing' => '1344277057011',
            'Hats' => '5121746298826',
            'Accessories' => '8159318238904',
            'Sticker Sheets' => '2128465830577'
        ]);
    }

    /**
     * Update material_type attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function updateMaterialTypeAttribute(EavSetup $eavSetup): void
    {
        $this->updateAttributeWithOptions($eavSetup, 'material_type', [
            'Kraft' => '4162503207583',
            'Standard White' => '6178631402629',
            'Premium White' => '7193240825945'
        ]);
    }

    /**
     * Update sides attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function updateSidesAttribute(EavSetup $eavSetup): void
    {
        $this->updateAttributeWithOptions($eavSetup, 'sides', [
            'Outside' => '8246883419456',
            'Inside' => '1291485153320',
            '2-Sided' => '7278739164038'
        ]);
    }

    /**
     * Add options.
     *
     * @param EavSetup $eavSetup
     * @param string $attributeCode
     * @param array $options
     * @return void
     */
    private function updateAttributeWithOptions(EavSetup $eavSetup, string $attributeCode, array $options): void
    {
        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, $attributeCode);
        $attrOptions = [
            'attribute_id' => $attributeId,
            'values' => array_map(function ($value, $choiceId) {
                return ['value' => $value, 'choice_id' => $choiceId];
            }, array_keys($options), $options)
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }
}
