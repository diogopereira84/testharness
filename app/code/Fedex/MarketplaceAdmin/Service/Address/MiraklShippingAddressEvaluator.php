<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Service\Address;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Fedex\MarketplaceAdmin\Model\Config;

class MiraklShippingAddressEvaluator
{
    /**
     * @param Config $config
     */
    public function __construct(
        private Config $config
    ) {}

    /**
     * @param Order $order
     * @param Address $address
     * @return bool
     */
    public function shouldOverride(Order $order, Address $address): bool
    {
        return $this->config->isD226848Enabled()
            && $order->getShippingAddress()
            && $address->getId() === $order->getShippingAddress()->getId()
            && $order->getShippingMethod() === 'fedexshipping_PICKUP';
    }
}
