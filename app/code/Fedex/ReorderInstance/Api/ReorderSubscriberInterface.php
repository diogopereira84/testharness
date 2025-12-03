<?php

namespace Fedex\ReorderInstance\Api;

use Fedex\ReorderInstance\Api\ReorderMessageInterface;

interface ReorderSubscriberInterface
{
    /**
     * @return void
     */
    public function processMessage(ReorderMessageInterface $message);
}
