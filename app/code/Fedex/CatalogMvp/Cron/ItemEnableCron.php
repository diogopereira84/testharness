<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Cron;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\CatalogMvp\Api\CatalogMvpItemEnableMessageInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;

class ItemEnableCron
{
    private const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';
    private const HAWKS_PUBLISHED_FLAG_INDEXING = 'hawks_published_flag_indexing';
    private const TECHTITANS_D_238087_FIX_FOR_PUBLISH_TOGGLE_START_DATE = 'TechTitans_D_238087_fix_for_Publish_Toggle_Start_Date';

    /**
     * Constructor
     *
     * @param Json $serializerJson
     * @param CatalogMvpItemEnableMessageInterface $message
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param CollectionFactory $productCollectionFactory
     * @param CatalogMvp $catalogMvpHelper
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAction $productAction
     */
    public function __construct(
        protected Json $serializerJson,
        protected CatalogMvpItemEnableMessageInterface $message,
        protected PublisherInterface $publisher,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected CollectionFactory $productCollectionFactory,
        protected CatalogMvp $catalogMvpHelper,
        readonly private ProductRepositoryInterface $productRepository,
        protected ProductAction $productAction
    ) {
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Cron hit for ***ItemEnable***');
        $currentTime = $this->catalogMvpHelper->getCurrentPSTDateAndTime();

        $collectionForActive = $this->productCollectionFactory->create();
        $collectionForActive->addAttributeToSelect('*');

        $publishedFlagToggle   = $this->toggleConfig->getToggleConfigValue(self::HAWKS_PUBLISHED_FLAG_INDEXING);
        $startDateFixToggle    = $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_238087_FIX_FOR_PUBLISH_TOGGLE_START_DATE);

        if ($startDateFixToggle) {
            $collectionForActive->addAttributeToFilter([
                ['attribute' => 'start_date_pod', 'lteq' => $currentTime],
                ['attribute' => 'start_date_pod', 'null' => true]
            ]);
        } else {
            $collectionForActive->addAttributeToFilter('start_date_pod', ['lteq' => $currentTime]);
        }

        if ($publishedFlagToggle) {
            $collectionForActive->addAttributeToFilter('published', ['eq' => 0]);
        }

        $collectionForActive->addAttributeToFilter(
            [
                ['attribute' => 'end_date_pod', 'null' => true],
                ['attribute' => 'end_date_pod', 'gteq' => $currentTime]
            ]
        );

        $nonStandardCatalogToggle = $this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG);
        if ($nonStandardCatalogToggle) {
            $collectionForActive->addAttributeToFilter('is_pending_review', ['neq' => 1]);
        }
        $batchSize = 100;
        $totalBatch = 100;
        $totalRecord = $batchSize * $totalBatch;
        $collectionForActive->getSelect()->limit($totalRecord);

        $records = $collectionForActive->getData();
        
        $recordCount = count($records);
        if ($recordCount > 0) {
            $productIds = array_column($records, 'entity_id');
            $attributesToUpdate = ['status' => 1];
            
            // Get toggle values for attribute updates
            $publishedFlagToggle = $this->toggleConfig->getToggleConfigValue(self::HAWKS_PUBLISHED_FLAG_INDEXING);
            $startDateFixToggle = $this->toggleConfig->getToggleConfigValue(self::TECHTITANS_D_238087_FIX_FOR_PUBLISH_TOGGLE_START_DATE);
            
            if ($publishedFlagToggle && $startDateFixToggle) {
                $attributesToUpdate['published'] = 1;
            }
            
            $this->productAction->updateAttributes(
                $productIds,
                $attributesToUpdate,
                0
            );
        }
    }
}
