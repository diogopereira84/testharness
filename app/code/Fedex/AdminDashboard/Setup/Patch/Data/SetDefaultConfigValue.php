<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\AdminDashboard\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SetDefaultConfigValue implements DataPatchInterface
{
    /**
     * Store config path
     * @var string
     */
    private string $path = "sales/dashboard/use_aggregated_data";
    /**
     * Store config path value
     * @var int
     */
    private int $configValue = 1;

    /**
     * Update value of Sales -> Sales -> Dashboard -> Use Aggregated Data
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $storeConfigWriter
     */
    public function __construct(
        readonly private ModuleDataSetupInterface $moduleDataSetup,
        readonly private WriterInterface          $storeConfigWriter
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->storeConfigWriter->save(
            $this->path,
            $this->configValue,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->moduleDataSetup->endSetup();
        return $this;
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
}
