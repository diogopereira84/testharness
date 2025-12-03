<?php

namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\DocRefMessageInterface;

/**
 * @codeCoverageIgnore
 */
class DocRefMessage implements DocRefMessageInterface
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @inheritdoc
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function setMessage($message)
    {
        return $this->message = $message;
    }
}
