<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Response\Introspect;

use Fedex\Automation\Gateway\Response\Introspect\Handler;
use Fedex\Automation\Gateway\Response\IntrospectFactory;
use Fedex\Automation\Gateway\Response\Introspect;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;

class HandlerTest extends TestCase
{
    private Handler $handler;
    private IntrospectFactory|MockObject $factory;

    protected function setUp():void
    {
        $this->factory = $this->getMockBuilder(IntrospectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new Handler(new Json(), $this->factory, new JsonValidator());
    }

    public function testHandle()
    {
        $isActive = true;
        $handlingSubject = new Response(200, [], json_encode([ 'active' => $isActive ]));
        $this->factory->expects($this->any())->method('create')->willReturn(new Introspect());
        $introspect = $this->handler->handle($handlingSubject);
        $this->assertEquals($isActive, $introspect->isActive());

    }
}
