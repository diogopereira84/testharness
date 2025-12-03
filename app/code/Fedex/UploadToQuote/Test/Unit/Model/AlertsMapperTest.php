<?php
/**
 * Unit test for AlertsMapper
 *
 * @category     Fedex
 * @package      Fedex_UploadToQuote
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Model\AlertsMapper;
use Fedex\UploadToQuote\Model\AlertsDtoFactory;
use Fedex\UploadToQuote\Model\AlertsDto;

/**
 * Class AlertsMapperTest
 *
 * Unit tests for AlertsMapper model
 */
class AlertsMapperTest extends TestCase
{
    /** @var AlertsDtoFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $alertsDtoFactoryMock;

    /** @var AlertsMapper */
    private $alertsMapper;

    protected function setUp(): void
    {
        $this->alertsDtoFactoryMock = $this->createMock(AlertsDtoFactory::class);
        $this->alertsMapper = new AlertsMapper($this->alertsDtoFactoryMock);
    }

    /**
     * Test map() returns empty array when input is empty
     */
    public function testMapReturnsEmptyArrayOnEmptyInput(): void
    {
        $result = $this->alertsMapper->map([]);
        $this->assertSame([], $result);
    }

    /**
     * Test map() returns correct alerts array
     */
    public function testMapReturnsAlertsArray(): void
    {
        $alertData = [
            'code' => 'CODE1',
            'message' => 'Test message',
            'alertType' => 'WARNING'
        ];
        $raqResponse = [$alertData];

        $alertsDtoMock = $this->createMock(AlertsDto::class);
        $alertsDtoMock->expects($this->once())
            ->method('setCode')
            ->with($alertData['code']);
        $alertsDtoMock->expects($this->once())
            ->method('setMessage')
            ->with($alertData['message']);
        $alertsDtoMock->expects($this->once())
            ->method('setAlertType')
            ->with($alertData['alertType']);
        $alertsDtoMock->expects($this->once())
            ->method('toArray')
            ->willReturn($alertData);

        $this->alertsDtoFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($alertsDtoMock);

        $result = $this->alertsMapper->map($raqResponse);
        $this->assertSame([$alertData], $result);
    }

    /**
     * Test map() handles missing keys gracefully
     */
    public function testMapHandlesMissingKeys(): void
    {
        $alertData = [];
        $raqResponse = [$alertData];
        $expected = [
            'code' => '',
            'message' => '',
            'alertType' => ''
        ];

        $alertsDtoMock = $this->createMock(AlertsDto::class);
        $alertsDtoMock->expects($this->once())
            ->method('setCode')
            ->with('');
        $alertsDtoMock->expects($this->once())
            ->method('setMessage')
            ->with('');
        $alertsDtoMock->expects($this->once())
            ->method('setAlertType')
            ->with('');
        $alertsDtoMock->expects($this->once())
            ->method('toArray')
            ->willReturn($expected);

        $this->alertsDtoFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($alertsDtoMock);

        $result = $this->alertsMapper->map($raqResponse);
        $this->assertSame([$expected], $result);
    }
}

