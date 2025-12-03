<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Config;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

abstract class ToggleBase implements ToggleInterface
{
    /**
     * Xpath for Toggle Enable webhook duplicate blocker toggle.
     */
    private const XPATH_ENABLE_WEBHOOOK_DUPLICATE_BLOCKER_TOGGLE
        = 'tiger_team_tk_4410123_duplicated_shipment_webhook_blocker';

    /**
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Return the XML path for MarketPlace Minicart.
     *
     * @return string
     */
    abstract protected function getPathEnableMarketplaceMinicart(): string;

    /**
     * Return the XML path for MarketPlace Cart.
     *
     * @return string
     */
    abstract protected function getPathEnableMarketplaceCart(): string;

    /**
     * Return the XML path for checkout delivery methods tooltip text.
     *
     * @return string
     */
    abstract protected function getCheckoutDeliveryMethodsTooltipText(): string;

    /**
     * Return the XML path for checkout apply shipping account message.
     *
     * @return string
     */
    abstract protected function getPathCheckoutShippingAccountMessage(): string;

    /**
     * Return the XML path for checkout apply shipping account message.
     *
     * @return string
     */
    abstract protected function getPathTigerTeamD180031Fix(): string;

    /**
     * Return the XML path for Print Modify Order Fulfillment Api Logic Toggle
     *
     * @return string
     */
    abstract protected function getPathToggleForEnableWebhookPayloadLogs(): string;

    /**
     * Return the XML path for Print Modify Order Fulfillment Api Logic Toggle
     *
     * @return string
     */
    abstract protected function getPathToggleForTtlBlockSeconds(): string;

    /**
     * @inheritDoc
     */
    public function getCheckoutDeliveryMethodsTooltip(): string
    {
        return (string)$this->toggleConfig->getToggleConfig(
            $this->getCheckoutDeliveryMethodsTooltipText()
        ) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getCheckoutShippingAccountMessage(): string
    {
        return (string) $this->toggleConfig->getToggleConfig(
            $this->getPathCheckoutShippingAccountMessage()
        ) ?? '';
    }

    /**
     * @return bool
     */
    public function getTigerTeamD180031Fix(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(
            $this->getPathTigerTeamD180031Fix()
        ) ?? false;
    }

    /**
     * @return bool
     */
    public function getDuplicateWebhookBlockerToggle(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(
            self::XPATH_ENABLE_WEBHOOOK_DUPLICATE_BLOCKER_TOGGLE
        );
    }

    /**
     * @return bool
     */
    public function getTogglePayloadWebhookLogs(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(
            $this->getPathToggleForEnableWebhookPayloadLogs()
        ) ?? false;
    }

    /**
     * @return int
     */
    public function getTtlBlockWebhookInSeconds(): int
    {
        return (int) $this->toggleConfig->getToggleConfig(
            $this->getPathToggleForTtlBlockSeconds()
        );
    }
}
