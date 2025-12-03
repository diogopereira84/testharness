<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Setup\Patch\Data;

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
class ProductAttributesUpdate implements DataPatchInterface, PatchRevertableInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private AddOptionToAttribute $addOptionToAttribute
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $this->addPaperSizeAttribute($eavSetup);
        $this->addPaperTypeAttribute($eavSetup);
        $this->addEnvelopeAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert()
    {
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [
            ProductAttributes::class
        ];
    }

    private function addPaperTypeAttribute(EavSetup $eavSetup)
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'paper_type');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '120lb Gloss Cardstock', 'choice_id' => '1632893984783']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addPaperSizeAttribute(EavSetup $eavSetup)
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'paper_size');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => '3.50x2.0', 'choice_id' => '1533241903556'],
                ['value' => '5.24x7.24', 'choice_id' => '1533111699862'],
                ['value' => '22x28', 'choice_id' => '1449002216146'],
                ['value' => '18x20', 'choice_id' => '1584636929056'],
                ['value' => '20x24', 'choice_id' => '1453908464424'],
                ['value' => '18x36', 'choice_id' => '1584636929057'],
                ['value' => '20x20', 'choice_id' => '1584636929058'],
                ['value' => '24x24', 'choice_id' => '1584636929059'],
                ['value' => '36x36', 'choice_id' => '1584636929060'],
                ['value' => '11x20', 'choice_id' => '1584636929048'],
                ['value' => '11x24', 'choice_id' => '1584636929049'],
                ['value' => '20x36', 'choice_id' => '1584638639411'],
                ['value' => '11x36', 'choice_id' => '1584636929050'],
                ['value' => '16x16', 'choice_id' => '1584636929051'],
                ['value' => '16x18', 'choice_id' => '1584636929052'],
                ['value' => '10x10', 'choice_id' => '1453908447605'],
                ['value' => '16x24', 'choice_id' => '1584636929053'],
                ['value' => '16x36', 'choice_id' => '1584636929054'],
                ['value' => '18x18', 'choice_id' => '1584636929055'],
                ['value' => '16x20', 'choice_id' => '1449002053197'],
                ['value' => '11x11', 'choice_id' => '1584636929045'],
                ['value' => '11x16', 'choice_id' => '1584636929046'],
                ['value' => '11x18', 'choice_id' => '1584636929047'],
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

    private function addEnvelopeAttribute(EavSetup $eavSetup)
    {
        $attrId = $eavSetup->getAttributeId(Product::ENTITY, 'envelope');
        $eavSetup->updateAttribute(Product::ENTITY, $attrId, 'is_product_level_default', true);
        $attrOptions = [
            'attribute_id' => $attrId,
            'values' => [
                ['value' => 'None', 'choice_id' => '1634129308274']
            ]
        ];
        $this->addOptionToAttribute->execute($attrOptions);
    }

}
