<?php

namespace Fedex\SharedCatalogCustomization\Api;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;

interface SubscriberInterface
{
    /**
     * @return void
     */
    public function processMessage(MessageInterface $message);
}
