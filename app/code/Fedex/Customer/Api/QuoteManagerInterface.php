<?php
/**
 * Interface QuoteManagerInterface
 *
 * Provides methods to manage quote operations after all items are removed.
 *
 * @category     Fedex
 * @package      Fedex_Customer
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Niket Kanoi <niket.kanoi.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Api;

use Magento\Quote\Model\Quote;

interface QuoteManagerInterface
{
    public function resetCustomerQuote(Quote $quote): void;
}