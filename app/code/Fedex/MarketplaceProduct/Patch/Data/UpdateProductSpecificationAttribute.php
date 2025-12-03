<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Patch\Data;

use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class UpdateProductSpecificationAttribute implements DataPatchInterface
{
    private const SPECIFICATIONS = 'product_specifications';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory
    ) {}

    public function apply(): void
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attribute = $eavSetup->getAttribute(Product::ENTITY, self::SPECIFICATIONS);

        if ($attribute) {
            $eavSetup->updateAttribute(
                Product::ENTITY,
                self::SPECIFICATIONS,
                EavAttributeInterface::USED_IN_PRODUCT_LISTING,
                1
            );
        } else {
            $eavSetup->addAttribute(Product::ENTITY, self::SPECIFICATIONS, [
                'type' => 'text',
                'label' => 'specifications',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 100,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'user_defined' => true,
                'used_in_product_listing' => 1,
                'visible_on_front' => true,
                'unique' => false,
                'group' => 'About The Product Attributes',
                'backend' => '',
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'is_html_allowed_on_front' => true,
                'visible_in_advanced_search' => false,
                'used_for_sort_by' => false,
                'used_for_promo_rules' => false,
                'position' => 0,
                'note' => '',
            ]);
        }
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
