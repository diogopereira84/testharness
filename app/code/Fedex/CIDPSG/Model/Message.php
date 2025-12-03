<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model;

use Fedex\CIDPSG\Api\MessageInterface;

class Message implements MessageInterface
{
    /**
     * @var string
     */
    protected $message;

    /**
     * Use to get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Use to set message
     *
     * @param string $message
     * @return void
     */
    public function setMessage($message)
    {
        return $this->message = $message;
    }
}
