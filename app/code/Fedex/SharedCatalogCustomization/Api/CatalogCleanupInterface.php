<?php

namespace Fedex\SharedCatalogCustomization\Api;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;

interface CatalogCleanupInterface
{
    /**
     * Process Message
     *
     * @param MessageInterface $message
     * @return void
     */
    public function processMessage(MessageInterface $message);
}
