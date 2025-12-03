<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Cron;

use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory;
use Fedex\SharedCatalogCustomization\Helper\EmailData;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CatalogSyncQueueComplete
{
    /**
     * CatalogSyncQueueComplete constructor.
     *
     * @param CollectionFactory $catalogSyncQueueCollectionFactory
     * @param EmailData $emailHelperData
     * @param ManageCatalogItems $manageCatalogItems
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private CollectionFactory $catalogSyncQueueCollectionFactory,
        private EmailData $emailHelperData,
        protected ManageCatalogItems $manageCatalogItems,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Automatic scheduler sync action
     *
     * @return void
     */
    public function execute()
    {
        $sharedCatalogSyncCollection = $this->catalogSyncQueueCollectionFactory->create();
        $sharedCatalogSyncQueueItemList = $sharedCatalogSyncCollection->addFieldToFilter(
            'status',
            ['eq' => $this->manageCatalogItems::STATUS_PROCESSING]
        );

        if (!empty($sharedCatalogSyncQueueItemList->getSize())) {

            foreach ($sharedCatalogSyncQueueItemList as $key => $sharedCatalogSyncQueueData) {
                if ($this->toggleConfig->getToggleConfigValue("explorers_catalog_migration") && $sharedCatalogSyncQueueData->getIsImport()) {
                    $this->emailHelperData->checkImportQueueItemStatus($sharedCatalogSyncQueueData);
                } else {
                    $this->emailHelperData->checkQueueItemStatus($sharedCatalogSyncQueueData);
                }
            }
        }
    }
}
