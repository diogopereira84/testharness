<?php

namespace Fedex\ReorderInstance\Api;

use Fedex\ReorderInstance\Api\ReorderMessageInterface;

interface ReorderMessageInterface
{
    /**
     * @param string $message
     * @return void
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getMessage();
}