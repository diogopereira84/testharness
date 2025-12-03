<?php

namespace Fedex\SharedCatalogCustomization\Model;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Psr\Log\LoggerInterface;

class CatalogEnableStoreSubscriber implements SubscriberInterface
{
    /**
      * Subscriber constructor.
      * @param ManageCatalogItems $helperManageCatalogItems
      * @param LoggerInterface $logger
      */
    public function __construct(
        protected ManageCatalogItems $helperManageCatalogItems,
        protected LoggerInterface $logger
    )
    {
    }
    
    /**
     * @inheritdoc
     */
    public function processMessage(MessageInterface $message)
    {
        $message = $message->getMessage();
        if ($message) {
            $message = json_decode($message);
            $catalogSyncQueueProcessId = $message->catalogSyncQueueProcessId;
            $productSku = $message->productSku;
            $storeId = $message->storeId;
            $this->logger->info(__METHOD__.':'.__LINE__.': Process message to update item status for store');
            $this->helperManageCatalogItems->itemEnableStore($catalogSyncQueueProcessId, $productSku, $storeId);    
        }
    }
}
