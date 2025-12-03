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

class UpgradeData implements DataPatchInterface
{
    private const MIRAKL_ITEMS_INVOICING_CONFIG= 'mirakl_connector/order_workflow/lock_mirakl_items_invoicing';

    private const MIRAKL_ITEMS_SHIPPING_CONFIG= 'mirakl_connector/order_workflow/lock_mirakl_items_shipping';

    /**
     * Construct.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->configWriter->save(self::MIRAKL_ITEMS_INVOICING_CONFIG, '0');
        $this->configWriter->save(self::MIRAKL_ITEMS_SHIPPING_CONFIG, '0');
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->configWriter->save(self::MIRAKL_ITEMS_INVOICING_CONFIG, '1');
        $this->configWriter->save(self::MIRAKL_ITEMS_SHIPPING_CONFIG, '1');
        $this->moduleDataSetup->getConnection()->endSetup();
    }


    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
