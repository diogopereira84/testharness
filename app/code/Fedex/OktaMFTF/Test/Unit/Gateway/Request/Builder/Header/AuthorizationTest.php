<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Request\Builder\Header;

use Magento\Framework\App\Request\Http;
use Fedex\OktaMFTF\Gateway\Request\Builder\Header\Authorization;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    public function testBuildSuccess(): void
    {
        $httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $httpMock->expects($this->any())
            ->method('getParam')
            ->willReturn('random_secret');

        $builder = new Authorization($httpMock);
        $result = $builder->build();

        $this->assertEquals([
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(
                        "random_secret:random_secret"
                    )
            ]
        ], $result);
    }

    public function testBuildFail(): void
    {
        $httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new Authorization($httpMock);
        $result = $builder->build();

        $this->assertEquals([], $result);
    }
}
