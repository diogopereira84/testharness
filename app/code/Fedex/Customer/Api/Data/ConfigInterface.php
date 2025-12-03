<?php
/**
 * @category Fedex
 * @package  Fedex_Customer
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Api\Data;

/**
 * Interface ConfigInterface
 */
interface ConfigInterface
{
    /**
     * Return the Marketing Opt-In Enable status
     *
     * @return bool
     **/
    public function isMarketingOptInEnabled(): bool;

    /**
     * Return Marketing Opt-In Api URL
     *
     * @return string|null
     **/
    public function getMarketingOptInApiUrl(): string|null;

    /**
     * Return Marketing Opt-In URL for Success Page
     *
     * @return string|null
     **/
    public function getMarketingOptInUrlSuccessPage(): string|null;
}
