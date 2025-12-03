<?php

namespace Fedex\DbOptimization\Api;

use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface SalesOrderItemSubscriberInterface
{
    /**
     * Process message for RabbitMq
     *
     * @param \Fedex\DbOptimization\Api\SalesOrderItemMessageInterface $message
     * @return void
     */
    public function processMessage(SalesOrderItemMessageInterface $message);
}
