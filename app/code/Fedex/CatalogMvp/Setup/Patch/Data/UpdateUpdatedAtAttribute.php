<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class UpdateUpdatedAtAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private const UPDATED_AT_ATTRIBUTE = 'updated_at';
    private const USED_FOR_SORT_BY = 'used_for_sort_by';
    private const FRONTEND_LABEL = 'frontend_label';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            Product::ENTITY,
            self::UPDATED_AT_ATTRIBUTE,
            self::USED_FOR_SORT_BY,
            1
        );

        $eavSetup->updateAttribute(
            Product::ENTITY,
            self::UPDATED_AT_ATTRIBUTE,
            self::FRONTEND_LABEL,
            'Updated At'
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            Product::ENTITY,
            self::UPDATED_AT_ATTRIBUTE,
            self::USED_FOR_SORT_BY,
            0
        );

        $eavSetup->updateAttribute(
            Product::ENTITY,
            self::UPDATED_AT_ATTRIBUTE,
            self::FRONTEND_LABEL,
            null
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
