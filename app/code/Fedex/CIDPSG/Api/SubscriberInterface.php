<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Api;

use Fedex\CIDPSG\Api\MessageInterface;

interface SubscriberInterface
{
    /**
     * Use to process email data
     *
     * @param MessageInterface $message
     * @return void
     */
    public function processGenericEmail(MessageInterface $message);
}
