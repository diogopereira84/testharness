<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Response;

use Fedex\Automation\Gateway\Response\IntrospectInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Automation\Gateway\Response\Introspect;

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
