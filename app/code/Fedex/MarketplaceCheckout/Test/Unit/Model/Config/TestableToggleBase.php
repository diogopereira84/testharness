<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model\Config;

use Fedex\MarketplaceCheckout\Model\Config\ToggleBase;

class TestableToggleBase extends ToggleBase
{
    protected function getPathEnableMarketplaceMinicart(): string
    {
        return 'path.minicart';
    }

    protected function getPathEnableMarketplaceCart(): string
    {
        return 'path.cart';
    }

    protected function getCheckoutDeliveryMethodsTooltipText(): string
    {
        return 'tooltip.path';
    }

    protected function getPathCheckoutShippingAccountMessage(): string
    {
        return 'shipping.message';
    }

    protected function getPathTigerTeamD180031Fix(): string
    {
        return 'tiger.fix';
    }

    protected function getPathToggleForPrintfulModifyOrderFulfillmentApiLogic(): string
    {
        return 'tiger.printful';
    }

    protected function getPathToggleForEnableWebhookPayloadLogs(): string
    {
        return 'webhook.payload.logs';
    }

    protected function getPathToggleForTtlBlockSeconds(): string
    {
        return 'ttl.block.seconds';
    }

    protected function getPathDuplicatedEmailsToggle(): string
    {
        return 'tiger_team_d_191183_multiple_order_confirmation_mails';
    }
}
