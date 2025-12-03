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

class MiraklShippingAddressProvider
{
    /**
     * @param Order $order
     * @return array|null
     */
    public function getAddress(Order $order): ?array
    {
        foreach ($order->getAllItems() as $item) {
            $additionalData = $item->getAdditionalData();
            if (!$additionalData) {
                continue;
            }

            $decoded = json_decode($additionalData, true);
            if (!is_array($decoded)) {
                continue;
            }

            if (isset($decoded['mirakl_shipping_data']['address'])) {
                return $decoded['mirakl_shipping_data']['address'];
            }
        }
        return null;
    }
}
