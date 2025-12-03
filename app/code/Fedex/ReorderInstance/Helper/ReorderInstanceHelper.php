<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\ReorderInstance\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\ReorderInstance\Api\ReorderMessageInterface;
use Psr\Log\LoggerInterface;

class ReorderInstanceHelper
{
    /**
     * @var ReorderManagerInterface $message
     */
    protected $message;

    /**
     * ReorderInstance Helper
     * @param PublisherInterface $publisher
     * @param ReorderMessageInterface $message
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected PublisherInterface $publisher,
        ReorderMessageInterface $message,
        protected LoggerInterface $logger
    ) {
        $this->message = $message;
    }

    /**
     * Push order Id in Queue
     *
     * @param Int $orderId
     *
     * @return boolean
     */
    public function pushOrderIdInQueue($orderId)
    {
        try {
            // Dynamically calling orderID
            $this->message->setMessage($orderId);
            $this->publisher->publish('reorderInstance', $this->message);

            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error in publishing the: ' . $e->getMessage());
        }
    }

    /**
     * Push order id in queue for retrying shipment creation
     *
     * @param string $messageRequest
     *
     * @return boolean
     */
    public function pushOrderIdInQueueForShipmentCreation($messageRequest)
    {
        try {
            // Dynamically calling orderID
            $this->message->setMessage($messageRequest);
            $this->publisher->publish('retryShipmentCreation', $messageRequest);

            return true;
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Error in publishing the message: ' . $messageRequest .'is: ' . $e->getMessage());

            return false;
        }
    }
}
