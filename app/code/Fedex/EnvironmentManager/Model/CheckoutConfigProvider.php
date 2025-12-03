<?php
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CheckoutConfigProvider
{
    public const TIGER_D238132 = 'tiger_d238132';

    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(protected ToggleConfig $toggleConfig)
    {}

    /**
     * Shipping configuration for checkout page
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'tiger_d238132' => (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D238132)
        ];
    }
}
