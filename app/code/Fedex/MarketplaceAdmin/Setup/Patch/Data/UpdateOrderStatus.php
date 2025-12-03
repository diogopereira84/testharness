<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;

class UpdateOrderStatus implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private StatusFactory $orderStatusFactory
    )
    {
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $statusLabels = ['Assigned', 'In Progress'];

        foreach ($statusLabels as $statusLabel) {
            $orderStatus = $this->orderStatusFactory->create();
            $orderStatus->load($statusLabel, 'label');

            if ($orderStatus->getId()) {
                if ($statusLabel == 'Assigned') {
                    $orderStatus->setLabel('Ordered');
                } elseif ($statusLabel == 'In Progress') {
                    $orderStatus->setLabel('Processing');
                }

                $orderStatus->save();
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Revert patch
     *
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->startSetup();

        $originalLabels = ['Ordered', 'Processing'];

        foreach ($originalLabels as $originalLabel) {
            $orderStatus = $this->orderStatusFactory->create();
            $orderStatus->load($originalLabel, 'label');

            if ($orderStatus->getId()) {
                if ($originalLabel == 'Ordered') {
                    $orderStatus->setLabel('Assigned');
                } elseif ($originalLabel == 'Processing') {
                    $orderStatus->setLabel('In Progress');
                }

                $orderStatus->save();
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }
}
