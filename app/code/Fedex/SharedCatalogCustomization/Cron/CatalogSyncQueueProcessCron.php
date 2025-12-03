<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Cron;

use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CatalogSyncQueueProcessCron
{
	/**
     * @var \Fedex\SharedCatalogCustomization\Helper\Data $helperData
     */
    protected $catalogSyncHelperData;
    private \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueue $catalogSyncQueue;

	/**
	 * CatalogSyncQueueProcessCron constructor.
     * @param \Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueFactory $catalogSyncQueueFactory
     * @param \Fedex\SharedCatalogCustomization\Helper\Data $helperData
     * @param \Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory $catalogSyncCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
	 * @param ToggleConfig $toggleConfig
	 */
	public function __construct(
	    protected CatalogSyncQueueFactory $catalogSyncQueueFactory,
	    \Fedex\SharedCatalogCustomization\Helper\Data $helperData,
	    protected \Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue\CollectionFactory $catalogSyncCollectionFactory,
	    protected LoggerInterface $logger,
	    protected ToggleConfig $toggleConfig
	)
	{
		$this->catalogSyncHelperData = $helperData;
	}

	/**
     * Check pending sync request and put in queue one by one
     *
	 * @return none
     */
	public function execute()
	{
		$catalogSyncQueueCollection = $this->catalogSyncCollectionFactory->create();

		$catalogSyncQueueData = $catalogSyncQueueCollection->addFieldToFilter(
			'status',
			['eq' => $this->catalogSyncHelperData::STATUS_PENDING]
		);
		$catalogSyncQueueData = $catalogSyncQueueCollection->addFieldToFilter(
			'legacy_catalog_root_folder_id',
			['neq' => null]
		);
		if ($this->toggleConfig->getToggleConfigValue('explorers_catalog_migration')) {

			$catalogSyncQueueData = $catalogSyncQueueCollection->addFieldToFilter('is_import', ['neq' => 1]);
		}

        if (!empty($catalogSyncQueueCollection->getSize())) {

			// @codeCoverageIgnoreStart
			$syncQueueData = $catalogSyncQueueData->getFirstItem();

			// Change status to processing inside catalog_sync_queue.
			$this->catalogSyncQueue = $this->catalogSyncQueueFactory->create();
			$this->catalogSyncQueue->setId($syncQueueData->getId());
			$this->catalogSyncQueue->setStatus($this->catalogSyncHelperData::STATUS_PROCESSING);
            try {
                $this->catalogSyncQueue->save();
				$this->logger->info(__METHOD__.':'.__LINE__.':Catalog sync queue processing success.');
            } catch (Exception $exception) {
				$this->logger->error(__METHOD__.':'.__LINE__.':Catalog sync queue processing error:' . $exception->getMessage());
			}

			// Call Helper function to set the mysql queues and assign into RabbitMQ for processing
			$this->catalogSyncHelperData->setCatalogSyncRequest(
				$syncQueueData->getId(),
				$syncQueueData->getSharedCatalogId(),
				$syncQueueData->getLegacyCatalogRootFolderId(),
				$syncQueueData->getStoreId()
			);
			// @codeCoverageIgnoreEnd
        }
	}
}
