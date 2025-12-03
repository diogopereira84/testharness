<?php

declare(strict_types=1);

namespace Fedex\InBranch\Test\Unit\Model\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\InBranch\Model\Config\E366082DocumentLevelRouting;

class E366082DocumentLevelRoutingTest extends TestCase
{
    /**
     * Toggle system configuration path
     */
    private const PATH = 'tigers_document_level_touting';

    /**
     * @var MockObject|ToggleConfig
     */
    private MockObject|ToggleConfig $toggleConfigMock;

    /**
     * @var E366082DocumentLevelRouting
     */
    private E366082DocumentLevelRouting $e366082documentLevelRouting;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->e366082documentLevelRouting = new E366082DocumentLevelRouting($this->toggleConfigMock);
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
        $this->assertTrue($this->e366082documentLevelRouting->isActive());
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
        $this->assertFalse($this->e366082documentLevelRouting->isActive());
    }
}
