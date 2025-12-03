<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Cron;

use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use Fedex\SharedCatalogCustomization\Helper\Data;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Psr\Log\LoggerInterface;

class CatalogSyncQueue
{
    /**
     * CatalogSyncQueue constructor.
     *
     * @param CollectionFactory $sharedCatalogCollectionFactory
     * @param Data $catalogSyncQueueHelper
     * @param SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CollectionFactory $sharedCatalogCollectionFactory,
        private Data $catalogSyncQueueHelper,
        private SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Automatic scheduler sync action
     *
     * @return Bool
     */
    public function execute()
    {
        $sharedCatalog = $this->sharedCatalogCollectionFactory->create();
        foreach ($sharedCatalog as $catalog) {
            $categoryId = null;
            try {
                $id = $catalog->getId();
                $sharedCatalogConfData = $this->sharedCatalogConfRepository->getBySharedCatalogId($id);
                $categoryId = $sharedCatalogConfData->getStatus() ? $sharedCatalogConfData->getCategoryId() : null;
                $this->logger->info(__METHOD__.':'.__LINE__.':'.$id.' Catalog sync successful.');
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->logger->error(__METHOD__.':'.__LINE__.':Catalog sync error with category fetch: ' . $exception->getMessage());
            }

            if (!empty($categoryId)) {
                $this->catalogSyncQueueHelper->createSyncCatalogQueue(
                    $sharedCatalogConfData->getLegacyCatalogRootFolderId(),
                    $catalog->getCustomerGroupId(),
                    $id,
                    $categoryId
                );
            }
        }

        return true;
    }
}
