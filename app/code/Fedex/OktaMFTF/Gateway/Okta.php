<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway;

use Fedex\CoreApi\Gateway\Http\Client;
use Fedex\CoreApi\Gateway\Http\TransferFactory;
use Fedex\CoreApi\Gateway\Http\TransferInterface;
use Fedex\OktaMFTF\Gateway\Request\Builder;
use Fedex\OktaMFTF\Gateway\Request\Builder\Header;
use Fedex\OktaMFTF\Gateway\Request\Builder\BaseUrl;
use Fedex\OktaMFTF\Gateway\Request\Token\Body as TokenBody;
use Fedex\OktaMFTF\Gateway\Response\Handler;
use Fedex\OktaMFTF\Gateway\Response\Introspect;
use Fedex\OktaMFTF\Gateway\Response\Introspect\Handler as IntrospectHandler;
use Fedex\OktaMFTF\Gateway\Response\Token;
use Fedex\OktaMFTF\Model\Config\Credentials;

class Okta
{
    public const TOKEN_PATH = '/v1/token';
    public const INTROSPECT_PATH = '/v1/introspect';

    public function __construct(
        private Client $client,
        private Credentials $credentials,
        private TransferFactory $transferFactory,
        private Handler $handler,
        private BaseUrl $baseUrl,
        private IntrospectHandler $introspectHandler,
        private Header $header,
        private TokenBody $tokenBody,
        private Builder $builder
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
