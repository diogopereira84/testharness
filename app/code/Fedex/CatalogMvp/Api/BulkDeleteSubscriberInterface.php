<?php

namespace Fedex\CatalogMvp\Api;

use Fedex\CatalogMvp\Api\BulkDeleteMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface BulkDeleteSubscriberInterface
{
    /**
     * Process message for RabbitMq
     *
     * @param BulkDeleteMessageInterface $message
     * @return void
     */
    public function processMessageBulkDelete(BulkDeleteMessageInterface $message);    
}