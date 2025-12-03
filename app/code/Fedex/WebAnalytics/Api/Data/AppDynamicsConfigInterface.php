<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2023.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Api\Data;

/**
 * Interface AppDynamicsConfigInterface
 */
interface AppDynamicsConfigInterface
{
    /**
     * Feature toggle for AppDynamics.
     * Check if should show or not the script code.
     *
     * @return bool
     **/
    public function isActive(): bool;

    /**
     * Return the AppDynamics source code to be displayed.
     *
     * @return ?string
     **/
    public function getScriptCode(): ?string;
}
