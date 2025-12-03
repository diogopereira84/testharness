<?php

namespace Fedex\DbOptimization\Api;

use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;

/**
 * @codeCoverageIgnore
 */
interface SalesOrderItemMessageInterface
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
