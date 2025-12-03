<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Api;

use Fedex\Shipment\Api\MessageInterface;

/**
 * @codeCoverageIgnore
 */
interface SubscriberInterface
{
    /**
     * @return void
     */
    public function processMessage(MessageInterface $message);
}
