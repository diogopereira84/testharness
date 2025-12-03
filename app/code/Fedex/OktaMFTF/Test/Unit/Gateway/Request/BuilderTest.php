<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Request;

use Fedex\OktaMFTF\Gateway\Request\Builder;
use Fedex\OktaMFTF\Gateway\Request\Builder\Header;
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
