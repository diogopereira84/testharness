<?php

namespace Fedex\Company\Model;

use Fedex\Company\Api\CreateCompanyEntitiesMessageInterface;

/**
 * @codeCoverageIgnore
 */
class CreateCompanyEntitiesMessage implements CreateCompanyEntitiesMessageInterface
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
