<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Request;

use Fedex\Automation\Gateway\Request\Builder;
use Fedex\Automation\Gateway\Request\Builder\Header;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    public function testBuilder()
    {
        $buildMock = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder = new Builder();

        $builder->add($buildMock);

        $buildMock->expects($this->once())->method('build');
        $builder->build();
    }
}
