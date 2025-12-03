<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway\Response;

use Fedex\Automation\Gateway\Response\Handler;
use Fedex\Automation\Gateway\Response\TokenFactory;
use Fedex\Automation\Gateway\Response\Token;
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
    private TokenFactory|MockObject $tokenFactory;

    protected function setUp():void
    {
        $this->tokenFactory = $this->getMockBuilder(TokenFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler = new Handler(new Json(), $this->tokenFactory, new JsonValidator());
    }

    public function testHandle()
    {
        $accessToken = 'VALID_ACCESS_TOKEN';
        $handlingSubject = new Response(200, [], json_encode([ 'access_token' => $accessToken ]));
        $this->tokenFactory->expects($this->any())->method('create')->willReturn(new Token());
        $userToken = $this->handler->handle($handlingSubject);
        $this->assertEquals($accessToken, $userToken->getAccessToken());

    }
}
