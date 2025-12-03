<?php
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

class AddToCartPerformanceOptimizationToggle extends ToggleBase implements ToggleInterface
{
    /**
     * Toggle system configuration path
     */
    private const SYSTEM_CONFIG_PATH = 'nfr_catelog_performance_improvement_phase_three';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::SYSTEM_CONFIG_PATH;
    }
}
