<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Framework\Api\ExtensibleDataInterface;

interface CancelOrderInterface extends ExtensibleDataInterface
{
    /**
     * @param string $miraklOrderId
     * @return void
     */
    public function cancelOrder(string $miraklOrderId): bool;
}
