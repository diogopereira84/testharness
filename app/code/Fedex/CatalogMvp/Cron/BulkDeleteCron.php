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
use Fedex\CatalogMvp\Model\BulkDeleteMessage;
use Magento\Framework\Serialize\Serializer\Json;

class BulkDeleteCron
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CatalogMvpItemDisableMessageInterface
     */
    protected $message;

    /**
     * Constructor
     *
     * @param Json $serializerJson
     * @param CatalogMvpItemDisableMessageInterface $message
     * @param PublisherInterface $publisher
     * @param LoggerInterface $loggerInterface
     * @param ToggleConfig $toggleConfig
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        protected Json $serializerJson,
        BulkDeleteMessage $message,
        protected PublisherInterface $publisher,
        LoggerInterface $loggerInterface,
        protected ToggleConfig $toggleConfig,
        protected CollectionFactory $productCollectionFactory
    ) {
        $this->message = $message;
        $this->logger = $loggerInterface;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $rabbitMqJson = [];
        $productCollection = $this->productCollectionFactory->create()
            ->addFieldToFilter('pod2_0_editable', 1);
        if ($productCollection) {
            foreach ($productCollection as $product) {
                if (!$product->getCategoryIds()) {
                    $rabbitMqJson[] = [
                        'produtId' => $product->getId(),
                    ];
                }
            }
        }
        $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);
        $this->message->setMessage($rabbitMqStr);
        $this->publisher->publish('bulkDelete', $this->message);
    }
}
