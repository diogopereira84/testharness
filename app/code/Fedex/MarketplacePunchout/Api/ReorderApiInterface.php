<?php

namespace Fedex\MarketplacePunchout\Api;

/**
 * Marketplace Reorder interface.
 *
 * @api
 */
interface ReorderApiInterface
{
    /**
     * Get Reorder Api data.
     *
     * @param string $brokerConfigId
     * @param string $productSku
     * @param string $orderIncrementId
     * @param int|null $itemQty
     * @param int|null $orderItemId
     * @return string
     */
    public function getReorderApiData(string $brokerConfigId, string $productSku, string $orderIncrementId, ?int $itemQty = null, int $orderItemId = null): string;
}
