<?php
/**
 * @category Fedex
 * @package  Fedex_SSO
 * @copyright   Copyright (c) 2021 Fedex
 * @author    Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Check if the SSO is enabled
     *
     * @return bool
     **/
    public function isEnabled(): bool;

    /**
     * Get login URL
     *
     * @return string
     **/
    public function getLoginPageURL(): string;

    /**
     * Get query parameter
     *
     * @return string
     **/
    public function getQueryParameter(): string;

    /**
     * Get register url
     *
     * @return string
     **/
    public function getRegisterUrl(): string;

    /**
     * Get register url parameter
     *
     * @return string
     **/
    public function getRegisterUrlParameter(): string;

    /**
     * Get Profile api URL
     *
     * @return string
     **/
    public function getProfileApiUrl(): string;

    /**
     * Get FCL my profile
     *
     * @return string
     **/
    public function getFclMyProfileUrl(): string;

    /**
     * Get FCL logout url
     *
     * @return string
     **/
    public function getFclLogoutUrl(): string;

    /**
     * Get FCL logout query param
     *
     * @return string
     **/
    public function getFclLogoutQueryParam(): string;

    /**
     * Get Profile mockup json
     *
     * @return string
     **/
    public function getProfileMockupJson(): string;


    /**
     * Get FCL New Logout Url
     *
     * @return string
     */
    public function getFclLogoutApiUrl(): string;
}
