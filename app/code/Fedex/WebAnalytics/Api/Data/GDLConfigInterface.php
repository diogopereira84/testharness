<?php
/**
 * @category Fedex
 * @package  Fedex_WebAnalytics
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Api\Data;

/**
 * Interface GDLConfigInterface
 */
interface GDLConfigInterface
{
    /**
     * Feature toggle for GDL.
     * Check if should show or not the script code.
     * @return bool
     **/
    public function isActive(): bool;

    /**
     * Return the GDL source code to be displayed.
     *
     * @return ?string
     **/
    public function getScriptCode(): ?string;

    /**
     * Return the GDL domain prefix
     *
     * @return ?string
     **/
    public function getSubDomainPrefix(): ?string;

    /**
     * Return the Page Types configured for CMS pages
     *
     * @return ?string
     **/
    public function getPageTypes(): ?string;

    /**
     * Return Script fully Rendered with Page values included
     *
     * @return string|null
     */

    public function getScriptFullyRendered(): ?string;
}
