<?php

namespace Fedex\CatalogMvp\Api;

/**
 * @codeCoverageIgnore
 */
interface CatalogMvpItemDisableMessageInterface
{
    /**
     * Set message
     *
     * @param string $message
     * @return void
     */
    public function setMessage($message);

    /**
     * GetMessage
     *
     * @return string
     */
    public function getMessage();
}
