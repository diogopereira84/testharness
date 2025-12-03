<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Response;

use Magento\Framework\DataObject;

class Token extends DataObject implements TokenInterface
{
    /**
     * @inheritDoc
     */
    public function getTokenType(): string
    {
        return $this->getData(static::TOKEN_TYPE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setTokenType(string $tokenType): TokenInterface
    {
        return $this->setData(static::TOKEN_TYPE, $tokenType);
    }

    /**
     * @inheritDoc
     */
    public function getExpiresIn(): string
    {
        return $this->getData(static::EXPIRES_IN) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setExpiresIn(string $expiresIn): TokenInterface
    {
        return $this->setData(static::EXPIRES_IN, $expiresIn);
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken(): string
    {
        return $this->getData(static::ACCESS_TOKEN) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setAccessToken(string $accessToken): TokenInterface
    {
        return $this->setData(static::ACCESS_TOKEN, $accessToken);
    }

    /**
     * @inheritDoc
     */
    public function getScope(): string
    {
        return $this->getData(static::SCOPE) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setScope(string $scope): TokenInterface
    {
        return $this->setData(static::SCOPE, $scope);
    }
}
