<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

interface RateQuoteDiscountCollectionInterface
{

    /**
     * Get item by type
     *
     * @param string $type
     *
     * @return RateQuoteDiscountInterface
     */
    public function getItemByType(string $type): RateQuoteDiscountInterface;

    /**
     * Get coupon discount
     *
     * @return RateQuoteDiscountInterface
     */
    public function getCouponDiscount(): RateQuoteDiscountInterface;

    /**
     * Get Ar customer discount
     *
     * @return RateQuoteDiscountInterface
     */
    public function getArCustomersDiscount(): RateQuoteDiscountInterface;

    /**
     * Check for coupon discount
     *
     * @return bool
     */
    public function hasCouponDiscount(): bool;

    /**
     * Check for ar customers discount
     *
     * @return bool
     */
    public function hasArCustomersDiscount(): bool;
}
