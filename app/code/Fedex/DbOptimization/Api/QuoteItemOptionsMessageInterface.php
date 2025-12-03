<?php

namespace Fedex\DbOptimization\Api;

use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface QuoteItemOptionsMessageInterface
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
