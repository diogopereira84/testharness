<?php

namespace Fedex\CatalogMvp\Api;

/**
 * @codeCoverageIgnore
 */
interface CatalogMvpItemEnableMessageInterface
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
