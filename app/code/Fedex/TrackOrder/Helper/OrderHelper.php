<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\TrackOrder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class OrderHelper extends AbstractHelper
{
    /**
     * Segregate order IDs based on prefix.
     *
     * @param array $orderIds
     * @return array
     */

    public function segregateOrderIds(array $orderIds): array
    {
        $apiOrders = $magOrders = [];

        // Loop through $orderIds and segregate them based on prefixes
        foreach ($orderIds as $orderId) {
            if (str_starts_with($orderId, '3010') || str_starts_with($orderId, '2020')) {
                $apiOrders[] = (int) $orderId;
            } else { // Includes Retail, Epro and Fuse orders
                $magOrders[] = $orderId;
            }
        }

        return [
            'apiOrders' => $apiOrders,
            'magOrders' => $magOrders,
        ];
    }
}
