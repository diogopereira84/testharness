<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess;

use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcess as CatalogSyncQueueCleanupProcessModel;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess as
CatalogSyncQueueCleanupProcessResourceModel;

/**
 * CatalogSyncQueueCleanupProcess Collection
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(CatalogSyncQueueCleanupProcessModel::class, CatalogSyncQueueCleanupProcessResourceModel::class);
    }
}
