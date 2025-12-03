<?php

namespace Fedex\SharedCatalogCustomization\Model;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\CatalogCleanupInterface;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcessFactory;
use Fedex\SharedCatalogCustomization\Cron\CatalogSyncDeleteItemCron;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Psr\Log\LoggerInterface;

class CatalogCleanup implements CatalogCleanupInterface
{
    /**
     * CatalogCleanup constructor.
     * @param CatalogSyncQueueCleanupProcessFactory $catalogSyncQueueCleanupProcessFactory
     * @param CatalogSyncDeleteItemCron $catalogSyncDeleteItemCron
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected CatalogSyncQueueCleanupProcessFactory $catalogSyncQueueCleanupProcessFactory,
        protected CatalogSyncDeleteItemCron $catalogSyncDeleteItemCron,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Process queue message
     *
     * @param string $message
     */
    public function processMessage(MessageInterface $message)
    {
        $id = $message->getMessage();
        $catalogSyncQueueCleanupProcess = $this->catalogSyncQueueCleanupProcessFactory->create();

        try {
            $catalogSyncQueueCleanupProcessData = $catalogSyncQueueCleanupProcess->load($id);

            if ($catalogSyncQueueCleanupProcessData->getCatalogType() == 'product'
            && $catalogSyncQueueCleanupProcessData->getStatus() == ManageCatalogItems::STATUS_PROCESSING) {
                $productId = $catalogSyncQueueCleanupProcessData->getProductId();
                $this->catalogSyncDeleteItemCron->deleteItem(
                    $catalogSyncQueueCleanupProcessData->getProductId(),
                    $catalogSyncQueueCleanupProcessData->getSku()
                );
                $catalogSyncQueueCleanupProcessData->setStatus(ManageCatalogItems::STATUS_COMPLETED);
                $catalogSyncQueueCleanupProcessData->save();
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':Catalog sync cleanup queue complete.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':atalog Sync Cleanup Reading Queue Message Error:' . print_r($e->getMessage(), true));
        }
    }
}
