<?php
declare(strict_types=1);

namespace Fedex\EnvironmentManager\Test\Unit\Model\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\EnvironmentManager\Model\Config\ByPassLiveSearchApiCacheToggle;

class ByPassLiveSearchApiCacheToggleTest extends TestCase
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'tech_titans_b_2398767';

    /**
     * @var MockObject|ToggleConfig
     */
    private MockObject|ToggleConfig $toggleConfigMock;

    /**
     * @var ByPassLiveSearchApiCacheToggle
     */
    private ByPassLiveSearchApiCacheToggle $byPassLiveSearchApiCacheToggle;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->byPassLiveSearchApiCacheToggle = new ByPassLiveSearchApiCacheToggle($this->toggleConfigMock);
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
        $this->assertTrue($this->byPassLiveSearchApiCacheToggle->isActive());
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
        $this->assertFalse($this->byPassLiveSearchApiCacheToggle->isActive());
    }
}
