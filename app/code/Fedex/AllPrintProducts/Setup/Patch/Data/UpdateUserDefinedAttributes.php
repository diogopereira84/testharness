<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\AllPrintProducts\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @codeCoverageIgnore
 */
class UpdateUserDefinedAttributes implements DataPatchInterface
{

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     */
    public function __construct(
        /**
         * ModuleDataSetupInterface
         */
        private ModuleDataSetupInterface $moduleDataSetup,
        /**
         * EavSetupFactory
         */
        private EavSetupFactory $eavSetupFactory
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->updateAttribute('catalog_product', 'in_store_pickup', 'is_user_defined', 1);
        $eavSetup->updateAttribute('catalog_product', 'has_canva_design', 'is_user_defined', 1);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [UpdateInStorePickUp::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
