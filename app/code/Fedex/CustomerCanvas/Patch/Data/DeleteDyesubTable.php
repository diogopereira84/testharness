<?php
namespace Fedex\CustomerCanvas\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DeleteDyesubTable implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Apply the patch
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('dyesub_expired_products');

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Patch dependencies
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Patch aliases
     */
    public function getAliases()
    {
        return [];
    }
}
