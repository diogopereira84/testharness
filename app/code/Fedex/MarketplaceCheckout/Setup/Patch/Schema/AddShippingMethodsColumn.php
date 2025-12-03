<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceCheckout
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddShippingMethodsColumn implements SchemaPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable('mirakl_shop'),
            'shipping_methods',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Shipping methods',
            ]
        );
        $this->moduleDataSetup->endSetup();
    }
} ?>