<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2025 FedEx
 * @author       Prasanta Hatui <phatui@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class AddLteIdentifierToQuote implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    public function apply(): void
    {
        $installer = $this->schemaSetup;
        $installer->startSetup();

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote'), 'lte_identifier')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote'),
                'lte_identifier',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'length' => 255,
                    'comment' => 'Local tax exemption identifier'
                ]
            );
        }

        $installer->endSetup();
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
