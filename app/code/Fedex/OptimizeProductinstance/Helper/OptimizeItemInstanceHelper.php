<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\OptimizeProductinstance\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Psr\Log\LoggerInterface;

class OptimizeItemInstanceHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * ReorderInstance Helper
     *
     * @param Context $context
     * @param PublisherInterface $publisher
     * @param OptimizeInstanceMessageInterface $message
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        protected PublisherInterface $publisher,
        protected OptimizeInstanceMessageInterface $message,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->_logger = $logger;
    }

    /**
     * Push quote Id in Queue
     *
     * @param Int $quoteId
     *
     * @return boolean
     */
    public function pushQuoteIdQueue($quoteId)
    {
        try {
            $this->message->setMessage($quoteId);
            $this->publisher->publish('cleanQuoteItemInstance', $this->message);

            return true;
        } catch (\Exception $e) {
            $this->_logger->error(
                "Error in publishing the quote id in cleanQuoteItemInstance Queue :" . var_export($e->getMessage(), true)
            );
        }
    }

    /**
     * Push temp table data id in queue for quote
     *
     * @param Int $id
     *
     * @return boolean
     */
    public function pushTempQuoteCompressionIdQueue($id)
    {
        try {
            $this->message->setMessage($id);
            $this->publisher->publish('cleanUpdateQuoteItemInstance', $this->message);

            return true;
        } catch (\Exception $e) {
            $this->_logger->error(
                "Error in publishing the temp quote compresssion id in cleanUpdateQuoteItemInstance Queue :" . var_export($e->getMessage(), true)
            );
        }
    }

    /**
     * Push temp table data id in queue for order
     *
     * @param Int $id
     *
     * @return boolean
     */
    public function pushTempOrderCompressionIdQueue($id)
    {
        try {
            $this->message->setMessage($id);
            $this->publisher->publish('updateOrderItemInstance', $this->message);

            return true;
        } catch (\Exception $e) {
            $this->_logger->error(
                "Error in publishing the temp order compresssion id in updateOrderItemInstance Queue :" . var_export($e->getMessage(), true)
            );
        }
    }
}
