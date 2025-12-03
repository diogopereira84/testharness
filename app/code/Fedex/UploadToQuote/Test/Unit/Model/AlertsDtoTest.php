<?php
/**
 * Unit test for AlertsDto
 *
 * @category     Fedex
 * @package      Fedex_UploadToQuote
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\UploadToQuote\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Model\AlertsDto;

/**
 * Class AlertsDtoTest
 *
 * Unit tests for AlertsDto model
 */
class AlertsDtoTest extends TestCase
{
    /**
     * Test constructor and getters
     */
    public function testConstructorAndGetters(): void
    {
        $dto = new AlertsDto('CODE1', 'Test message', 'WARNING');
        $this->assertSame('CODE1', $dto->getCode());
        $this->assertSame('Test message', $dto->getMessage());
        $this->assertSame('WARNING', $dto->getAlertType());
    }

    /**
     * Test setters and getters
     */
    public function testSettersAndGetters(): void
    {
        $dto = new AlertsDto();
        $dto->setCode('CODE2');
        $dto->setMessage('Another message');
        $dto->setAlertType('ERROR');
        $this->assertSame('CODE2', $dto->getCode());
        $this->assertSame('Another message', $dto->getMessage());
        $this->assertSame('ERROR', $dto->getAlertType());
    }

    /**
     * Test toArray returns correct structure
     */
    public function testToArray(): void
    {
        $dto = new AlertsDto('CODE3', 'Array message', 'INFO');
        $expected = [
            'code' => 'CODE3',
            'message' => 'Array message',
            'alertType' => 'INFO',
        ];
        $this->assertSame($expected, $dto->toArray());
    }

    /**
     * Test default values
     */
    public function testDefaultValues(): void
    {
        $dto = new AlertsDto();
        $this->assertSame('', $dto->getCode());
        $this->assertSame('', $dto->getMessage());
        $this->assertSame('', $dto->getAlertType());
    }
}

