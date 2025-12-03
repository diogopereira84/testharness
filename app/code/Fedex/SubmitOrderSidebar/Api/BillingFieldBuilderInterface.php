<?php
/**
 * @category  Fedex
 * @package   Fedex_SubmitOrderSidebar
 * @author    Nathan Alves <nathan.alves.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Api;

use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldCollectionInterface;
use Magento\Quote\Api\Data\CartInterface;

interface BillingFieldBuilderInterface
{
    /**
     * Builds billing fields object
     *
     * @param CartInterface $quote
     * @return BillingFieldCollectionInterface
     */
    public function build(CartInterface $quote): BillingFieldCollectionInterface;
}
