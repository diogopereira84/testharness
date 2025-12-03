<?php

namespace Fedex\CatalogMvp\Model;

use Fedex\CatalogMvp\Api\CatalogMvpItemDisableMessageInterface;

/**
 * @codeCoverageIgnore
 */
class CatalogMvpItemDisableMessage implements CatalogMvpItemDisableMessageInterface
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
