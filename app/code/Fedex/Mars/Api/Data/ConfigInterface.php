<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Api\Data;

interface ConfigInterface
{
    /**
     * Check if module enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get client id
     *
     * @return string
     */

    public function getClientId(): string;

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret(): string;

    /**
     * Get resource
     *
     * @return string
     */
    public function getResource(): string;

    /**
     * Get grant type
     *
     * @return string
     */
    public function getGrantType(): string;

    /**
     * Get token url
     *
     * @return string
     */
    public function getTokenApiUrl(): string;

    /**
     * Get api url
     *
     * @return string
     */
    public function getApiUrl(): string;

    /**
     * Get max retries
     *
     * @return int
     */
    public function getMaxRetries(): int;
}
