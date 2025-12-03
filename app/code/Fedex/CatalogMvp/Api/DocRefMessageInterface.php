<?php

namespace Fedex\CatalogMvp\Api;

/**
 * @codeCoverageIgnore
 */
interface DocRefMessageInterface
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
