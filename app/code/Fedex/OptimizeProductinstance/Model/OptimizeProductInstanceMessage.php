<?php

namespace Fedex\OptimizeProductinstance\Model;

use Fedex\OptimizeProductinstance\Api\OptimizeInstanceMessageInterface;

class OptimizeProductInstanceMessage implements OptimizeInstanceMessageInterface
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
