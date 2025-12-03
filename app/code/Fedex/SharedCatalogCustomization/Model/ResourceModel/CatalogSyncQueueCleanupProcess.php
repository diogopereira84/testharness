<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel;

/**
 * CatalogSyncQueueCleanupProcess ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogSyncQueueCleanupProcess extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('catalog_sync_queue_cleanup_process', 'id');
    }

    /**
     * Get catalog sync queue cleanup process status completed ids
     *
     * @return array
     */
    public function getStatusCompleted()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            ['id']
        )->where(
            'status  = ?',
            'completed'
        );
        return $connection->fetchCol($select);
    }
}
