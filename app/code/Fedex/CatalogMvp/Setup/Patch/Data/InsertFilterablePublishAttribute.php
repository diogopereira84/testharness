<?php

/**
 * @copyright Copyright (c) 2024 Fedex.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InsertFilterablePublishAttribute implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }
   /**
     * apply function
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            'published',
            [
                'type' => 'int',
                'label' => 'Published',
                'input' => 'boolean',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'filterable_in_search' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'default' => '0',
                'group' => 'General',
                'sort_order' => 90,
                'unique' => false,
                'apply_to' => 'simple,grouped,bundle,configurable,virtual'
            ]
        );
        $this->moduleDataSetup->endSetup();
    }
    /**
     * getAliases function
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }
    /**
     * getDependencies function
     */
    public static function getDependencies()
    {
        return [];
    }
}
