<?php

namespace Fedex\CatalogMvp\Api;

use Fedex\CatalogMvp\Api\CatalogMvpItemEnableMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface CatalogMvpItemEnableSubscriberInterface
{
    /**
     * Message Interface for RabbitMq
     *
     * @param CatalogMvpItemEnableMessageInterface $message
     * @return void
     */
    public function processMessage(CatalogMvpItemEnableMessageInterface $message);
}
