<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Request\Builder\Header;

use Magento\Framework\App\Request\Http;
use Fedex\Automation\Gateway\Request\Builder\Header\Authorization;
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
            ->with('token')
            ->willReturn('Y3JlZGVudGlhbHNfZW5jb2RlZF9pbl9iYXNlNjQ=');

        $builder = new Authorization($httpMock);
        $result = $builder->build();

        $this->assertEquals([
            'headers' => [
                'Authorization' => 'Basic Y3JlZGVudGlhbHNfZW5jb2RlZF9pbl9iYXNlNjQ='
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
