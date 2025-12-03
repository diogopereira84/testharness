<?php
/**
 * @category Fedex
 * @package Fedex_WebAbalytics
 * @copyright Copyright (c) 2024.
 * @author Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Api\Data;

/**
 * Interface ConfigInterface
 */
interface NewRelicInterface
{
    public const XML_PATH_FEDEX_NEWRELIC_ACTIVE = 'web/newrelic/newrelic_active';
    public const XML_PATH_FEDEX_NEWRELIC_SCRIPT_CODE = 'web/newrelic/script_code';

    /**
     * Feature toggle for NewRelic.
     * Check if should show or not the script code.
     *
     * @return bool
     **/
    public function isActive(): bool;

    /**
     * Return the NewRelic source code to be displayed.
     *
     * @return ?string
     **/
    public function getScriptCode(): ?string;
}
