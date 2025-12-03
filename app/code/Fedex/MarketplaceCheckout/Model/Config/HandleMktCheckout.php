<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Config;

class HandleMktCheckout extends ToggleBase implements ToggleInterface
{
    /**
     * Xpath enable marketplace minicart
     */
    private const XPATH_ENABLE_MARKETPLACE_MINICART =
        'environment_toggle_configuration/environment_toggle/enable_marketplace_minicart';

    /**
     * Xpath checkout delivery methods tooltip
     */
    private const XPATH_CHECKOUT_DELIVERY_METHODS_TOOLTIP = 'fedex/marketplace_configuration/delivery_methods_tooltip';

    /**
     * Xpath checkout shipping account message
     */
    private const XPATH_CHECKOUT_SHIPPING_ACCOUNT_MESSAGE = 'fedex/marketplace_configuration_toast/shipping_account_message';

    /**
     * Xpath enable marketplace cart
     */
    private const XPATH_ENABLE_MARKETPLACE_CART = 'environment_toggle_configuration/environment_toggle/enable_marketplace_cart';

    /**
     * Xpath for Toggle D-180031 Approved quotes in cart with other items are forced to go through shipping page
     */
    private const TIGERTEAM_D180031_FIX = 'environment_toggle_configuration/environment_toggle/tigerTeam_D180031_fix';

    /**
     * Xpath for Toggle TK-4410123 Enable payload webhook logs.
     */
    private const TIGER_TEAM_TK4410123_ENABLE_PAYLOAD_WEBHOOK_LOGS_TOGGLE
        = 'fedex/marketplacewebhook/enable_webhook_payload_logs';

    /**
     * Xpath for TTL seconds for duplicate webhook blocker.
     */
    private const TIGER_TEAM_TK4410123_TTL_BLOCK_SECONDS
        = 'fedex/marketplacewebhook/webhook_duplicate_block_ttl';

    /**
     * @inheritDoc
     */
    protected function getPathEnableMarketplaceMinicart(): string
    {
        return self::XPATH_ENABLE_MARKETPLACE_MINICART;
    }

    /**
     * @inheritDoc
     */
    protected function getPathCheckoutShippingAccountMessage(): string
    {
        return self::XPATH_CHECKOUT_SHIPPING_ACCOUNT_MESSAGE;
    }

    /**
     * @inheritDoc
     */
    protected function getPathEnableMarketplaceCart(): string
    {
        return self::XPATH_ENABLE_MARKETPLACE_CART;
    }

    /**
     * @inheritDoc
     */
    protected function getCheckoutDeliveryMethodsTooltipText(): string
    {
        return self::XPATH_CHECKOUT_DELIVERY_METHODS_TOOLTIP;
    }

    /**
     * @return string
     */
    public function getPathTigerTeamD180031Fix(): string
    {
        return self::TIGERTEAM_D180031_FIX;
    }

    /**
     * @return string
     */
    public function getPathToggleForEnableWebhookPayloadLogs(): string
    {
        return self::TIGER_TEAM_TK4410123_ENABLE_PAYLOAD_WEBHOOK_LOGS_TOGGLE;
    }

    /**
     * @return string
     */
    public function getPathToggleForTtlBlockSeconds(): string
    {
        return self::TIGER_TEAM_TK4410123_TTL_BLOCK_SECONDS;
    }
}
