<?php

namespace Fedex\SharedCatalogCustomization\Model;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueProcessFactory;
use Psr\Log\LoggerInterface;

class Subscriber implements SubscriberInterface
{
    /**
      * Subscriber constructor.
      * @param CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory
      * @param \Fedex\SharedCatalogCustomization\Helper\Data $helper
      * @param LoggerInterface $logger
      */
    public function __construct(
        protected CatalogSyncQueueProcessFactory $catalogSyncQueueProcessFactory,
        protected \Fedex\SharedCatalogCustomization\Helper\Data $helper,
        protected LoggerInterface $logger
    )
    {
    }
    
    /**
     * @inheritdoc
     */
    public function processMessage(MessageInterface $message)
    {
        $catalogSyncQueueProcessId = $message->getMessage();
        $catalogSyncQueueProcess = $this->catalogSyncQueueProcessFactory->create();

        try {
            $catalogSyncQueueProcessData = $catalogSyncQueueProcess->load($catalogSyncQueueProcessId);
    
            // Mark status - processing of queue item under catalog_sync_queue_process table
            $this->helper
                ->manageCatalogItems
                ->setQueueStatus($catalogSyncQueueProcessId, $this->helper::STATUS_PROCESSING);
            if ($catalogSyncQueueProcessData->getCatalogType() == 'root_category'
            || $catalogSyncQueueProcessData->getCatalogType() == 'category') {
                
                $legacyCatalogRootFolderId = $catalogSyncQueueProcessData->getJsonData();
                $rootParentCateId = $catalogSyncQueueProcessData->getCategoryId();

                // Call API and create queues and publish queues into rabbitMQ
                $responseDatas = $this->helper->catalogSyncApiRequest($legacyCatalogRootFolderId);

                if (!array_key_exists('errors', $responseDatas)) {
                    $this->helper->processCategories(
                        $responseDatas,
                        $catalogSyncQueueProcessData->getCatalogSyncQueueId(),
                        $rootParentCateId,
                        $catalogSyncQueueProcessData->getSharedCatalogId(),
                        $catalogSyncQueueProcessData->getCatalogType(),
                        $catalogSyncQueueProcessData->getStoreId(),
                        $catalogSyncQueueProcessId
                    );
                } else {
                    $errorMsg = json_encode($responseDatas);
                    // Mark queue status Failed
                    $this->helper
                        ->manageCatalogItems
                        ->setQueueStatus($catalogSyncQueueProcessId, $this->helper::STATUS_FAILED, $errorMsg);
                }
                $this->helper
                    ->manageCatalogItems
                    ->setQueueStatus($catalogSyncQueueProcessId, $this->helper::STATUS_COMPLETED);
            } else {
                // Add/update/delete products queues executions.
                $productJson = $catalogSyncQueueProcessData->getJsonData();
                $categoryId = $catalogSyncQueueProcessData->getCategoryId();

                if ($catalogSyncQueueProcessData->getActionType() == 'new') {
                    $this->helper->manageCatalogItems->createItem(
                        $productJson,
                        $catalogSyncQueueProcessData->getSharedCatalogId(),
                        $catalogSyncQueueProcessData->getCategoryId(),
                        $catalogSyncQueueProcessId,
                        $catalogSyncQueueProcessData->getStoreId()
                    );
                } elseif ($catalogSyncQueueProcessData->getActionType() == 'update') {
                    $this->helper->manageCatalogItems->updateItem(
                        $productJson,
                        $catalogSyncQueueProcessId,
                        $catalogSyncQueueProcessData->getStoreId()
                    );
                }
            }
            $this->logger->info(__METHOD__.':'.__LINE__.':Catalog sync queue completed.');
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Catalog Sync Reading Queue Message Error:' . print_r($e->getMessage(), true));
        }
    }
}
