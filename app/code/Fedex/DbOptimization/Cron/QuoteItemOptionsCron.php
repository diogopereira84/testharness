<?php
declare(strict_types=1);
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\DbOptimization\Cron;

use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class QuoteItemOptionsCron
{
    /**
     * Constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param Json $serializerJson
     * @param QuoteItemOptionsMessageInterface $message
     * @param PublisherInterface $publisher
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param Quote $quoteModel
     */
    public function __construct(
        protected ResourceConnection             $resourceConnection,
        protected Json                           $serializerJson,
        private QuoteItemOptionsMessageInterface $message,
        private PublisherInterface               $publisher,
        protected ScopeConfigInterface           $scopeConfigInterface,
        protected LoggerInterface                $logger,
        protected ToggleConfig                   $toggleConfig,
        protected Quote                          $quoteModel
    ) {
    }

    /**
     * Execute Constroller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' Cron hit for ***QuoteItemOptions***');

        $quoteItemTable = $this->resourceConnection->getTableName('quote_item');
        $quoteItemOptionsTable = $this->resourceConnection->getTableName('quote_item_option');

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

        $quoteCollections = $this->quoteModel->getCollection()->addFieldToSelect('entity_id')
                                    ->addFieldToSelect('updated_at')
                                        ->addFieldToFilter('main_table.updated_at', ['lt' => $beforeCleanupDate]);

        $quoteCollections->getSelect()->join(
            ['item_table' => $quoteItemTable],
            'main_table.entity_id = item_table.quote_id',
            []
        )->columns('item_table.item_id')->join(
            ['qi_option' => $quoteItemOptionsTable],
            'item_table.item_id = qi_option.item_id',
            []
        )->columns(['qi_option.option_id', 'qi_option.value']);

        $quoteCollections->addFieldToFilter('qi_option.value', ['neq' => 'NULL']);
        $quoteCollections->getSelect()->limit($totalRecord);

        $records = $quoteCollections->getData();
        $recordCount = count($records);

        if ($recordCount) {
            for ($i = 0; $i < $recordCount; $i = $i + $totalBatch) {
                $rabbitMqStr = null;
                $rabbitMqJson = [];

                for ($j = 0; $j < $batchSize; $j++) {
                    $idx = $i + $j;
                    if (isset($records[$idx]['option_id'])) {
                        $rabbitMqJson[] = ['option_id' => $records[$idx]['option_id']];
                    }
                }

                $rabbitMqStr = $this->serializerJson->serialize($rabbitMqJson);

                // call rabbitMq queue
                $this->message->setMessage($rabbitMqStr);
                $this->publisher->publish('quoteItemOptionsTable', $this->message);
            }
        } else {
            $this->logger->info(__METHOD__ . ':' . __LINE__ .' No record found for data compression, in quote_item_options table.');
        }
    }
}
