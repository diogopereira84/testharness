<?php

namespace Fedex\MarketplaceCheckout\Api;

/**
 *
 */
interface OrderStoreRetrieverInterface
{

    /**
     * @param int $orderId
     * @return int
     */
    public function getStoreIdFromOrder(int $orderId): int;

}
