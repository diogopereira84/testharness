<?php
/**
 * @category    Fedex
 * @package     Fedex_ProductEngine
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class ConfigProductIdAttributes implements DataPatchInterface, PatchRevertableInterface
{
    const PRODUCT_ID_CODE = 'product_id';
    const PE_PRODUCT_ID_CODE = 'pe_product_id';

    /**
     * @var int
     */
    protected $productIdID;

    /**
     * @var int
     */
    protected $peProductIdID;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetup $eavSetupFactory
     * @param Attribute $eavAttribute
     */
    public function __construct(
        private ModuleDataSetupInterface    $moduleDataSetup,
        private EavSetup                    $eavSetupFactory,
        private Attribute                   $eavAttribute
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->configPeProductIdAttribute();
        $this->configProductIdAttribute();

        $this->removeProductEngineAttributesFromPrintOnDemand();

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases() : array
    {
        return [];
    }

    public static function getDependencies() : array
    {
        return [AddPeProductIdAttribute::class];
    }

    /**
     * @param EavSetup $eavSetup
     * @return void
     */
    private function configPeProductIdAttribute()
    {
        $this->peProductIdID = $this->eavAttribute->getIdByCode(Product::ENTITY, self::PE_PRODUCT_ID_CODE);
        $this->eavSetupFactory->updateAttribute(
            Product::ENTITY,
            $this->peProductIdID,
            'is_required',
            false
        );
    }

    /**
     * @param EavSetup $eavSetup
     * @return void
     */
    private function configProductIdAttribute()
    {
        $this->productIdID = $this->eavAttribute->getIdByCode(Product::ENTITY, self::PRODUCT_ID_CODE);
        $this->eavSetupFactory->updateAttribute(
            Product::ENTITY,
            $this->productIdID,
            'is_required',
            false
        );
    }

    /**
     * @param EavSetup $eavSetup
     * @return void
     */
    private function removeProductEngineAttributesFromPrintOnDemand()
    {
        try {
            $printOnDemandAttrSetId = $this->eavSetupFactory->getAttributeSetId(
                \Magento\Catalog\Model\Product::ENTITY,
                'PrintOnDemand'
            );
        } catch (\Exception $e) {
            $this->eavSetupFactory->addAttributeSet(Product::ENTITY, 'PrintOnDemand');
            $printOnDemandAttrSetId = $this->eavSetupFactory->getAttributeSetId(
                \Magento\Catalog\Model\Product::ENTITY,
                'PrintOnDemand'
            );
        }
        if ($printOnDemandAttrSetId) {
            $this->eavSetupFactory->removeAttributeGroup(
                \Magento\Catalog\Model\Product::ENTITY,
                $printOnDemandAttrSetId,
                'Product Engine Attributes'
            );
            $this->moduleDataSetup->getConnection()->delete(
                $this->moduleDataSetup->getTable('eav_entity_attribute'),
                [
                    'attribute_id IN (?)' => [$this->productIdID, $this->peProductIdID],
                    'attribute_set_id = ?' => $printOnDemandAttrSetId,
                ]
            );
        }
    }
}
