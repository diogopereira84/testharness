<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Request\Token;

use Fedex\OktaMFTF\Model\Config\Credentials as config;
use Fedex\OktaMFTF\Gateway\Request\Token\Body;
use PHPUnit\Framework\TestCase;

class BodyTest extends TestCase
{
    public function testBuildSuccess(): void
    {
        $configMock = $this->getMockBuilder(config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder = new Body($configMock);
        $configMock->expects($this->atLeast(2))->method('getGrantType')->willReturn('test');
        $configMock->expects($this->atLeast(2))->method('getScope')->willReturn('test');

        $result = $builder->build();

        $this->assertEquals(['body' => "grant_type=test&scope=test"], $result);
    }

    public function testBuildEmpty(): void
    {
        $configMock = $this->getMockBuilder(config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new Body($configMock);
        $result = $builder->build();

        $this->assertEquals([], $result);
    }
}
