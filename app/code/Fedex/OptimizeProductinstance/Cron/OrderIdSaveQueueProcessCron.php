<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OptimizeProductinstance\Cron;

use Magento\Sales\Model\OrderFactory;
use Fedex\OptimizeProductinstance\Model\OrderCompressionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Fedex\OptimizeProductinstance\Model\ResourceModel\OrderCompression\CollectionFactory as OrderCompressionCollectionFactory;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ResourceConnection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class OrderIdSaveQueueProcessCron
{
    public const EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS = "explorers_optimize_quotes_and_orders_within_14_months";

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * OrderIdSaveQueueProcessCron constructor.
     *
     * @param OrderFactory $orderFactory
     * @param OrderCompressionFactory $orderCompressionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderCompressionCollectionFactory $orderCompressionCollectionFactory
     * @param OptimizeItemInstanceHelper $optimizeItemInstanceHelper
     * @param ResourceConnection $resourceConnection
     * @param ToggleConfig $toggleConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected OrderFactory $orderFactory,
        protected OrderCompressionFactory $orderCompressionFactory,
        protected OrderCollectionFactory $orderCollectionFactory,
        protected OrderCompressionCollectionFactory $orderCompressionCollectionFactory,
        protected OptimizeItemInstanceHelper $optimizeItemInstanceHelper,
        private ResourceConnection $resourceConnection,
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger
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

        if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS)) {

            $orderCollection = $this->orderFactory->create()->getCollection();
            $orderCompressionCollection = $this->orderCompressionFactory->create()->getCollection();
            $tempOrderCompressionTable = $this->resourceConnection->getTableName('temp_order_compression');
            $getCurrentDate = new \DateTime();

            $toDate = $getCurrentDate->format('Y-m-d H:i:s');
            $getFourteenthMonthToDate = $getCurrentDate->modify('-14 months');
            $fromDate = $getFourteenthMonthToDate->format('Y-m-d H:i:s');

            $getAllAddedOrderIds = $orderCompressionCollection->addFieldToSelect('order_id')->getSelect()->__toString();

            $orderCollection->addFieldToSelect('entity_id')
                ->addFieldToFilter(
                    'created_at',
                    [
                        'from' => $fromDate, 'to' => $toDate
                    ]
                )
                ->getSelect()
                ->joinLeft(
                    ['temp_order_compression' => $tempOrderCompressionTable],
                    'main_table.entity_id = temp_order_compression.order_id'
                )
                ->where("main_table.entity_id NOT IN(".$getAllAddedOrderIds.")")
                ->limit(100);
            $this->addOrderDataInTempTable($orderCollection);
        }

        return true;
    }

    /**
     * Add order Ids into Temp Table
     *
     * @param  object $orderCollection
     * @return void
     * @throws Exception
     */
    public function addOrderDataInTempTable($orderCollection)
    {
        try {
            foreach ($orderCollection as $orderData) {
                $orderCompressionFactory = $this->orderCompressionFactory->create();
                $orderId = (int) $orderData->getId();
                $orderCompressionFactory->setOrderId($orderId);
                $tempOrderId = $orderCompressionFactory->save()->getId();
                $this->optimizeItemInstanceHelper->pushTempOrderCompressionIdQueue($tempOrderId);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Error in inserting data in temp table for order:" . var_export($e->getMessage(), true)
            );
        }
    }
}
