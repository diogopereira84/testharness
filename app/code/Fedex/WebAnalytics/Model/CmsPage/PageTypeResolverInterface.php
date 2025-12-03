<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model\CmsPage;

interface PageTypeResolverInterface
{
    /**
     * Return the page type value
     * if current page is a cms page
     * and the value is configured in admin
     *
     * @return ?string
     */
    public function resolve(): ?string;
}
