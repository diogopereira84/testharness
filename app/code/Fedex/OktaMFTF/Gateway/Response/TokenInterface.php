<?php
/**
 * @category Fedex
 * @package  Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Gateway\Response;

interface TokenInterface
{
    /**
     * Token type key
     */
    public const TOKEN_TYPE = 'token_type';

    /**
     * Expire In key
     */
    public const EXPIRES_IN = 'expires_in';

    /**
     * Access token key
     */
    public const ACCESS_TOKEN = 'access_token';

    /**
     * Scope key
     */
    public const SCOPE = 'scope';

    /**
     * Get token type property
     *
     * @return string
     */
    public function getTokenType(): string;

    /**
     * Set token type property
     *
     * @param string $tokenType
     * @return TokenInterface
     */
    public function setTokenType(string $tokenType): TokenInterface;

    /**
     * Get expires in property
     *
     * @return string
     */
    public function getExpiresIn(): string;

    /**
     * Set expires in property
     *
     * @param string $expiresIn
     * @return TokenInterface
     */
    public function setExpiresIn(string $expiresIn): TokenInterface;

    /**
     * Get access token property
     *
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * Set access token property
     *
     * @param string $accessToken
     * @return TokenInterface
     */
    public function setAccessToken(string $accessToken): TokenInterface;

    /**
     * Get scope property
     *
     * @return string
     */
    public function getScope(): string;

    /**
     * Set scope property
     *
     * @param string $scope
     * @return TokenInterface
     */
    public function setScope(string $scope): TokenInterface;
}
