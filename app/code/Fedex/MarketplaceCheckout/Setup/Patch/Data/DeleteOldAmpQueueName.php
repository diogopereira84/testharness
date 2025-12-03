<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceCheckout
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class DeleteOldAmpQueueName implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Delete old AMP Queue name
        $table = $this->moduleDataSetup->getTable('queue');
        $condition = ['name = ?' => 'SendOrderQueueToMirakl'];
        $this->moduleDataSetup->getConnection()->delete($table, $condition);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases()
    {
        return [];
    }
}