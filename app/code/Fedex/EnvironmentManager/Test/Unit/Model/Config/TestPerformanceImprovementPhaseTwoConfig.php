<?php

declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Model\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnvironmentManager\Model\Config\PerformanceImprovementPhaseTwoConfig;

class TestPerformanceImprovementPhaseTwoConfig extends TestCase
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'nfr_catalog_performance_improvement_phase_two';

    /**
     * @var MockObject|ToggleConfig
     */
    private MockObject|ToggleConfig $toggleConfigMock;

     /**
     * @var PerformanceImprovementPhaseTwoConfig
     */
    private PerformanceImprovementPhaseTwoConfig $performanceImprovementPhaseTwoConfig;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->performanceImprovementPhaseTwoConfig = new PerformanceImprovementPhaseTwoConfig($this->toggleConfigMock);
    }

    /**
     * Test isActive() method when toggle enabled
     *
     * @return void
     */
    public function testIsActiveTrue(): void
    {
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(self::PATH)
            ->willReturn(true);
        $this->assertTrue($this->performanceImprovementPhaseTwoConfig->isActive());
    }

    /**
     * Test isActive() method when toggle disabled
     *
     * @return void
     */
    public function testIsActiveFalse(): void
    {
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with(self::PATH)
            ->willReturn(false);
        $this->assertFalse($this->performanceImprovementPhaseTwoConfig->isActive());
    }
}
