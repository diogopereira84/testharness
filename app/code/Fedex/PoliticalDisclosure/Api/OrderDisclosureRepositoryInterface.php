<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\PoliticalDisclosure\Api;

use Fedex\PoliticalDisclosure\Model\OrderDisclosure;

interface OrderDisclosureRepositoryInterface
{
    /**
     * @param int $orderId
     * @return OrderDisclosure|null
     */
    public function getByOrderId(int $orderId): ?OrderDisclosure;

    /**
     * @param OrderDisclosure $entity
     * @return OrderDisclosure
     */
    public function save(OrderDisclosure $entity): OrderDisclosure;

    /**
     * @param int $orderId
     * @return bool
     */
    public function deleteByOrderId(int $orderId): bool;

    /**
     * @param int $quoteId
     * @return OrderDisclosure|null
     */
    public function getByQuoteId(int $quoteId): ?OrderDisclosure;
}
