<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Gateway\Response\Introspect;

use Fedex\OktaMFTF\Gateway\Response\Introspect\Handler;
use Fedex\OktaMFTF\Gateway\Response\IntrospectFactory;
use Fedex\OktaMFTF\Gateway\Response\Introspect;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;

class HandlerTest extends TestCase
{
    /**
     * @var Handler
     */
    private Handler $handler;

    /**
     * @var IntrospectFactory|MockObject
     */
    private $factory;

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
