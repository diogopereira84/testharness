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

class UpdateProductAttributeSetsId implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            Product::ENTITY,
            'product_attribute_sets_id',
            [
                'frontend_input' => 'select',
                'source_model' => \Fedex\CatalogMvp\Model\Source\AttributeSetIdOptions::class,
                'filterable_in_search' => 1,
            ]
        );
    
        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}