<?php
/**
 * @category     Fedex
 * @package      Fedex_FXOCMConfigurator
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Matias Hidalgo <matias.hidalgo.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveDocumentIdIndexFromOrderRetentionPeriod implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    ) {}

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $connection = $this->moduleDataSetup->getConnection();
            $tableName = $this->moduleDataSetup->getTable('order_retention_period');

            // Check if the index exists before attempting to drop it
            $indexName = 'ORDER_RETENTION_PERIOD_DOCUMENT_ID';
            $indexes = $connection->getIndexList($tableName);
            if (isset($indexes[$indexName])) {
                $connection->dropIndex($tableName, $indexName);
            }
        } finally {
            $this->moduleDataSetup->getConnection()->endSetup();
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
