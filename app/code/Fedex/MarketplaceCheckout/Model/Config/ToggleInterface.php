<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\Config;

interface ToggleInterface
{

    /**
     * Get checkout delivery methods tooltip
     *
     * @return string
     */
    public function getCheckoutDeliveryMethodsTooltip(): string;
    public function getCheckoutShippingAccountMessage(): string;
    public function getTigerTeamD180031Fix(): bool;
}
