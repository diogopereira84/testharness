<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * @codeCoverageIgnore
 */
class AddShippingStatus implements DataPatchInterface, PatchVersionInterface
{
    /**
     * AddNewShipmentStatus constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->insertNewStatus();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Insert new status into shipment_status table
     *
     * @return void
     */
    private function insertNewStatus()
    {
        $connection = $this->moduleDataSetup->getConnection();

        $maxValue = $connection->fetchOne(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('shipment_status'),
                    ['max_value' => new \Zend_Db_Expr('MAX(value)')])
        );

        $newValue = (int)$maxValue + 1;

        $statusData = [
            'value' => $newValue,
            'label' => 'Shipping',
            'key' => 'shipping',
        ];

        $this->moduleDataSetup->getConnection()
            ->insert($this->moduleDataSetup->getTable('shipment_status'), $statusData);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
