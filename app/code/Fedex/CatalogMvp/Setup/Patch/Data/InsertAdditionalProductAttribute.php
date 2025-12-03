<?php

/**
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Yogesh Suryawanshi <yogesh.suryawanshi.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InsertAdditionalProductAttribute implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'pod2_0_editable',
            [
                'type' => 'int',
                'label' => 'POD2.0 Editable',
                'input' => 'boolean',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '0',
                'group' => 'General',
                'sort_order' => 90,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $eavSetup->addAttributeToGroup(
            Product::ENTITY,
            'PrintOnDemand',
            'General',
            'pod2_0_editable',
            8
        );
        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return $this->getDependencies();
    }

    public static function getDependencies()
    {
        return [];
    }
}
