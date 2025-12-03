<?php

namespace Fedex\DbOptimization\Model;

use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;
use Fedex\DbOptimization\Api\SalesOrderItemSubscriberInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;

class SalesOrderItemSubscriber implements SalesOrderItemSubscriberInterface
{
    /**
     * Subscriber constructor.
     *
     * @param \Magento\Framework\Serialize\Serializer\Json $serializerJson
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param Item $salesOrderItem
     */
    public function __construct(
        private \Magento\Framework\Serialize\Serializer\Json $serializerJson,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected Item $salesOrderItem
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function processMessage(SalesOrderItemMessageInterface $message)
    {
        $messages = $message->getMessage();
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' -- Data Compression Start in sales_order_item Table--');
        try {

            $messageArray = $this->serializerJson->unserialize($messages);
            foreach ($messageArray as $msg) {
                if (isset($msg['item_id']) && $msg['item_id']) {
                    $this->updateSalesOrderItem($msg['item_id']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Data Compression Error in sales_order_item Table");
        }
    }

    /**
     * Update sales_order_item table
     *
     * @param int $itemId
     * @return void
     */
    public function updateSalesOrderItem($itemId)
    {
        try {
            $this->salesOrderItem->load($itemId)->setData('product_options', null)->save();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Data Compression Error -- Sales Order Item");
        }
    }
}
