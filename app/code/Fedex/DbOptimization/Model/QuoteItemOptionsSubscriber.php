<?php

namespace Fedex\DbOptimization\Model;

use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;
use Fedex\DbOptimization\Api\QuoteItemOptionsSubscriberInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class QuoteItemOptionsSubscriber implements QuoteItemOptionsSubscriberInterface
{
    /**
     * Subscriber constructor
     *
     * @param \Magento\Framework\Serialize\Serializer\Json $serializerJson
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param \Magento\Quote\Model\Quote\Item\Option $quoteItemOptionModel
     */
    public function __construct(
        protected \Magento\Framework\Serialize\Serializer\Json $serializerJson,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        protected \Magento\Quote\Model\Quote\Item\Option $quoteItemOptionModel
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function processMessage(QuoteItemOptionsMessageInterface $message)
    {
        $messages = $message->getMessage();
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' -- Data Compression Start in quote_item_options Table--');
        try {

            $messageArray = $this->serializerJson->unserialize($messages);
            foreach ($messageArray as $msg) {
                if (isset($msg['option_id']) && $msg['option_id']) {
                    $this->updateQuoteItemOptions($msg['option_id']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Data Compression Error in quote_item_options Table");
        }
    }

    /**
     * Update quote_item_option table
     *
     * @param int $optionId
     * @return void
     */
    public function updateQuoteItemOptions($optionId)
    {
        try {
            $this->quoteItemOptionModel->load($optionId)->setValue(null)->save();
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ":" . __LINE__ ." Data Compression Error -- Quote Item Options");
        }
    }
}
