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
use Fedex\CatalogMvp\Api\CatalogMvpItemDisableMessageInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class ItemDisableCron
{
    private const HAWKS_PUBLISHED_FLAG_INDEXING = 'hawks_published_flag_indexing';
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Json $serializerJson
     * @param CatalogMvpItemDisableMessageInterface $message
     * @param PublisherInterface $publisher
     * @param LoggerInterface $loggerInterface
     * @param ToggleConfig $toggleConfig
     * @param CollectionFactory $productCollectionFactory
     * @param CatalogMvp $catalogMvpHelper
     */
    public function __construct(
        protected Json $serializerJson,
        protected CatalogMvpItemDisableMessageInterface $message,
        protected PublisherInterface $publisher,
        LoggerInterface $loggerInterface,
        protected ToggleConfig $toggleConfig,
        protected CollectionFactory $productCollectionFactory,
        protected CatalogMvp $catalogMvpHelper
    ) {
        $this->logger = $loggerInterface;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' Cron hit for ***ItemDisable***');
        $currentTime = $this->catalogMvpHelper->getCurrentPSTDateAndTime();
        $collectionForInactive = $this->productCollectionFactory->create();
        $collectionForInactive->addAttributeToSelect('*');
        $publishedFlagToggle = $this->toggleConfig->getToggleConfigValue(self::HAWKS_PUBLISHED_FLAG_INDEXING);
        if ($publishedFlagToggle) {
            $collectionForInactive->addAttributeToFilter('published', ['eq' => 1]);
        }

        $collectionForInactive->addAttributeToFilter([
            ['attribute' => 'start_date_pod','gt' => $currentTime ],
            ['attribute' => 'end_date_pod','lt' => $currentTime ]
        ]);

        $batchSize = 100;
        $totalBatch = 100;
        $totalRecord = $batchSize * $totalBatch;
        $collectionForInactive->getSelect()->limit($totalRecord);

        $records = $collectionForInactive->getData();
        $recordCount = count($records);

        if ($recordCount) {
            for ($i = 0; $i < $recordCount; $i = $i + $totalBatch) {
                $rabbitMqStr = null;
                $rabbitMqJson = [];
                for ($j = 0; $j < $batchSize; $j++) {
                    $idx = $i + $j;
                    if (isset($records[$idx]['entity_id'])) {
                        $rabbitMqJson[] = ['entity_id' => $records[$idx]['entity_id']];
                    }
                }
                $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
                // call rabbitMq queue
                $this->message->setMessage($rabbitMqStr);
                $this->publisher->publish('catalogMvpItemDisable', $this->message);
            }
        } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .' No record found for disable products.');
        }
    }
}
