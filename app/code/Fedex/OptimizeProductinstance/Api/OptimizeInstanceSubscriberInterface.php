<?php

namespace Fedex\OptimizeProductinstance\Api;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;

interface OptimizeInstanceSubscriberInterface
{
    /**
     * Proocess the message
     *
     * @param OptimizeInstanceMessageInterface $message
     * @return void
     */
    public function processMessage(OptimizeInstanceMessageInterface $message);
}
