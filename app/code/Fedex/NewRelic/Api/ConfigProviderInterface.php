<?php
/**
 * @category  Fedex
 * @package   Fedex_NewRelic
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\NewRelic\Api;

interface ConfigProviderInterface
{
    /**
     * Retrieve marker status
     *
     * @return bool
     */
    public function getStatus(): bool;

    /**
     * Retrieve marker api url
     *
     * @return string
     */
    public function getApiUrl(): string;

    /**
     * Retrieve marker api key
     *
     * @return string
     */
    public function getApiKey(): string;

    /**
     * Retrieve marker app identifier
     *
     * @return string
     */
    public function getAppIdentifier(): string;

    /**
     * Retrieve marker description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Retrieve marker changelog
     *
     * @return string
     */
    public function getChangeLog(): string;

    /**
     * Retrieve marker user
     *
     * @return string
     */
    public function getUser(): string;

    /**
     * Check if you can perform deployment marker
     *
     * @return bool
     */
    public function canPerformDeploymentMarker(): bool;

    /**
     * Reset change log fields
     *
     * @return void
     */
    public function resetFields(): void;
}
