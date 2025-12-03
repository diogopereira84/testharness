<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SharedDetails\Api;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Resolve store email from pickup address
 */
interface PickupStoreEmailResolverInterface
{
    /**
     * Get store emails for multiple order IDs.
     *
     * @param int[] $orderIds
     * @return array<int, string|null> [order_id => store_email]
     */
    public function getStoreEmailsByOrderIds(array $orderIds): array;
}
