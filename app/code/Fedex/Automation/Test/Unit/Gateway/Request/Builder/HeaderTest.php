<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Request\Builder;

use Fedex\Automation\Gateway\Request\Builder\Header\Authorization;
use Fedex\Automation\Gateway\Request\Builder\Header;
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
