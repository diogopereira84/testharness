<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * SharedCatalogSyncQueueConfiguration ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SharedCatalogSyncQueueConfiguration extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
         //here "shared_catalog_sync_queue_configuration" is table name and "id" is the primary key of custom table
        $this->_init('shared_catalog_sync_queue_configuration', 'id');
    }

    /**
     * Get Main Table
     *
     * @return string
     */
    public function getTableName()
    {
        return 'shared_catalog_sync_queue_configuration';
    }
}
