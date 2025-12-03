<?php
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * Makes `order_id` nullable and adds `quote_id` to support Political Disclosure
 * records created before order placement (EPro flow).
 *
 * Drops existing foreign keys and unique indexes involving `order_id`
 * before altering the column, since InnoDB doesn't allow column type
 * changes while constraints exist.
 *
 * Recreates a unique composite index (order_id, quote_id)
 * and restores the foreign key to `sales_order.entity_id`.
 */
class AddQuoteIdAndMakeOrderIdNullable implements SchemaPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {}

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName  = $this->moduleDataSetup->getTable('fedex_sales_order_political_disclosure');
        $salesOrder = $this->moduleDataSetup->getTable('sales_order');

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $schema = $connection->fetchOne('SELECT DATABASE()');

        $fks = $connection->fetchAll(
            "SELECT CONSTRAINT_NAME
               FROM information_schema.REFERENTIAL_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = :schema
                AND TABLE_NAME = :table",
            ['schema' => $schema, 'table' => $tableName]
        );

        foreach ($fks as $fk) {
            try {
                $connection->query(sprintf(
                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`;',
                    $tableName,
                    $fk['CONSTRAINT_NAME']
                ));
            } catch (\Throwable) {
            }
        }

        $indexes = $connection->getIndexList($tableName);
        foreach ($indexes as $indexName => $indexData) {
            if (in_array('order_id', $indexData['COLUMNS_LIST'], true)) {
                $connection->dropIndex($tableName, $indexName);
            }
        }

        $connection->modifyColumn(
            $tableName,
            'order_id',
            [
                'type'      => Table::TYPE_INTEGER,
                'unsigned'  => true,
                'nullable'  => true,
                'comment'   => 'Order ID (nullable for EPro flow)',
            ]
        );

        if (!$connection->tableColumnExists($tableName, 'quote_id')) {
            $connection->addColumn(
                $tableName,
                'quote_id',
                [
                    'type'      => Table::TYPE_INTEGER,
                    'unsigned'  => true,
                    'nullable'  => true,
                    'comment'   => 'Quote ID (used when Order not yet created)',
                    'after'     => 'order_id',
                ]
            );
        }

        $connection->addIndex(
            $tableName,
            $connection->getIndexName(
                $tableName,
                ['order_id', 'quote_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['order_id', 'quote_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );

        try {
            $newFkName = 'FK_' . strtoupper(md5(uniqid((string)microtime(), true)));
            $connection->addForeignKey(
                $newFkName,
                $tableName,
                'order_id',
                $salesOrder,
                'entity_id',
                Table::ACTION_CASCADE
            );
        } catch (\Throwable) {
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
 