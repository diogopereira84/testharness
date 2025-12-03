<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Request\Builder;

use Fedex\OktaMFTF\Gateway\Request\Builder\Header\Authorization;
use Fedex\OktaMFTF\Gateway\Request\Builder\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testBuild(): void
    {
        $authorizationMock = $this->getMockBuilder(Authorization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder = new Header($authorizationMock);
        $authorizationMock->expects($this->once())->method('build')->willReturn([]);
        $result = $builder->build();

        $this->assertEquals(['headers' => [
            'Accept' => 'application/json',
            'cache-control' => 'no-cache',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]], $result);
    }
}
