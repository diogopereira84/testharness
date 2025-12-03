<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model\UrlProcessor;

interface SearchPageInterface
{
    /**
     * Check if current page is a search page
     *
     * @return bool
     */
    public function isSearchPage(): bool;

    /**
     * Retrieve the page type
     *
     * @return string
     */
    public function getType(): string;
}
