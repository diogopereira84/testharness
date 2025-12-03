<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Shipto\Test\Unit\Plugin;

use Fedex\Shipto\Helper\Data;
use Fedex\Shipto\Plugin\LocationIdAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocationIdAdapterTest extends TestCase
{
    /** @var MockObject  */
    private MockObject $helperDataMock;

    /** @var LocationIdAdapter  */
    private LocationIdAdapter $locationIdAdapter;

    /**
     * Setup for Test Case
     */
    protected function setUp(): void
    {
        $this->helperDataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->locationIdAdapter = new LocationIdAdapter();
    }

    /**
     * @return void
     */
    public function testBeforeGetAddressByLocationIdTestIntegerParameter(): void
    {
        $result = $this->locationIdAdapter
            ->beforeGetAddressByLocationId(
                $this->helperDataMock,
                1
            );
        $expected = [1, false];
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testBeforeGetAddressByLocationIdTestString(): void
    {
        $result = $this->locationIdAdapter
            ->beforeGetAddressByLocationId(
                $this->helperDataMock,
                "1"
            );
        $expected = ["1", false];
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testBeforeGetAddressByLocationIdTestStringNull(): void
    {
        $result = $this->locationIdAdapter
            ->beforeGetAddressByLocationId(
                $this->helperDataMock,
                "NULL"
            );
        $expected = [null, false];
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testBeforeGetAddressByLocationIdTestStringNullLowerCase(): void
    {
        $result = $this->locationIdAdapter
            ->beforeGetAddressByLocationId(
                $this->helperDataMock,
                "null"
            );
        $expected = [null, false];
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testBeforeGetAddressByLocationIdTestNull(): void
    {
        $result = $this->locationIdAdapter
            ->beforeGetAddressByLocationId(
                $this->helperDataMock,
                null
            );
        $expected = [null, false];
        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
    }
}
