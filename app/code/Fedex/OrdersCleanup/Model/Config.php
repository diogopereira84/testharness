<?php
/**
 * @category    Fedex
 * @package     Fedex_OrdersCleanup
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\OrdersCleanup\Model;

use Fedex\OrdersCleanup\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{

    private const ORDERSCLEANUP_ENABLE_LOGGING = 'orders_cleanup/remove/enable_logging';
    private const ORDERSCLEANUP_IS_REMOVE_ENABLED = 'orders_cleanup/remove/is_enabled';
    private const LOGGEDINORDERSCLEANUP_AGE = 'orders_cleanup/remove/loggedin_user_order_retention_days';
    private const GUESTORDERSCLEANUP_AGE = 'orders_cleanup/remove/guest_user_order_retention_days';
    private const ORDERSCLEANUP_TERMINATELIMIT = 'orders_cleanup/remove/terminate';
    public const SALES_INVOICE_GRID = 'sales_invoice_grid';
    public const SALES_SHIPMENT_GRID = 'sales_shipment_grid';
    public const ORDER_PRODUCING_ADDRESS = 'order_producing_address';
    public const SALES_CREDITMEMO_GRID = 'sales_creditmemo_grid';
    public const SALES_ORDER_GRID = 'sales_order_grid';
    private const SGC_ORDERCLEANUP_TOGGLE = 'environment_toggle_configuration/environment_toggle/sgc_e455559_ordercleanup_process';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Get configuration value
     *
     * @param string $configPath
     * @return mixed
     */
    public function getConfigValue(string $configPath): mixed
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if remove enabled
     *
     * @return bool
     */
    public function isRemoveEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::ORDERSCLEANUP_IS_REMOVE_ENABLED);
    }

    /**
     * Get logged in users order age
     *
     * @return int
     */
    public function getLoggedInUsersRetentionDays(): int
    {
        $age = $this->getConfigValue(self::LOGGEDINORDERSCLEANUP_AGE) ?? 390;
        return (int)$age;
    }

    /**
     * Get guest users order age
     *
     * @return int
     */
    public function getGuestUsersRetentionDays(): int
    {
        $age = $this->getConfigValue(self::GUESTORDERSCLEANUP_AGE) ?? 390;
        return (int)$age;
    }

    /**
     * Get terminate limit
     *
     * @return int
     */
    public function getTerminateLimit(): int
    {
        $age = $this->getConfigValue(self::ORDERSCLEANUP_TERMINATELIMIT) ?? 0;
        return (int)$age;
    }

    /**
     * Get order statuses
     *
     * @return string[]
     */
    public function getOrderStatuses(): array
    {
        return ['processing', 'assigned', 'delivered', 'ready_for_pickup', 'shipped'];
    }

    /**
     * Enable logs
     */
    public function isLoggingEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::ORDERSCLEANUP_ENABLE_LOGGING);
    }

    public function isSgcOrderCleanupProcessEnabled(): bool
    {
        return (bool)$this->getConfigValue(self::SGC_ORDERCLEANUP_TOGGLE);
    }
}