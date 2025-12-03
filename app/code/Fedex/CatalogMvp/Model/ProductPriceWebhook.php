<?php

/**
 * Copyright © fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Model;

use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory as sharedCollectionFactory;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\CollectionFactory as sharedCollectionItemFactory;

class ProductPriceWebhook implements WebhookInterface
{
    /**
     * @var LoggerInterface $message
     */
    private $logger;

    /**
     * ProductPriceWebhook Construct
     * @param Json $context
     * @param MessageInterface $catalogSynchQueueFactory
     * @param PublisherInterface $messageManager
     * @param LoggerInterface $logger
     * @param sharedCollectionFactory $sharedCatalogCollectionFactory
     * @param sharedCollectionItemFactory $sharedCatalogProductItem
     * @return void
     */

    public function __construct(
        private Json $serializerJson,
        private MessageInterface $message,
        private PublisherInterface $publisher,
        LoggerInterface $loggerInterface,
        private sharedCollectionFactory $sharedCatalogCollectionFactory,
        private sharedCollectionItemFactory $sharedCatalogProductItem
    ) {
        $this->logger                          = $loggerInterface;
    }

    /**
     * addProductToRM
     * return boolean
     */

    public function addProductToRM($requestData)
    {
        $batchSize = 50;
        $totalBatch = 100;

        try {
            if (is_array($requestData) && array_key_exists('shared_catalog_id', $requestData) && !empty($requestData['shared_catalog_id'])) {
                $sharedCatalogCollection = $this->getCustomerGroupIdByShareCatalogId($requestData['shared_catalog_id']);
                $customerGroupId = current($sharedCatalogCollection->getData());
                $customerGrpId = $customerGroupId['customer_group_id'];
                $productCollection = $this->sharedCatalogProductItem->create();
                $productCollection->addFieldToFilter('customer_group_id', ['eq' => $customerGrpId]);
         
                $records = $productCollection->getData();
                $totalProductCount = count($records);
                if ($totalProductCount > 0) {
                    $records = array_reverse($records);
                    for ($indexer = 0; $indexer < $totalProductCount; $indexer = $indexer + $totalBatch) {
                        $rabbitMqStr = null;
                        $rabbitMqJson = [];
                        for ($counter = 0; $counter < $batchSize; $counter++) {
                            $indexKey = $indexer + $counter;
                            if (isset($records[$indexKey]['sku'])) {
                                $rabbitMqJson[] = [
                                    'customer_group_id' => $customerGrpId,
                                    'shared_catalog_id' => $requestData['shared_catalog_id'],
                                    'sku' => $records[$indexKey]['sku']
                                ];
                            }
                        }

                        $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
                        $this->message->setMessage($rabbitMqStr);
                        $this->publisher->publish('enableProductPriceSync', $this->message);
                    }
                }
            } else {
                $rmMessage = $this->message->getMessage();
                if (empty($rmMessage)) {
                    $rabbitMqStr = $this->serializerJson->serialize($requestData);
                    $this->message->setMessage($rabbitMqStr);
                    $this->publisher->publish('enableProductPriceSync', $this->message);
                } else {
                    $messageArray = $this->serializerJson->unserialize($rmMessage);
                    $key = array_search($requestData['sku'], $messageArray, true);
                    if ($key !== false) {
                        unset($messageArray[$key]);
                    }

                    $rabbitMqStr = $this->serializerJson->serialize($requestData);
                    $this->message->setMessage($rabbitMqStr);
                    $this->publisher->publish('enableProductPriceSync', $this->message);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ . " error price sync webhook");
        }

        return true;
    }

    /**
     * getCustomerGroupIdByShareCatalogId
     * return object
     */
    public function getCustomerGroupIdByShareCatalogId($sharedCatalogId)
    {
        $collection = $this->sharedCatalogCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['eq' => $sharedCatalogId]);
        return $collection;
    }
}
