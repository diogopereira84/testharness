<?php

namespace Fedex\SharedCatalogCustomization\Api;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;

interface MessageInterface
{
    /**
     * @param string $message
     * @return void
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getMessage();
}