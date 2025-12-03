<?php
/**
 * Interface CartInfoGrouperInterface
 *
 * Defines methods for handling shipping methods and related operations in the Fedex MarketplaceCheckout module.
 *
 * @category     Fedex
 * @package      Fedex_MarketplaceCheckout
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Api;

use Magento\Quote\Model\Quote;

interface CartInfoGrouperInterface
{
    /**
     * Retrieves marketplace cart information grouped by seller.
     *
     * @param Quote $quote
     * @return array
     */
    public function getMarketplaceCartInfoGroupedBySeller(Quote $quote): array;
}