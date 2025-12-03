<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class ClearAttributeFromGroups implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param Config $eavConfig
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private AttributeSetFactory $attributeSetFactory,
        private Config $eavConfig
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create();

        $select = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('eav_attribute_set')
        )->where(
            'entity_type_id = :entity_type_id'
        );
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $sets = $this->moduleDataSetup->getConnection()->fetchAll($select, ['entity_type_id' => $entityTypeId]);

        $this->clearTextInStoreCTAAttributes($entityTypeId, $sets);
        $this->clearSelectInStoreCTAAttributes($entityTypeId, $sets);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    protected function clearTextInStoreCTAAttributes($entityTypeId, $sets)
    {

        $textAttributes = ['order_form', 'find_location'];

        foreach ($textAttributes as $attributeCode) {

            $attribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            if ($attribute && $attribute->getId()) {

                foreach ($sets as $set) {

                    if ($set['attribute_set_name'] == 'FXOInStoreProducts') {
                        continue;
                    }
                    $this->removeAttrFromAttributeSet(
                        $entityTypeId,
                        $set['attribute_set_id'],
                        $attribute->getAttributeId()
                    );
                }
            }
        }
    }

    protected function clearSelectInStoreCTAAttributes($entityTypeId, $sets)
    {

        $selectAttributes = ['order_form_target', 'find_location_target'];
        foreach ($selectAttributes as $attributeCode) {

            $attribute = $this->eavConfig->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            if ($attribute && $attribute->getId()) {

                foreach ($sets as $set) {

                    if ($set['attribute_set_name'] == 'FXOInStoreProducts') {
                        continue;
                    }
                    $this->removeAttrFromAttributeSet(
                        $entityTypeId,
                        $set['attribute_set_id'],
                        $attribute->getAttributeId()
                    );
                }
            }
        }
    }

    protected function removeAttrFromAttributeSet($entityTypeId, $attributeSetId, $attributeId)
{
        $where = [
            'entity_type_id = ?' => (int)$entityTypeId,
            'attribute_set_id = ?' => $attributeSetId,
            'attribute_id = ?' => $attributeId,
        ];
        $this->moduleDataSetup->getConnection()->delete('eav_entity_attribute', $where);
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
        return [CreateInStoreAttributeSet::class];
    }
}
