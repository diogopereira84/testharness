<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Response;

use Fedex\OktaMFTF\Gateway\Response\IntrospectInterface;
use PHPUnit\Framework\TestCase;
use Fedex\OktaMFTF\Gateway\Response\Introspect;

class IntrospectTest extends TestCase
{
    private Introspect $token;
    private bool $isActive = true;

    protected function setUp():void
    {
        $this->token = new Introspect([
            IntrospectInterface::ACTIVE => $this->isActive,
        ]);
    }

    public function testGetActive()
    {
        $this->assertEquals($this->isActive, $this->token->isActive());
    }

    public function testSetActive()
    {
        $newActive = false;
        $this->token->setActive($newActive);
        $this->assertEquals($newActive, $this->token->isActive());
    }
}
