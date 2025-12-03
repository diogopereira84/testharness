<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\TrackOrder\Api;

interface CurlRequestInterface
{
    /**
     * Send a curl request.
     *
     * @param int $orderId
     * @return array
     */
    public function sendRequest(int $orderId): array;
}
