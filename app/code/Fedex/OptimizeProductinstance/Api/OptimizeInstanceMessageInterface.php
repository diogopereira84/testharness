<?php

namespace Fedex\OptimizeProductinstance\Api;

use Fedex\OptimizeProductinstance\Api;

interface OptimizeInstanceMessageInterface
{
    /**
     * Set Message
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
