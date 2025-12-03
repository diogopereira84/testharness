<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SaaSCommon\Api\ConfigInterface;

class Config implements ConfigInterface
{
    const TIGER_D200529 = 'tiger_d200529';

    public function __construct(
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isTigerD200529Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D200529);
    }
}
