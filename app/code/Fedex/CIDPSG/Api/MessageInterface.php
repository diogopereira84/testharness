<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Api;

use Fedex\CIDPSG\Api\MessageInterface;

interface MessageInterface
{
    /**
     * Use to set message
     *
     * @param string $message
     * @return void
     */
    public function setMessage($message);

    /**
     * Use to get message
     *
     * @return string
     */
    public function getMessage();
}
