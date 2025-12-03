<?php

namespace Fedex\CatalogMvp\Api;
/**
 * @codeCoverageIgnore
 */
use Fedex\SharedCatalogCustomization\Api\MessageInterface;

interface ProductPriceSyncSubscriberInterface
{
    /**
     * @return void
     */
    public function processMessage(MessageInterface $message);
}
