<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Cron;

use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;

class CatalogSyncRemoveData
{

    /**
     * @var CollectionFactory $catalogSyncQueueCollectionFactory
     */
    private $_catalogSyncQueueCollectionFactory;
    
    /**
     * CatalogRemoveData constructor.
     * @param CollectionFactory $catalogSyncQueueCollectionFactory
     */
    public function __construct(
        CollectionFactory $catalogSyncQueueCollectionFactory
    ) {
        $this->_catalogSyncQueueCollectionFactory = $catalogSyncQueueCollectionFactory;
    }

    /**
     * Automatic delete 30 days old records action
     *
     * @return void
     */
    public function execute()
    {
        $catalogSyncQueueFactory = $this->_catalogSyncQueueCollectionFactory->create();
            $dateFilter = date('Y-m-d', strtotime('-30 day'));
            $catalogSyncQueueList = $catalogSyncQueueFactory->addFieldToFilter('created_at', ['lt' => $dateFilter]);
        if (!empty($catalogSyncQueueList->getSize())) {
            foreach ($catalogSyncQueueList as $record) {
                $record->delete();
            }
        }
    }
}
