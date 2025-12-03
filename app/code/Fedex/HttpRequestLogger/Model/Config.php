<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\HttpRequestLogger\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\HttpRequestLogger\Api\ConfigInterface;

class Config implements ConfigInterface
{
    /** @var string  */
    const XPATH_LOG_ENABLED = 'environment_toggle_configuration/environment_toggle/hawks_b_2313648';

    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private readonly ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @return bool
     */
    public function isLoggerEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_LOG_ENABLED);
    }
}
