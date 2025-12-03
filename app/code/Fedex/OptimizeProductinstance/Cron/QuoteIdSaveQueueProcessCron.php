<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OptimizeProductinstance\Cron;

use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Quote\Model\QuoteFactory;
use Fedex\OptimizeProductinstance\Model\QuoteCompressionFactory;
use Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression\CollectionFactory as QuoteCompressionCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class QuoteIdSaveQueueProcessCron
{
    public const EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS = "explorers_optimize_quotes_and_orders_within_14_months";

    /**
     * @var uoteCompressionCollectionFactory $quoteCompressionCollectionFactory
     */
    protected $quoteCompressionCollectionFactory;

    /**
     * CatalogSyncQueue constructor.
     *
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param QuoteCompressionCollectionFactory $quoteCompressionCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param QuoteFactory $quoteFactory
     * @param QuoteCompressionFactory $quoteCompressionFactory
     * @param OptimizeItemInstanceHelper $optimizeItemInstanceHelper
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected QuoteCollectionFactory $quoteCollectionFactory,
        QuoteCompressionCollectionFactory $quoteCompressionCollectionFactory,
        protected ResourceConnection $resourceConnection,
        protected QuoteFactory $quoteFactory,
        private QuoteCompressionFactory $quoteCompressionFactory,
        protected OptimizeItemInstanceHelper $optimizeItemInstanceHelper,
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger
    ) {
        $this->quoteCompressionCollectionFactory = $quoteCompressionCollectionFactory;
    }

    /**
     * Execute method to fetch and add quote data in temp compression table
     *
     * @return bool true
     */
    public function execute()
    {
        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS)) {
            $quoteCollection = $this->quoteFactory->create()->getCollection();
            $quoteCompressionCollection = $this->quoteCompressionFactory->create()->getCollection();
            $tempQuoteCompressionTable = $this->resourceConnection->getTableName('temp_quote_compression');
            $getCurrentDate = new \DateTime();
            $toDate = $getCurrentDate->format('Y-m-d H:i:s');
            $getFourteenthMonthToDate = $getCurrentDate->modify('-14 months');
            $fromDate = $getFourteenthMonthToDate->format('Y-m-d H:i:s');

            // Get all added quote ids from temp compression query
            $getAllAddedQuoteIds = $quoteCompressionCollection
            ->addFieldToSelect('quote_id')
            ->getSelect()
            ->__toString();

            // Join two table quote and temp_quote_compression and get within 14 months items
            $quoteCollection->addFieldToSelect('entity_id')
            ->addFieldToFilter(
                'created_at',
                [
                    'from' => $fromDate, 'to' => $toDate
                ]
            )
            ->getSelect()
            ->joinLeft(
                ['temp_quote_compression' => $tempQuoteCompressionTable],
                'main_table.entity_id = temp_quote_compression.quote_id',
                ['main_table.is_active']
            )
            ->where("main_table.entity_id NOT IN(".$getAllAddedQuoteIds.")")
            ->limit(100);

            // calling method to add data in custom temp table
            $this->addQuoteDataInTempTable($quoteCollection);
        }
        return true;
    }

    /**
     * Quote ids data save in temp table and put in queue
     *
     * @param  object $quoteCollection
     * @return void
     * @throws Exception
     */
    public function addQuoteDataInTempTable($quoteCollection)
    {
        try {
            foreach ($quoteCollection as $quoteData) {
                $quoteId = (int) $quoteData->getId();
                $quoteCompressionFactory = $this->quoteCompressionFactory->create();
                $quoteCompressionFactory->setQuoteId($quoteId);
                $quoteCompressionFactory->setStatus(0);
                $tempQuoteId = $quoteCompressionFactory->save()->getId();
                // Push data into queque
                $this->optimizeItemInstanceHelper->pushTempQuoteCompressionIdQueue($tempQuoteId);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in inserting data in temp table for quote id:".$quoteId .'is:' . var_export($e->getMessage(), true));
        }
    }
}
