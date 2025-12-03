<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Gateway;

use Fedex\CoreApi\Gateway\Http\Client;
use Fedex\CoreApi\Gateway\Http\Transfer;
use Fedex\Automation\Gateway\Request\Builder;
use Fedex\CoreApi\Gateway\Http\TransferFactory;
use Fedex\Automation\Gateway\Request\Builder\BaseUrl;
use Fedex\Automation\Gateway\Request\Builder\Header;
use Fedex\Automation\Gateway\Request\Token\Body as TokenBody;
use Fedex\Automation\Gateway\Response\Handler;
use Fedex\Automation\Gateway\Response\Introspect;
use Fedex\Automation\Gateway\Response\Introspect\Handler as IntrospectHandler;
use Fedex\Automation\Gateway\Response\Token;
use Fedex\Automation\Gateway\Response\TokenInterface;
use Fedex\OktaMFTF\Model\Config\Credentials;
use Fedex\Automation\Gateway\Okta;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class OktaTest extends TestCase
{
    private string $method = 'POST';
    private array $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];
    private array $body = [
        'data' => [
            'data' => 'data'
        ]
    ];
    private string $uri = '/';
    private array $params = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => '{"data":{"data":"data"}}'
    ];

    public function testToken()
    {
        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $credentialsMock = $this->getMockBuilder(Credentials::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferFactoryMock = $this->getMockBuilder(TransferFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handlerMock = $this->getMockBuilder(Handler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseUrlMock = $this->getMockBuilder(BaseUrl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $introspectHandlerMock = $this->getMockBuilder(IntrospectHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $headerMock = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenBodyMock = $this->getMockBuilder(TokenBody::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transferFactoryMock->expects($this->once())->method('create')->willReturn(new Transfer());
        $clientMock->expects($this->once())->method('request')
            ->willReturn(new Response(200, [], json_encode([ 'access_token' => 'token' ])));
        $handlerMock->expects($this->once())->method('handle')
            ->willReturn(new Token([
                TokenInterface::TOKEN_TYPE => 'grant',
                TokenInterface::EXPIRES_IN => 'VALID_EXPIRATION_DATE',
                TokenInterface::ACCESS_TOKEN => 'VALID_ACCESS_TOKEN',
                TokenInterface::SCOPE => 'oob',
            ]));
        $okta = new Okta(
            $clientMock,
            $credentialsMock,
            $transferFactoryMock,
            $handlerMock,
            $baseUrlMock,
            $introspectHandlerMock,
            $headerMock,
            $tokenBodyMock,
            $builderMock
        );

        $this->isInstanceOf(Token::class, $okta->token());
    }

    public function testIntrospect()
    {
        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $credentialsMock = $this->getMockBuilder(Credentials::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferFactoryMock = $this->getMockBuilder(TransferFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handlerMock = $this->getMockBuilder(Handler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseUrlMock = $this->getMockBuilder(BaseUrl::class)
            ->disableOriginalConstructor()
            ->getMock();
        $introspectHandlerMock = $this->getMockBuilder(IntrospectHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $headerMock = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenBodyMock = $this->getMockBuilder(TokenBody::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transferFactoryMock->expects($this->once())->method('create')->willReturn(new Transfer());
        $clientMock->expects($this->once())->method('request')
            ->willReturn(new Response(200, [], json_encode([ 'active' => true ])));
        $introspectHandlerMock->expects($this->once())->method('handle')
            ->willReturn(new Introspect());
        $okta = new Okta(
            $clientMock,
            $credentialsMock,
            $transferFactoryMock,
            $handlerMock,
            $baseUrlMock,
            $introspectHandlerMock,
            $headerMock,
            $tokenBodyMock,
            $builderMock
        );

        $this->isInstanceOf(Introspect::class, $okta->introspect('some_token'));
    }
}
