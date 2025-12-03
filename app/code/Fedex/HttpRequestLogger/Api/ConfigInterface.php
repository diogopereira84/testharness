<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\HttpRequestLogger\Api;

interface ConfigInterface
{
    /**
     * Check if the logger is enabled
     *
     * @return bool
     */
    public function isLoggerEnabled(): bool;
}
