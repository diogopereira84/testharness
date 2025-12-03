<?php
/**
 * @category    Fedex
 * @package     Fedex_EnvironmentManager
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Adithya Adithya <adithya.adithya@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Model\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnvironmentManager\Model\Config\TrackOrderPod;

class TrackOrderPodTest extends TestCase
{
    /**
     * @var MockObject|ToggleConfig
     */
    private MockObject|ToggleConfig $toggleConfigMock;

     /**
     * @var TrackOrderPod
     */
    private TrackOrderPod $trackOrderPod;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->trackOrderPod = new TrackOrderPod($this->toggleConfigMock);
    }

    /**
     * Test isActive() method
     *
     * @return void
     */
    public function testIsActive(): void
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);
        $this->assertTrue($this->trackOrderPod->isActive());
    }
}