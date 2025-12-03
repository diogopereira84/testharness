<?php
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Model\Config;

class PerformanceImprovementPhaseTwoConfig extends ToggleBase implements ToggleInterface
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'nfr_catalog_performance_improvement_phase_two';

    /**
     * @inheritDoc
     */
    protected function getPath(): string
    {
        return self::PATH;
    }
}
