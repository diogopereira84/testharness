<?php

declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Model\Config;

use Fedex\EnvironmentManager\Model\Config\AddToCartPerformanceOptimizationToggle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class AddToCartPerformanceOptimizationToggleTest extends TestCase
{
    protected $addToCartPerformanceOptimizationToggle;
    /**
     * Toggle system configuration path
     */
    private const PATH = 'nfr_catelog_performance_improvement_phase_three';

    /**
     * @var MockObject|ToggleConfig
     */
    private MockObject|ToggleConfig $toggleConfigMock;

    /**
     * @var AddToCartPerformanceOptimizationToggle
     */
    private AddToCartPerformanceOptimizationToggle $AddToCartPerformanceOptimizationToggle;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->addToCartPerformanceOptimizationToggle = new AddToCartPerformanceOptimizationToggle($this->toggleConfigMock);
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
        $this->assertTrue($this->addToCartPerformanceOptimizationToggle->isActive());
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
        $this->assertFalse($this->addToCartPerformanceOptimizationToggle->isActive());
    }
}
