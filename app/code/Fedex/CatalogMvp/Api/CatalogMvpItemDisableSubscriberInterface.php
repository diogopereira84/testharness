<?php

namespace Fedex\CatalogMvp\Api;

use Fedex\CatalogMvp\Api\CatalogMvpItemDisableMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface CatalogMvpItemDisableSubscriberInterface
{
    /**
     * Process message for RabbitMq
     *
     * @param CatalogMvpItemDisableMessageInterface $message
     * @return void
     */
    public function processMessage(CatalogMvpItemDisableMessageInterface $message);
}
