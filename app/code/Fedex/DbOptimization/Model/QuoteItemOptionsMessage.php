<?php

namespace Fedex\DbOptimization\Model;

use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;

class QuoteItemOptionsMessage implements QuoteItemOptionsMessageInterface
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
