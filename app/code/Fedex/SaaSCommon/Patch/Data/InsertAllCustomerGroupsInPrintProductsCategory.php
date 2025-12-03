<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Patch\Data;

use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InsertAllCustomerGroupsInPrintProductsCategory implements DataPatchInterface
{
    private const ALLOW = -1;
    private const TABLE = 'magento_catalogpermissions';

    public function __construct(
        private readonly ModuleDataSetupInterface    $moduleDataSetup,
        private readonly ResourceConnection          $resource,
        private readonly ConfigInterface             $configInterface
    ) {}

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $printProductB2bCategory = $this->configInterface->getB2bPrintProductsCategory();
        $conn  = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);

        $permissionId = $conn->fetchOne(
            sprintf(
                'SELECT permission_id FROM `%s`
                     WHERE category_id = :cid
                       AND website_id IS NULL
                       AND customer_group_id IS NULL
                     LIMIT 1',
                $table
            ),
            ['cid' => $printProductB2bCategory]
        );

        if (!$permissionId) {
            $conn->insert($table, [
                'category_id' => $printProductB2bCategory,
                'website_id' => null,
                'customer_group_id' => null,
                'grant_catalog_category_view' => self::ALLOW,
                'grant_catalog_product_price' => self::ALLOW,
                'grant_checkout_items' => self::ALLOW,
            ]);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
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
