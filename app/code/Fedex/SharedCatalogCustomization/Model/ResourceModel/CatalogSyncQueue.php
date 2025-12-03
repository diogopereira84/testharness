<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * CatalogSyncQueue ResourceModel
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CatalogSyncQueue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('catalog_sync_queue', 'id'); //here "catalog_sync_queue" is table name and "id" is the primary key of custom table
    }

    /**
     * Get catalog sync queue email sent ids
     *
     * @return array
     */
    public function getEmailSentIds()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            ['id']
        )->where(
            'email_sent  = ?',
            1
        );
        return $connection->fetchCol($select);
    }
}
