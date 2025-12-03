<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Niket Kanoi <niket.kanoi.osv@fedex.com>
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
class UpdateMarketPlaceAttributes implements DataPatchInterface
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
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        /**
         * Unset/Update Choice IDs for marketplace attributes
         */
        $this->updateApparealSizeAttribute($eavSetup);
        $this->updateMaterialTypeAttribute($eavSetup);
        $this->updateFxoProductTypeAttribute($eavSetup);
        $this->updateFinishingAttribute($eavSetup);
        $this->updateSidesAttribute($eavSetup);

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
        return [
            AddApparelSizeAttribute::class,
            UpdateValueListAttributesEnhancement::class
        ];
    }

    /**
     * Update apparel_size attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function updateApparealSizeAttribute(EavSetup $eavSetup): void
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'apparel_size');
        if ($attrId) {
            $attrOptions = [
                'attribute_id' => $attrId,
                'values' => [
                    ['value' => 'S', 'choice_id' => '0'],
                    ['value' => 'M', 'choice_id' => '0'],
                    ['value' => 'L', 'choice_id' => '0'],
                    ['value' => 'XL', 'choice_id' => '0'],
                    ['value' => '2XL', 'choice_id' => '0'],
                    ['value' => '3XL', 'choice_id' => '0'],
                    ['value' => '4XL', 'choice_id' => '0'],
                    ['value' => '5XL', 'choice_id' => '0'],
                    ['value' => 'XS', 'choice_id' => '0'],
                    ['value' => 'One size', 'choice_id' => '0'],
                    ['value' => '3-6m', 'choice_id' => '0'],
                    ['value' => '6-12m', 'choice_id' => '0'],
                    ['value' => '12-18m', 'choice_id' => '0'],
                    ['value' => '18-24m', 'choice_id' => '0'],
                    ['value' => 'XXL', 'choice_id' => '0']
                ]
            ];
            $this->addOptionToAttribute->execute($attrOptions);
        }
    }

    /**
     * Update material_type attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function updateMaterialTypeAttribute(EavSetup $eavSetup): void
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'material_type');
        if ($attrId) {
            $attrOptions = [
                'attribute_id' => $attrId,
                'values' => [
                    ['value' => 'Kraft', 'choice_id' => '0'],
                    ['value' => 'Standard White', 'choice_id' => '0'],
                    ['value' => 'Premium White', 'choice_id' => '0']
                ]
            ];
            $this->addOptionToAttribute->execute($attrOptions);
        }
    }

    /**
     * Update fxo_product_type attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function updateFxoProductTypeAttribute(EavSetup $eavSetup): void
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'fxo_product_type');
        if ($attrId) {
            $attrOptions = [
                'attribute_id' => $attrId,
                'values' => [
                    ['value' => 'T-shirts', 'choice_id' => '0'],
                    ['value' => 'Hoodies & Sweatshirts', 'choice_id' => '0'],
                    ['value' => 'Kids & Baby Clothing', 'choice_id' => '0'],
                    ['value' => 'Hats', 'choice_id' => '0'],
                    ['value' => 'Accessories', 'choice_id' => '0'],
                    ['value' => 'Sticker Sheets', 'choice_id' => '0']
                ]
            ];
            $this->addOptionToAttribute->execute($attrOptions);
        }
    }

    /**
     * Update finishing attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function updateFinishingAttribute(EavSetup $eavSetup): void
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'finishing');
        if ($attrId) {
            $attrOptions = [
                'attribute_id' => $attrId,
                'values' => [
                    ['value' => 'Direct to Garment', 'choice_id' => '0'],
                    ['value' => 'Embroidery', 'choice_id' => '0']
                ]
            ];
            $this->addOptionToAttribute->execute($attrOptions);
        }
    }

    /**
     * Update sides attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    private function updateSidesAttribute(EavSetup $eavSetup): void
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'sides');
        if ($attrId) {
            $attrOptions = [
                'attribute_id' => $attrId,
                'values' => [
                    ['value' => 'Outside', 'choice_id' => '0'],
                    ['value' => 'Inside', 'choice_id' => '0'],
                    ['value' => '2-Sided', 'choice_id' => '0']
                ]
            ];
            $this->addOptionToAttribute->execute($attrOptions);
        }
    }
}
