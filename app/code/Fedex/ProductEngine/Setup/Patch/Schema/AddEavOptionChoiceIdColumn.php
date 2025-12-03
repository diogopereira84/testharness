<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @codeCoverageIgnore
 */
class AddEavOptionChoiceIdColumn implements SchemaPatchInterface, PatchRevertableInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable('eav_attribute_option'),
            'choice_id',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment'  => 'Choice ID of the attribute',
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }


    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->moduleDataSetup->getConnection()->dropColumn(
            $this->moduleDataSetup->getTable('eav_attribute_option'),
            'choice_id'
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases() : array
    {
        return [];
    }

    public static function getDependencies() : array
    {
        return [];
    }
}
