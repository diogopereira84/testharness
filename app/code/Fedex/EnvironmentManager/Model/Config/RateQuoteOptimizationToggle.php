<?php
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

class RateQuoteOptimizationToggle extends ToggleBase implements ToggleInterface
{
    /**
     * Toggle system configuration path
     */
    private const SYSTEM_CONFIG_PATH = 'tech_titans_b_2219831';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::SYSTEM_CONFIG_PATH;
    }
}
