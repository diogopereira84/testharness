<?php

namespace Fedex\CatalogMvp\Api;

/**
 * @codeCoverageIgnore
 */
interface ProductPriceSyncMessageInterface
{
    /**
     * Set message
     *
     * @param string $message
     * @return void
     */
    public function setMessage($message);

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage();
}
