<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UpdateDefaultSortByConfig implements DataPatchInterface, PatchRevertableInterface
{
    private const CATALOG_FRONTEND_DEFAULT_SORT_BY = 'catalog/frontend/default_sort_by';
    private const DEFAULT_SORT_BY_NAME = 'name';
    private const DEFAULT_SORT_BY_UPDATED_AT = 'updated_at';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private WriterInterface $configWriter
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->configWriter->save(
            self::CATALOG_FRONTEND_DEFAULT_SORT_BY,
            self::DEFAULT_SORT_BY_UPDATED_AT,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->configWriter->save(
            self::CATALOG_FRONTEND_DEFAULT_SORT_BY,
            self::DEFAULT_SORT_BY_NAME,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
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
