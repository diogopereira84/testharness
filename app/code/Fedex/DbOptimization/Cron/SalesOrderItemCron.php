<?php
declare(strict_types=1);
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\DbOptimization\Cron;

use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;

class SalesOrderItemCron
{
    /**
     * Constructor
     *
     * @param SalesOrderItemMessageInterface $message
     * @param Json $serializerJson
     * @param PublisherInterface $publisher
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param Item $salesOrderItem
     */
    public function __construct(
        private SalesOrderItemMessageInterface $message,
        protected Json                         $serializerJson,
        private PublisherInterface             $publisher,
        protected ScopeConfigInterface         $scopeConfigInterface,
        protected LoggerInterface              $logger,
        protected ToggleConfig                 $toggleConfig,
        protected Item                         $salesOrderItem
    ) {
    }

    /**
     * Execute Constroller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' Cron hit for ***SalesOrderItem***');

        $prevMonth = $this->scopeConfigInterface->getValue('db_cleanup_configuration/cleaup_setting/prev_month');

        if ($prevMonth < 14) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .' Minimun cleaup months required for old entries is: 14');
            return;
        }

        $currentDate = date('Y-m-d');
        $beforeCleanupDate = date('Y-m-d', strtotime($currentDate . " -$prevMonth months"));

        $batchSize = 100;
        $totalBatch = 100;
        $totalRecord = $batchSize * $totalBatch;

        $salesItemCollection = $this->salesOrderItem->getCollection()->addFieldToSelect('item_id')
                            ->addFieldToSelect('product_options')
                            ->addFieldToSelect('created_at')
                            ->addFieldToFilter('created_at', ['lt' => $beforeCleanupDate])
                            ->addFieldToFilter('product_options', ['neq' => '[]'])
                            ->addFieldToFilter('product_options', ['neq' => null])
                            ->addFieldToFilter('product_options', ['notnull' => true]);

        $salesItemCollection = $salesItemCollection->setPageSize($totalRecord)
                            ->setCurPage(1)
                            ->load();

        $records = $salesItemCollection->getData();
        $recordCount = count($records);

        if ($recordCount) {
            for ($i = 0; $i < $recordCount; $i = $i + $totalBatch) {
                $rabbitMqStr = null;
                $rabbitMqJson = [];

                for ($j = 0; $j < $batchSize; $j++) {
                    $idx = $i + $j;
                    if (isset($records[$idx]['item_id'])) {
                        $rabbitMqJson[] = ['item_id' => $records[$idx]['item_id']];
                    }
                }

                $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);

                // call rabbitMq queue
                $this->message->setMessage($rabbitMqStr);
                $this->publisher->publish('salesOrderItemTable', $this->message);
            }
        } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .' No record found for data compression, in sales_order_item table.');
        }
    }
}
