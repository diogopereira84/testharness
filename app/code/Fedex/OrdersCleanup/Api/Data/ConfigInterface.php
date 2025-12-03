<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrdersCleanup\Api\Data;

interface ConfigInterface
{
    /**
     * Get configuration value
     *
     * @param string $configPath
     * @return mixed
     */
    public function getConfigValue(string $configPath): mixed;

    /**
     * Check if remove enabled
     *
     * @return bool
     */
    public function isRemoveEnabled(): bool;

    /**
     * Get logged in users order age
     *
     * @return int
     */
    public function getLoggedInUsersRetentionDays(): int;

    /**
     * Get guest users order age
     *
     * @return int
     */
    public function getGuestUsersRetentionDays(): int;

    /**
     * Get order statuses
     *
     * @return string[]
     */
    public function getOrderStatuses(): array;

}