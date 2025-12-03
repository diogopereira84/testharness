<?php

/**
 * @category    Fedex
 * @package     Fedex_OrderGraphQl
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Nitin Pawar <npawar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OrderGraphQl\Api;

use Magento\Sales\Model\Order;

interface GetPoliticalDisclosureInformationInterface
{
    /**
     * Get political disclosure information for an order.
     *
     * @param Order $order
     * @return array|null
     */
    public function getPoliticalDisclosureInfo(Order $order): ?array;
}

