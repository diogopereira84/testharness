<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueueCleanupProcess\CollectionFactory;
use Fedex\SharedCatalogCustomization\Model\CatalogSyncQueueCleanupProcessFactory;
use Fedex\SharedCatalogCustomization\Helper\ManageCatalogItems;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Psr\Log\LoggerInterface;

class CatalogSyncQueueCleanupProcessCron
{
    /**
     * CatalogSyncQueueCleanupProcessCron constructor.
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param CollectionFactory $catalogSyncCleanupCollectionFactory
     * @param CatalogSyncQueueCleanupProcessFactory $catalogSyncQueueCleanupProcessFactory
     * @param ManageCatalogItems $manageCatalogItemsHelper
     * @param PublisherInterface $publisher
     * @param MessageInterface $message
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ProductRepositoryInterface $productRepositoryInterface,
        protected CollectionFactory $catalogSyncCleanupCollectionFactory,
        protected CatalogSyncQueueCleanupProcessFactory $catalogSyncQueueCleanupProcessFactory,
        protected ManageCatalogItems $manageCatalogItemsHelper,
        protected PublisherInterface $publisher,
        protected MessageInterface $message,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Check pending sync request and put in queue one by one
     */
    public function execute()
    {
        $catalogSyncQueueCleanupCollection = $this->catalogSyncCleanupCollectionFactory->create();
        $catalogSyncQueueCleanupCollection->addFieldToFilter('status', ['eq' => ManageCatalogItems::STATUS_PENDING]);

        if (!empty($catalogSyncQueueCleanupCollection->getSize())) {
            foreach ($catalogSyncQueueCleanupCollection as $catalogSyncQueueCleanup) {
                $rowId = $catalogSyncQueueCleanup->getId();
                $productId = $catalogSyncQueueCleanup->getProductId();
                if ($this->canProductDelete($productId, $rowId)) {
                    // Publish into message Queue.
                    $this->message->setMessage($rowId);
                    $this->publisher->publish('catalogCleanup', $this->message);
                }
            }
        }
    }

    /**
     * Check if product available in negotiable quote and any category
     *
     * @param int $productId
     * @param int $rowId
     *
     * @return boolean
     */
    public function canProductDelete($productId, $rowId)
    {
        $catalogSyncCleanup = $this->catalogSyncQueueCleanupProcessFactory->create()->load($rowId);

        try {
            $product = $this->productRepositoryInterface->getById($productId);
            $isQuoteAvailable = $this->manageCatalogItemsHelper->checkNegotiableQuote($productId);
            if (empty($product->getCategoryIds()) && !$isQuoteAvailable) {
                $catalogSyncCleanup->setStatus(ManageCatalogItems::STATUS_PROCESSING);
                $catalogSyncCleanup->save();
                return true;
            } else {
                $catalogSyncCleanup->setErrorMsg(
                    'Product may be assigned into another category or negotiable quote is in active status.'
                );
                $catalogSyncCleanup->setStatus(ManageCatalogItems::STATUS_FAILED);
                $catalogSyncCleanup->save();
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . ManageCatalogItems::STATUS_FAILED . ' ' . $e->getMessage());
            $catalogSyncCleanup->setErrorMsg(json_encode($e->getMessage()));
            $catalogSyncCleanup->setStatus(ManageCatalogItems::STATUS_FAILED);
            $catalogSyncCleanup->save();
        }

        return false;
    }
}
