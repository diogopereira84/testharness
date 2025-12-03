<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Api\Data;

use Fedex\Base\Api\Data\DataObjectInterface;

interface RateInterface extends DataObjectInterface
{
    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @param string $currency
     * @return RateInterface
     */
    public function setCurrency(string $currency): RateInterface;

    /**
     * @return RateDetailCollectionInterface
     */
    public function getDetails(): RateDetailCollectionInterface;

    /**
     * @param RateDetailCollectionInterface $details
     * @return RateInterface
     */
    public function setDetails(RateDetailCollectionInterface $details): RateInterface;

    /**
     * Check if it has a shipping delivery lines
     *
     * @return bool
     */
    public function hasDetailShippingDeliveryLine(): bool;

    /**
     * Check if the has a shipping delivery line with discounts
     *
     * @return bool
     */
    public function hasDetailShippingDeliveryDiscount(): bool;

    /**
     * Check if details has discounts
     *
     * @return bool
     */
    public function hasDetailCouponDiscounts(): bool;

    /**
     * Check if details coupon discount amount
     * is the same as shipping delivery line discount
     *
     * @return bool
     */
    public function isDetailCouponDiscountSameAsShippingDeliveryLineDiscount(): bool;
}
