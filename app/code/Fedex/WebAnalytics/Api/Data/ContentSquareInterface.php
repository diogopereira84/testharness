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
 * Interface ConfigInterface
 */
interface ContentSquareInterface
{
    /**
     * Feature toggle for Contentsquare.
     * Check if should show or not the script code.
     *
     * @return bool
     **/
    public function isActive(): bool;

    /**
     * Return the Contentsquare source code to be displayed.
     *
     * @return ?string
     **/
    public function getScriptCode(): ?string;
}
