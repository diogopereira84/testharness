<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Api\Data;

/**
 * @class UserTokenResponseInterface
 *
 * This class represents the response from FEDEX user token api
 */
interface UserTokenResponseInterface
{
    /**
     * Status token key
     */
    const STATUS = 'status';

    /**
     * Access token key
     */
    const ACCESS_TOKEN = 'accessToken';

    /**
     * Client id key
     */
    const CLIENT_ID = 'clientId';

    /**
     * Expiration date time key
     */
    const EXPIRATION_DATE_TIME = 'expirationDateTime';

    /**
     * @return bool
     */
    public function getStatus(): bool;

    /**
     * @param bool $status
     * @return UserTokenResponseInterface
     */
    public function setStatus(bool $status): UserTokenResponseInterface;

    /**
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * @param string $accessToken
     * @return UserTokenResponseInterface
     */
    public function setAccessToken(string $accessToken): UserTokenResponseInterface;

    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @param string $clientId
     * @return UserTokenResponseInterface
     */
    public function setClientId(string $clientId): UserTokenResponseInterface;

    /**
     * @return string
     */
    public function getExpirationDateTime(): string;

    /**
     * @param string $expirationDateTime
     * @return UserTokenResponseInterface
     */
    public function setExpirationDateTime(string $expirationDateTime): UserTokenResponseInterface;
}
