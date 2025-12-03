<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Gateway\Response;

use Fedex\Canva\Api\Data\UserTokenResponseInterface;
use Magento\Framework\DataObject;

class UserToken extends DataObject implements UserTokenResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getStatus(): bool
    {
        return $this->getData(static::STATUS) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(bool $status): UserTokenResponseInterface
    {
        return $this->setData(static::STATUS, $status);
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
    public function setAccessToken(string $accessToken): UserTokenResponseInterface
    {
        return $this->setData(static::ACCESS_TOKEN, $accessToken);
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return $this->getData(static::CLIENT_ID) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setClientId(string $clientId): UserTokenResponseInterface
    {
        return $this->setData(static::CLIENT_ID, $clientId);
    }

    /**
     * @inheritDoc
     */
    public function getExpirationDateTime(): string
    {
        return $this->getData(static::EXPIRATION_DATE_TIME) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setExpirationDateTime(string $expirationDateTime): UserTokenResponseInterface
    {
        return $this->setData(static::EXPIRATION_DATE_TIME, $expirationDateTime);
    }
}
