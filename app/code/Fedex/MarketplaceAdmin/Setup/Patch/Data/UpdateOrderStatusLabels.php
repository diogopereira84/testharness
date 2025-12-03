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
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class UpdateOrderStatusLabels implements DataPatchInterface
{
    /**
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        private CollectionFactory $statusCollectionFactory
    ) {
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply()
    {
        $statusCollection = $this->statusCollectionFactory->create();

        $confirmedStatus = $statusCollection->getItemByColumnValue('status', 'confirmed');
        if ($confirmedStatus) {
            $confirmedStatus->setLabel('Processing')->save();
        }

        $newStatus = $statusCollection->getItemByColumnValue('status', 'new');
        if ($newStatus) {
            $newStatus->setLabel('Ordered')->save();
        }
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
