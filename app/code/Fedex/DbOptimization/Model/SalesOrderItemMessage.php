<?php

namespace Fedex\DbOptimization\Model;

use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;

class SalesOrderItemMessage implements SalesOrderItemMessageInterface
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     */
    public function setMessage($message)
    {
        return $this->message = $message;
    }
}
