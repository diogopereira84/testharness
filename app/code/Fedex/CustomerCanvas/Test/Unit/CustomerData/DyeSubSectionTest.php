<?php

declare(strict_types=1);

namespace Fedex\CustomerCanvas\Test\Unit\CustomerData;

use Fedex\CustomerCanvas\CustomerData\DyeSubSection;
use Fedex\CustomerCanvas\ViewModel\CanvasParams;
use PHPUnit\Framework\TestCase;

class DyeSubSectionTest extends TestCase
{
    private $canvasParamsMock;

    protected function setUp(): void
    {
        $this->canvasParamsMock = $this->createMock(CanvasParams::class);
    }

    public function testGetSectionDataReturnsParamsWhenDyeSubEnabled(): void
    {
        $expected = ['foo' => 'bar'];
        $this->canvasParamsMock->method('isDyeSubEnabled')->willReturn(true);
        $this->canvasParamsMock->method('getRequiredCanvasParams')->willReturn($expected);

        $section = new DyeSubSection(
            $this->canvasParamsMock
        );

        $this->assertSame($expected, $section->getSectionData());
    }

    public function testGetSectionDataReturnsEmptyArrayWhenDyeSubDisabled(): void
    {
        $this->canvasParamsMock->method('isDyeSubEnabled')->willReturn(false);
        $this->canvasParamsMock->expects($this->never())->method('getRequiredCanvasParams');

        $section = new DyeSubSection(
            $this->canvasParamsMock
        );

        $this->assertSame([], $section->getSectionData());
    }
}

