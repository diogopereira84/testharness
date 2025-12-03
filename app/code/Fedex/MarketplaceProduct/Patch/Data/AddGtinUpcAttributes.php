<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Setup\EavSetup;

class AddGtinUpcAttributes implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private AttributeSetRepositoryInterface $attributeSetRepository
    ) {
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Validator\ValidateException
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeCodes = ['gtin', 'upc'];

        foreach ($attributeCodes as $attributeCode) {
            if (!$this->attributeExists($attributeCode)) {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeCode,
                    $this->getAttributeConfig($attributeCode)
                );
            }
        }

        $attributeSetId = $this->getAttributeSetIdByName($eavSetup, 'FXONonCustomizableProducts');
        if ($attributeSetId) {
            foreach ($attributeCodes as $attributeCode) {
                $eavSetup->addAttributeToSet(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeSetId,
                    'General',
                    $attributeCode
                );
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Get Attribute common config.
     *
     * @param string $attributeCode
     * @return array
     */
    private function getAttributeConfig(string $attributeCode): array
    {
        return [
            'type' => 'varchar',
            'input' => 'text',
            'label' => strtoupper($attributeCode),
            'required' => false,
            'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible' => true,
            'user_defined' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => true,
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'show_product_level_default' => false,
            'unique' => false,
            'validate_rules' => null,
            'group' => 'General',
            'position' => 0,
            'is_html_allowed_on_front' => false,
            'is_visible_on_front' => false,
            'used_in_product_listing' => false,
            'used_for_sort_by' => false,
            'is_promo_rule' => false,
            'mirakl_is_exportable' => true,
            'mirakl_is_variant' => false,
            'mirakl_is_localizable' => false
        ];
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    private function attributeExists(string $attributeCode): bool
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()
            ->from('eav_attribute', 'attribute_id')
            ->where('attribute_code = ?', $attributeCode)
            ->where('entity_type_id = ?', 4);

        return (bool) $connection->fetchOne($select);
    }

    /**
     * @param EavSetup $eavSetup
     * @param string $attributeSetName
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAttributeSetIdByName(EavSetup $eavSetup, string $attributeSetName): ?int
    {
        $attributeSetIds = $eavSetup->getAllAttributeSetIds(\Magento\Catalog\Model\Product::ENTITY);

        foreach ($attributeSetIds as $attributeSetId) {
            $attributeSet = $this->attributeSetRepository->get($attributeSetId);
            if ($attributeSet->getAttributeSetName() === $attributeSetName) {
                return (int) $attributeSetId;
            }
        }
        return null;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
