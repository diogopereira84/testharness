<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\AllPrintProducts\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @codeCoverageIgnore
 */
class UpdateInStorePickUp implements DataPatchInterface
{
    private const IN_STORE_PICKUP_ATTRIBUTE_CODE = 'in_store_pickup';
    private const IN_STORE_PICKUP_AVAILABLE = 'Available';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     */
    public function __construct(
        /**
         * ModuleDataSetupInterface
         */
        private ModuleDataSetupInterface $moduleDataSetup,
        /**
         * EavSetupFactory
         */
        private EavSetupFactory $eavSetupFactory,
        /**
         * EavConfig
         */
        private Config $eavConfig
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $existingAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'in_store_pickup');
        $options = [];
        $options['value']['Available'][0] = 'Available';
        $options['value']['Not Available'][0] = 'Not Available';
        if ($existingAttribute && $existingAttribute->getAttributeId()) {

            /** @var \Magento\Eav\Api\Data\AttributeOptionInterface[] $attributesOptions */
            $attributesOptions = $existingAttribute->getOptions();
            foreach ($attributesOptions as $attributeOption) {
                $label = $attributeOption->getLabel() instanceof Phrase
                    ? $attributeOption->getLabel()->getText()
                    : $attributeOption->getLabel();
                unset($options['value'][$label]);
            }

            if (!empty($options['value'])) {
                $options['attribute_id'] = $existingAttribute->getAttributeId();
                $eavSetup->addAttributeOption($options);
            }

            $existingAttribute->addData([
                'type' => 'varchar',
                'backend_model' => null,
                'source_model' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'is_user_defined' => true,
                'default_value' => $this->getDefaultOptionIdForInStorePickup() ?? null
            ]);
            $existingAttribute->save();

            $attributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);
            foreach ($attributeSetIds as $attributeSetId) {
                if ($attributeSetId) {
                    $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'General');
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $attributeSetId,
                        $groupId,
                        'in_store_pickup',
                        '500'
                    );
                }
            }

        } else {

            $eavSetup->addAttribute(Product::ENTITY, 'in_store_pickup', [
                'type' => 'varchar',
                'frontend' => '',
                'label' => 'In-Store Pickup',
                'input' => 'select',
                'class' => '',
                'group' => 'General',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
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
                'option' => $options,
                'sort_order' => '500',
            ]);

            $existingAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'in_store_pickup');
            $existingAttribute->addData([
                'attribute_id' => $eavSetup->getAttributeId('catalog_product', 'in_store_pickup'),
                'default_value' => $this->getDefaultOptionIdForInStorePickup() ?? null
            ]);
            $existingAttribute->save();

            $attributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);
            foreach ($attributeSetIds as $attributeSetId) {
                if ($attributeSetId) {
                    $groupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'General');
                    $eavSetup->addAttributeToGroup(
                        Product::ENTITY,
                        $attributeSetId,
                        $groupId,
                        'in_store_pickup',
                        '500'
                    );
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return int|string
     */
    private function getDefaultOptionIdForInStorePickup()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()->from(
            ['eaov' => 'eav_attribute_option_value'],
            'eaov.option_id'
        )
            ->joinInner(
                ['eao' => 'eav_attribute_option'],
                'eao.option_id = eaov.option_id',
                ''
            )
            ->joinInner(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = eao.attribute_id',
                ''
            )
            ->where('eaov.value = ?', self::IN_STORE_PICKUP_AVAILABLE)
            ->where('ea.attribute_code = ?', self::IN_STORE_PICKUP_ATTRIBUTE_CODE);

        $value = $connection->fetchOne($select);

        return $value !== false ? $value : 0;
    }
}
