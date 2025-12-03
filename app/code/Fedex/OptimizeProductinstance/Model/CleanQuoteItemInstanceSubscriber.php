<?php

namespace Fedex\OptimizeProductinstance\Model;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;
use Fedex\OptimizeProductinstance\Api\OptimizeInstanceSubscriberInterface;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;

class CleanQuoteItemInstanceSubscriber implements OptimizeInstanceSubscriberInterface
{
    /**
     * CleanQuoteItemInstanceSubscriber constructor.
     *
     * @param QuoteFactory $quoteFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected QuoteFactory $quoteFactory,
        protected LoggerInterface $logger
    )
    {
    }
    
    /**
     * @inheritdoc
     */
    public function processMessage(OptimizeInstanceMessageInterface $message)
    {
        try {
            $quoteId = $message->getMessage();
            $quote = $this->quoteFactory->create()->load($quoteId);
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                $item->setIsSuperMode(true);
                $additionalOption = $item->getOptionByCode('info_buyRequest');
                if (!empty($additionalOption->getOptionId())) {
                    $additionalOption->setValue(null)->save();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Error in processing clean product Instance queue:" . var_export($e->getMessage(), true)
            );
        }
    }
}
