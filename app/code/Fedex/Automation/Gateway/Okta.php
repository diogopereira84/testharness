<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Gateway;

use Fedex\CoreApi\Gateway\Http\Client;
use Fedex\CoreApi\Gateway\Http\TransferFactory;
use Fedex\CoreApi\Gateway\Http\TransferInterface;
use Fedex\Automation\Gateway\Request\Builder;
use Fedex\Automation\Gateway\Request\Builder\Header;
use Fedex\Automation\Gateway\Request\Builder\BaseUrl;
use Fedex\Automation\Gateway\Request\Token\Body as TokenBody;
use Fedex\Automation\Gateway\Response\Handler;
use Fedex\Automation\Gateway\Response\Introspect;
use Fedex\Automation\Gateway\Response\Introspect\Handler as IntrospectHandler;
use Fedex\Automation\Gateway\Response\Token;
use Fedex\OktaMFTF\Model\Config\Credentials;

class Okta
{
    public const TOKEN_PATH = '/v1/token';
    public const INTROSPECT_PATH = '/v1/introspect';

    public function __construct(
        protected Client            $client,
        protected Credentials       $credentials,
        protected TransferFactory   $transferFactory,
        protected Handler           $handler,
        protected BaseUrl           $baseUrl,
        protected IntrospectHandler $introspectHandler,
        protected Header            $header,
        protected TokenBody         $tokenBody,
        protected Builder           $builder
    )
    {
    }

    public function token(): Token
    {
        $this->builder
            ->add($this->header)
            ->add($this->tokenBody);
        $response = $this->client->request(
            $this->transferFactory->create(['data' => [
                TransferInterface::METHOD => 'POST',
                TransferInterface::URI => $this->baseUrl->build() . self::TOKEN_PATH,
                TransferInterface::PARAMS => $this->builder->build()
            ]])
        );
        return $this->handler->handle($response);
    }

    public function introspect($token): Introspect
    {
        $this->builder->add($this->header);
        $response = $this->client->request(
            $this->transferFactory->create(['data' => [
                TransferInterface::METHOD => 'POST',
                TransferInterface::URI => $this->baseUrl->build() . self::INTROSPECT_PATH,
                TransferInterface::PARAMS => $this->builder->build([
                    'body' => "token_type_hint=access_token&token=$token"
                ])
            ]])
        );
        return $this->introspectHandler->handle($response);
    }
}
