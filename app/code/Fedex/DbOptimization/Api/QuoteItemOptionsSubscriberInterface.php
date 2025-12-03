<?php

namespace Fedex\DbOptimization\Api;

use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface QuoteItemOptionsSubscriberInterface
{
    /**
     * Message Interface for RabbitMq
     *
     * @param \Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface $message
     * @return void
     */
    public function processMessage(QuoteItemOptionsMessageInterface $message);
}
